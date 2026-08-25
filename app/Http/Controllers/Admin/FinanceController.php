<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\FinanceCategoryRequest;
use App\Http\Requests\Admin\FinanceFilterRequest;
use App\Http\Requests\Admin\FinanceTransactionRequest;
use App\Models\FinanceCategory;
use App\Models\FinancialTransaction;
use App\Services\FinanceReportExporter;
use App\Services\FinanceReportService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class FinanceController extends Controller
{
    public function index(FinanceFilterRequest $request, FinanceReportService $reports): View
    {
        $filters = $request->validated();
        $query = $reports->query($filters);
        $reportTransactions = (clone $query)->get();

        return view('admin.finance.index', [
            'categories' => FinanceCategory::query()->withCount('transactions')->orderBy('transaction_type')->orderBy('name')->get(),
            'filters' => $filters,
            'summary' => $reports->summary($reportTransactions),
            'currentMonth' => $reports->currentMonthSummary(),
            'transactions' => $query->paginate(20)->withQueryString(),
        ]);
    }

    public function storeTransaction(FinanceTransactionRequest $request): RedirectResponse
    {
        $data = $request->validated();

        FinancialTransaction::create($data + [
            'created_by' => $request->user()->id,
            'is_automatic' => false,
        ]);

        return back()->with('status', 'Financial transaction recorded.');
    }

    public function updateTransaction(FinanceTransactionRequest $request, FinancialTransaction $financialTransaction): RedirectResponse
    {
        $this->ensureManual($financialTransaction);
        $financialTransaction->update($request->validated());

        return back()->with('status', 'Financial transaction updated.');
    }

    public function destroyTransaction(FinancialTransaction $financialTransaction): RedirectResponse
    {
        $this->ensureManual($financialTransaction);
        $financialTransaction->update(['voided_at' => now()]);

        return back()->with('status', 'Financial transaction removed from reports.');
    }

    public function storeCategory(FinanceCategoryRequest $request): RedirectResponse
    {
        $data = $request->validated();
        FinanceCategory::create([
            'name' => $data['name'],
            'slug' => $this->uniqueSlug($data['name']),
            'transaction_type' => $data['transaction_type'],
            'is_active' => $request->boolean('is_active'),
        ]);

        return back()->with('status', 'Finance category created.');
    }

    public function updateCategory(FinanceCategoryRequest $request, FinanceCategory $financeCategory): RedirectResponse
    {
        $data = $request->validated();
        $financeCategory->update([
            'name' => $data['name'],
            'slug' => $this->uniqueSlug($data['name'], $financeCategory->id),
            'transaction_type' => $financeCategory->system_code || $financeCategory->transactions()->exists()
                ? $financeCategory->transaction_type
                : $data['transaction_type'],
            'is_active' => $request->boolean('is_active'),
        ]);

        return back()->with('status', 'Finance category updated.');
    }

    public function export(
        FinanceFilterRequest $request,
        FinanceReportService $reports,
        FinanceReportExporter $exporter,
    ): BinaryFileResponse {
        $filters = $request->validated();
        $transactions = $reports->query($filters)->get();
        $path = $exporter->create($transactions, $reports->summary($transactions), $filters);

        return response()->download(
            $path,
            'gymravana-finance-report-'.now()->format('Y-m-d').'.xlsx',
            ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'],
        )->deleteFileAfterSend(true);
    }

    private function ensureManual(FinancialTransaction $transaction): void
    {
        if ($transaction->is_automatic) {
            throw ValidationException::withMessages([
                'transaction' => 'Automatic order income must be changed through the related order status.',
            ]);
        }
    }

    private function uniqueSlug(string $name, ?int $ignoreId = null): string
    {
        $base = Str::slug($name) ?: 'finance-category';
        $slug = $base;
        $suffix = 2;

        while (FinanceCategory::query()
            ->where('slug', $slug)
            ->when($ignoreId, fn ($query) => $query->whereKeyNot($ignoreId))
            ->exists()) {
            $slug = $base.'-'.$suffix++;
        }

        return $slug;
    }
}
