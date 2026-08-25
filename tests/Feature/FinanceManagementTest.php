<?php

namespace Tests\Feature;

use App\Models\FinanceCategory;
use App\Models\FinancialTransaction;
use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use OpenSpout\Reader\XLSX\Reader;
use Tests\TestCase;

class FinanceManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_finance_area_and_export_are_admin_only(): void
    {
        $member = User::factory()->create();
        $member->assignRole('member');
        $trainer = User::role('trainer')->firstOrFail();

        $this->actingAs($member)->get(route('admin.finance.index'))->assertForbidden();
        $this->actingAs($member)->get(route('admin.finance.export'))->assertForbidden();
        $this->actingAs($trainer)->get(route('admin.finance.index'))->assertForbidden();
        $this->actingAs($trainer)->get(route('admin.finance.export'))->assertForbidden();

        $this->actingAs($this->admin())
            ->get(route('admin.finance.index'))
            ->assertOk()
            ->assertSee('Finance & reports', false)
            ->assertSee('Record confirmed income or expense');
    }

    public function test_admin_can_record_edit_and_remove_manual_transactions(): void
    {
        $admin = $this->admin();
        $expenseCategory = FinanceCategory::where('system_code', 'utilities')->firstOrFail();

        $this->actingAs($admin)
            ->post(route('admin.finance.transactions.store'), $this->transactionPayload($expenseCategory, [
                'description' => 'August electricity bill',
                'amount' => '18750.50',
                'supplier_payee' => 'Electricity Board',
            ]))
            ->assertSessionHasNoErrors();

        $transaction = FinancialTransaction::where('description', 'August electricity bill')->firstOrFail();
        $this->assertSame($admin->id, $transaction->created_by);
        $this->assertFalse($transaction->is_automatic);

        $this->actingAs($admin)
            ->patch(route('admin.finance.transactions.update', $transaction), $this->transactionPayload($expenseCategory, [
                'description' => 'Corrected electricity bill',
                'amount' => '18000.00',
            ]))
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('financial_transactions', [
            'id' => $transaction->id,
            'description' => 'Corrected electricity bill',
            'amount' => 18000,
            'voided_at' => null,
        ]);

        $this->actingAs($admin)
            ->delete(route('admin.finance.transactions.destroy', $transaction))
            ->assertSessionHasNoErrors();

        $this->assertNotNull($transaction->fresh()->voided_at);
    }

    public function test_transaction_category_must_match_its_type(): void
    {
        $incomeCategory = FinanceCategory::where('system_code', FinanceCategory::CODE_MEMBERSHIPS)->firstOrFail();

        $this->actingAs($this->admin())
            ->post(route('admin.finance.transactions.store'), $this->transactionPayload($incomeCategory, [
                'transaction_type' => FinanceCategory::TYPE_EXPENSE,
                'description' => 'Invalid mixed transaction',
            ]))
            ->assertSessionHasErrors('finance_category_id');

        $this->assertDatabaseMissing('financial_transactions', ['description' => 'Invalid mixed transaction']);
    }

    public function test_finance_report_filters_by_date_type_and_category(): void
    {
        $membership = FinanceCategory::where('system_code', FinanceCategory::CODE_MEMBERSHIPS)->firstOrFail();
        $utilities = FinanceCategory::where('system_code', 'utilities')->firstOrFail();
        FinancialTransaction::factory()->create([
            'finance_category_id' => $membership->id,
            'transaction_type' => FinanceCategory::TYPE_INCOME,
            'transaction_date' => '2026-08-10',
            'description' => 'Visible August membership',
            'amount' => 7500,
        ]);
        FinancialTransaction::factory()->create([
            'finance_category_id' => $utilities->id,
            'transaction_type' => FinanceCategory::TYPE_EXPENSE,
            'transaction_date' => '2026-07-15',
            'description' => 'Hidden July utility',
            'amount' => 22000,
        ]);

        $this->actingAs($this->admin())
            ->get(route('admin.finance.index', [
                'from_date' => '2026-08-01',
                'to_date' => '2026-08-31',
                'transaction_type' => FinanceCategory::TYPE_INCOME,
                'finance_category_id' => $membership->id,
            ]))
            ->assertOk()
            ->assertSee('Visible August membership')
            ->assertDontSee('Hidden July utility')
            ->assertSee('LKR 7,500.00');
    }

    public function test_completed_order_income_is_idempotent_and_reversible(): void
    {
        $admin = $this->admin();
        $order = $this->order();

        $this->actingAs($admin)
            ->patch(route('admin.orders.update', $order), ['status' => 'completed'])
            ->assertSessionHasNoErrors();

        $transaction = FinancialTransaction::where('source_type', $order->getMorphClass())
            ->where('source_id', $order->id)
            ->firstOrFail();
        $this->assertSame('12500.00', $transaction->amount);
        $this->assertTrue($transaction->is_automatic);
        $this->assertNull($transaction->voided_at);

        $this->actingAs($admin)->patch(route('admin.orders.update', $order), ['status' => 'completed']);
        $this->assertSame(1, FinancialTransaction::where('source_type', $order->getMorphClass())->where('source_id', $order->id)->count());

        $this->actingAs($admin)->patch(route('admin.orders.update', $order), ['status' => 'cancelled']);
        $this->assertNotNull($transaction->fresh()->voided_at);

        $this->actingAs($admin)->patch(route('admin.orders.update', $order), ['status' => 'completed']);
        $this->assertSame($transaction->id, FinancialTransaction::where('source_type', $order->getMorphClass())->where('source_id', $order->id)->firstOrFail()->id);
        $this->assertNull($transaction->fresh()->voided_at);
    }

    public function test_automatic_order_income_cannot_be_changed_from_finance(): void
    {
        $admin = $this->admin();
        $order = $this->order();
        $this->actingAs($admin)->patch(route('admin.orders.update', $order), ['status' => 'completed']);
        $transaction = FinancialTransaction::where('source_id', $order->id)->firstOrFail();
        $category = $transaction->category;

        $this->actingAs($admin)
            ->patch(route('admin.finance.transactions.update', $transaction), $this->transactionPayload($category, [
                'amount' => 1,
                'description' => 'Tampered automatic income',
            ]))
            ->assertSessionHasErrors('transaction');
        $this->assertSame('12500.00', $transaction->fresh()->amount);

        $this->actingAs($admin)
            ->delete(route('admin.finance.transactions.destroy', $transaction))
            ->assertSessionHasErrors('transaction');
        $this->assertNull($transaction->fresh()->voided_at);
    }

    public function test_admin_can_add_and_configure_a_custom_category(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->post(route('admin.finance.categories.store'), [
            'name' => 'Professional Development',
            'transaction_type' => FinanceCategory::TYPE_EXPENSE,
            'is_active' => '1',
        ])->assertSessionHasNoErrors();

        $category = FinanceCategory::where('slug', 'professional-development')->firstOrFail();
        $this->actingAs($admin)->patch(route('admin.finance.categories.update', $category), [
            'name' => 'Staff Development',
            'transaction_type' => FinanceCategory::TYPE_EXPENSE,
        ])->assertSessionHasNoErrors();

        $category->refresh();
        $this->assertSame('staff-development', $category->slug);
        $this->assertFalse($category->is_active);
    }

    public function test_export_route_returns_a_real_xlsx_workbook_with_four_sheets(): void
    {
        $category = FinanceCategory::where('system_code', FinanceCategory::CODE_MEMBERSHIPS)->firstOrFail();
        FinancialTransaction::factory()->create([
            'finance_category_id' => $category->id,
            'transaction_type' => FinanceCategory::TYPE_INCOME,
            'transaction_date' => '2026-08-20',
            'description' => 'Excel membership income',
            'amount' => 7500,
        ]);

        $response = $this->actingAs($this->admin())->get(route('admin.finance.export', ['year' => 2026]));
        $response->assertOk()
            ->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet')
            ->assertDownload('gymravana-finance-report-'.now()->format('Y-m-d').'.xlsx');

        $downloadPath = $response->baseResponse->getFile()->getPathname();
        $this->assertSame('PK', substr(File::get($downloadPath), 0, 2));

        $reader = new Reader;
        $reader->open($downloadPath);
        $sheetNames = [];
        $allValues = [];
        foreach ($reader->getSheetIterator() as $sheet) {
            $sheetNames[] = $sheet->getName();
            foreach ($sheet->getRowIterator() as $row) {
                $allValues = [...$allValues, ...$row->toArray()];
            }
        }
        $reader->close();

        $this->assertSame(['Summary', 'Income', 'Expenses', 'Income by Source'], $sheetNames);
        $this->assertContains('Excel membership income', $allValues);
        File::delete($downloadPath);
    }

    private function admin(): User
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        return $admin;
    }

    private function order(): Order
    {
        return Order::create([
            'order_number' => (string) Str::uuid(),
            'customer_name' => 'Finance Test Customer',
            'guest_email' => 'finance-customer@example.test',
            'phone' => '0771234567',
            'delivery_address' => '12 Finance Road, Colombo',
            'status' => 'pending',
            'total' => 12500,
        ]);
    }

    private function transactionPayload(FinanceCategory $category, array $overrides = []): array
    {
        return array_replace([
            'transaction_type' => $category->transaction_type,
            'finance_category_id' => $category->id,
            'amount' => '5000.00',
            'transaction_date' => now()->toDateString(),
            'description' => 'Finance test transaction',
            'programme_name' => null,
            'supplier_payee' => null,
            'reference_number' => null,
            'notes' => null,
        ], $overrides);
    }
}
