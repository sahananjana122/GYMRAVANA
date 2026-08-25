<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <p class="text-xs font-black uppercase tracking-[0.2em] text-lime-300">Administration · Finance</p>
                <h1 class="mt-2 text-2xl font-black">Finance & reports</h1>
                <p class="mt-2 max-w-2xl text-sm text-stone-400">Track real income and expenses in LKR. Completed store orders are recorded automatically; other income can be entered after payment is confirmed.</p>
            </div>
            <a href="{{ route('admin.finance.export', request()->query()) }}" class="inline-flex items-center justify-center rounded-xl bg-lime-400 px-5 py-3 text-sm font-black text-black hover:bg-lime-300">Export filtered .xlsx</a>
        </div>
    </x-slot>

    @if ($errors->any())
        <div class="mb-6 rounded-2xl border border-rose-400/25 bg-rose-400/10 px-5 py-4 text-sm text-rose-100">
            <p class="font-black">Please correct the following:</p>
            <ul class="mt-2 list-disc space-y-1 pl-5">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <section class="rounded-3xl border border-white/10 bg-[#111411] p-5 sm:p-6">
        <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <p class="text-xs font-black uppercase tracking-[0.18em] text-stone-500">Report controls</p>
                <h2 class="mt-1 text-lg font-black">Filter the ledger</h2>
            </div>
            @if (request()->hasAny(['from_date', 'to_date', 'month', 'year', 'transaction_type', 'finance_category_id']))
                <a href="{{ route('admin.finance.index') }}" class="text-sm font-bold text-lime-300 hover:text-lime-200">Clear all filters</a>
            @endif
        </div>

        <form method="GET" action="{{ route('admin.finance.index') }}" class="mt-5 grid gap-4 sm:grid-cols-2 lg:grid-cols-6">
            <label class="text-sm font-bold text-stone-300">From date
                <input type="date" name="from_date" value="{{ $filters['from_date'] ?? '' }}" class="mt-2 w-full rounded-xl border-white/10 bg-black/30 text-stone-100">
            </label>
            <label class="text-sm font-bold text-stone-300">To date
                <input type="date" name="to_date" value="{{ $filters['to_date'] ?? '' }}" class="mt-2 w-full rounded-xl border-white/10 bg-black/30 text-stone-100">
            </label>
            <label class="text-sm font-bold text-stone-300">Month
                <select name="month" class="mt-2 w-full rounded-xl border-white/10 bg-black/30 text-stone-100">
                    <option value="">All months</option>
                    @foreach (range(1, 12) as $month)
                        <option value="{{ $month }}" @selected((int) ($filters['month'] ?? 0) === $month)>{{ now()->setMonth($month)->format('F') }}</option>
                    @endforeach
                </select>
            </label>
            <label class="text-sm font-bold text-stone-300">Year
                <input type="number" name="year" min="2000" max="2100" value="{{ $filters['year'] ?? '' }}" placeholder="{{ now()->year }}" class="mt-2 w-full rounded-xl border-white/10 bg-black/30 text-stone-100">
            </label>
            <label class="text-sm font-bold text-stone-300">Type
                <select name="transaction_type" class="mt-2 w-full rounded-xl border-white/10 bg-black/30 text-stone-100">
                    <option value="">Income & expenses</option>
                    @foreach (\App\Models\FinanceCategory::TYPES as $value => $label)
                        <option value="{{ $value }}" @selected(($filters['transaction_type'] ?? '') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </label>
            <label class="text-sm font-bold text-stone-300">Category
                <select name="finance_category_id" class="mt-2 w-full rounded-xl border-white/10 bg-black/30 text-stone-100">
                    <option value="">All categories</option>
                    @foreach ($categories as $category)
                        <option value="{{ $category->id }}" @selected((int) ($filters['finance_category_id'] ?? 0) === $category->id)>{{ $category->name }}</option>
                    @endforeach
                </select>
            </label>
            <button class="rounded-xl bg-white px-5 py-3 text-sm font-black text-black sm:col-span-2 lg:col-span-6 lg:justify-self-end">Apply filters</button>
        </form>
    </section>

    <section class="mt-6">
        <div class="mb-3 flex items-end justify-between">
            <div>
                <p class="text-xs font-black uppercase tracking-[0.18em] text-lime-300">Selected report</p>
                <h2 class="mt-1 text-xl font-black">Financial position</h2>
            </div>
            <p class="text-xs text-stone-500">Voided records are excluded</p>
        </div>
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            <x-stat-card label="Total income" :value="'LKR '.number_format($summary['total_income'], 2)"/>
            <x-stat-card label="Total expenses" :value="'LKR '.number_format($summary['total_expenses'], 2)"/>
            <x-stat-card label="Net income" :value="'LKR '.number_format($summary['net_income'], 2)"/>
            <x-stat-card label="Product revenue" :value="'LKR '.number_format($summary['product_revenue'], 2)"/>
            <x-stat-card label="Membership revenue" :value="'LKR '.number_format($summary['membership_revenue'], 2)"/>
            <x-stat-card label="Transactions" :value="$transactions->total()"/>
        </div>
    </section>

    <section class="mt-6 rounded-3xl border border-lime-400/15 bg-lime-400/[0.04] p-5 sm:p-6">
        <p class="text-xs font-black uppercase tracking-[0.18em] text-lime-300">{{ now()->format('F Y') }}</p>
        <div class="mt-4 grid gap-5 sm:grid-cols-3">
            <div><p class="text-sm text-stone-400">Current month income</p><p class="mt-1 text-xl font-black">LKR {{ number_format($currentMonth['income'], 2) }}</p></div>
            <div><p class="text-sm text-stone-400">Current month expenses</p><p class="mt-1 text-xl font-black">LKR {{ number_format($currentMonth['expenses'], 2) }}</p></div>
            <div><p class="text-sm text-stone-400">Current month net</p><p class="mt-1 text-xl font-black {{ $currentMonth['net'] < 0 ? 'text-rose-300' : 'text-lime-300' }}">LKR {{ number_format($currentMonth['net'], 2) }}</p></div>
        </div>
    </section>

    <div class="mt-8 grid gap-6 xl:grid-cols-[1.1fr_0.9fr]">
        <section class="rounded-3xl border border-white/10 bg-[#111411] p-5 sm:p-6">
            <p class="text-xs font-black uppercase tracking-[0.18em] text-lime-300">New ledger entry</p>
            <h2 class="mt-2 text-xl font-black">Record confirmed income or expense</h2>
            <p class="mt-2 text-sm text-stone-400">Do not enter completed product orders here—the order workflow records them automatically.</p>

            <form method="POST" action="{{ route('admin.finance.transactions.store') }}" class="mt-6 grid gap-4 sm:grid-cols-2" x-data="{ type: '{{ old('transaction_type', 'expense') }}', category: '{{ old('finance_category_id') }}' }">
                @csrf
                <label class="text-sm font-bold text-stone-300">Transaction type
                    <select name="transaction_type" x-model="type" @change="category = ''" class="mt-2 w-full rounded-xl border-white/10 bg-black/30 text-stone-100" required>
                        @foreach (\App\Models\FinanceCategory::TYPES as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </label>
                <label class="text-sm font-bold text-stone-300">Category
                    <select name="finance_category_id" x-model="category" class="mt-2 w-full rounded-xl border-white/10 bg-black/30 text-stone-100" required>
                        <option value="">Select category</option>
                        @foreach ($categories->where('is_active', true) as $category)
                            <option value="{{ $category->id }}" x-show="type === '{{ $category->transaction_type }}'" :disabled="type !== '{{ $category->transaction_type }}'">{{ $category->name }} · {{ ucfirst($category->transaction_type) }}</option>
                        @endforeach
                    </select>
                </label>
                <label class="text-sm font-bold text-stone-300">Amount (LKR)
                    <input type="number" name="amount" value="{{ old('amount') }}" min="0.01" step="0.01" class="mt-2 w-full rounded-xl border-white/10 bg-black/30 text-stone-100" required>
                </label>
                <label class="text-sm font-bold text-stone-300">Transaction date
                    <input type="date" name="transaction_date" value="{{ old('transaction_date', now()->toDateString()) }}" class="mt-2 w-full rounded-xl border-white/10 bg-black/30 text-stone-100" required>
                </label>
                <label class="text-sm font-bold text-stone-300 sm:col-span-2">Description
                    <input type="text" name="description" value="{{ old('description') }}" maxlength="255" placeholder="What was this payment for?" class="mt-2 w-full rounded-xl border-white/10 bg-black/30 text-stone-100" required>
                </label>
                <label class="text-sm font-bold text-stone-300">Programme / service <span class="font-normal text-stone-500">(optional)</span>
                    <input type="text" name="programme_name" value="{{ old('programme_name') }}" maxlength="160" placeholder="e.g. Evening Yoga" class="mt-2 w-full rounded-xl border-white/10 bg-black/30 text-stone-100">
                </label>
                <label class="text-sm font-bold text-stone-300">Supplier / payee <span class="font-normal text-stone-500">(optional)</span>
                    <input type="text" name="supplier_payee" value="{{ old('supplier_payee') }}" maxlength="160" class="mt-2 w-full rounded-xl border-white/10 bg-black/30 text-stone-100">
                </label>
                <label class="text-sm font-bold text-stone-300">Payment / reference no. <span class="font-normal text-stone-500">(optional)</span>
                    <input type="text" name="reference_number" value="{{ old('reference_number') }}" maxlength="120" class="mt-2 w-full rounded-xl border-white/10 bg-black/30 text-stone-100">
                </label>
                <label class="text-sm font-bold text-stone-300">Notes <span class="font-normal text-stone-500">(optional)</span>
                    <textarea name="notes" rows="2" maxlength="5000" class="mt-2 w-full rounded-xl border-white/10 bg-black/30 text-stone-100">{{ old('notes') }}</textarea>
                </label>
                <button class="rounded-xl bg-lime-400 px-5 py-3 text-sm font-black text-black hover:bg-lime-300 sm:col-span-2">Record transaction</button>
            </form>
        </section>

        <div class="grid gap-6">
            <section class="rounded-3xl border border-white/10 bg-[#111411] p-5 sm:p-6">
                <p class="text-xs font-black uppercase tracking-[0.18em] text-stone-500">Income breakdown</p>
                <h2 class="mt-2 text-lg font-black">By source</h2>
                <div class="mt-5 space-y-4">
                    @forelse ($summary['income_by_source'] as $source => $amount)
                        @php($percentage = $summary['total_income'] > 0 ? min(100, ($amount / $summary['total_income']) * 100) : 0)
                        <div>
                            <div class="flex justify-between gap-4 text-sm"><span class="font-bold text-stone-300">{{ $source }}</span><span class="text-stone-400">LKR {{ number_format($amount, 2) }}</span></div>
                            <div class="mt-2 h-2 overflow-hidden rounded-full bg-white/5"><div class="h-full rounded-full bg-lime-400" style="width: {{ $percentage }}%"></div></div>
                        </div>
                    @empty
                        <p class="rounded-2xl border border-dashed border-white/10 p-5 text-sm text-stone-500">No income matches this report yet.</p>
                    @endforelse
                </div>
            </section>

            <section class="rounded-3xl border border-white/10 bg-[#111411] p-5 sm:p-6">
                <p class="text-xs font-black uppercase tracking-[0.18em] text-stone-500">Programme analysis</p>
                <h2 class="mt-2 text-lg font-black">Income by programme</h2>
                <div class="mt-4 divide-y divide-white/10">
                    @forelse ($summary['income_by_programme'] as $programme => $amount)
                        <div class="flex justify-between gap-4 py-3 text-sm"><span class="text-stone-300">{{ $programme }}</span><span class="font-black">LKR {{ number_format($amount, 2) }}</span></div>
                    @empty
                        <p class="py-4 text-sm text-stone-500">Add a programme name to manual income entries to see this breakdown.</p>
                    @endforelse
                </div>
            </section>
        </div>
    </div>

    <section class="mt-8 rounded-3xl border border-white/10 bg-[#111411] p-5 sm:p-6">
        <p class="text-xs font-black uppercase tracking-[0.18em] text-stone-500">Monthly trend</p>
        <h2 class="mt-2 text-xl font-black">Income, expenses & net</h2>
        <div class="mt-5 overflow-x-auto">
            <table class="w-full min-w-[620px] text-left text-sm">
                <thead class="text-xs uppercase tracking-wider text-stone-500"><tr><th class="pb-3">Month</th><th class="pb-3 text-right">Income</th><th class="pb-3 text-right">Expenses</th><th class="pb-3 text-right">Net</th></tr></thead>
                <tbody class="divide-y divide-white/10">
                    @forelse ($summary['monthly_trend'] as $month)
                        <tr><td class="py-3 font-bold">{{ \Illuminate\Support\Carbon::createFromFormat('Y-m', $month['month'])->format('F Y') }}</td><td class="py-3 text-right text-lime-300">LKR {{ number_format($month['income'], 2) }}</td><td class="py-3 text-right text-rose-300">LKR {{ number_format($month['expenses'], 2) }}</td><td class="py-3 text-right font-black {{ $month['net'] < 0 ? 'text-rose-300' : '' }}">LKR {{ number_format($month['net'], 2) }}</td></tr>
                    @empty
                        <tr><td colspan="4" class="py-6 text-center text-stone-500">Monthly trends will appear after transactions are recorded.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    <section class="mt-8">
        <div class="flex items-end justify-between gap-4">
            <div><p class="text-xs font-black uppercase tracking-[0.18em] text-lime-300">Ledger</p><h2 class="mt-1 text-xl font-black">Transactions</h2></div>
            <p class="text-sm text-stone-500">{{ $transactions->total() }} result{{ $transactions->total() === 1 ? '' : 's' }}</p>
        </div>
        <div class="mt-4 space-y-3">
            @forelse ($transactions as $transaction)
                <details class="group rounded-2xl border border-white/10 bg-[#111411] open:border-lime-400/20">
                    <summary class="grid cursor-pointer list-none gap-3 p-5 sm:grid-cols-[120px_1fr_auto_auto] sm:items-center">
                        <span class="text-sm text-stone-400">{{ $transaction->transaction_date->format('d M Y') }}</span>
                        <span><span class="block font-black">{{ $transaction->description }}</span><span class="mt-1 block text-xs text-stone-500">{{ $transaction->category?->name }}{{ $transaction->reference_number ? ' · Ref '.$transaction->reference_number : '' }}</span></span>
                        <span class="justify-self-start rounded-full px-3 py-1 text-xs font-black uppercase {{ $transaction->transaction_type === 'income' ? 'bg-lime-400/10 text-lime-300' : 'bg-rose-400/10 text-rose-300' }}">{{ $transaction->transaction_type }}</span>
                        <span class="text-lg font-black {{ $transaction->transaction_type === 'income' ? 'text-lime-300' : 'text-rose-300' }}">{{ $transaction->transaction_type === 'income' ? '+' : '-' }} LKR {{ number_format((float) $transaction->amount, 2) }}</span>
                    </summary>
                    <div class="border-t border-white/10 p-5">
                        <div class="grid gap-3 text-sm sm:grid-cols-2 lg:grid-cols-4">
                            <p><span class="block text-xs uppercase text-stone-500">Entry</span>{{ $transaction->is_automatic ? 'Automatic order sync' : 'Manual' }}</p>
                            <p><span class="block text-xs uppercase text-stone-500">Programme</span>{{ $transaction->programme_name ?: '—' }}</p>
                            <p><span class="block text-xs uppercase text-stone-500">Supplier / payee</span>{{ $transaction->supplier_payee ?: '—' }}</p>
                            <p><span class="block text-xs uppercase text-stone-500">Recorded by</span>{{ $transaction->creator?->name ?: 'System' }}</p>
                        </div>
                        @if ($transaction->notes)<p class="mt-4 rounded-xl bg-black/20 p-4 text-sm text-stone-400">{{ $transaction->notes }}</p>@endif

                        @if ($transaction->is_automatic)
                            <p class="mt-4 rounded-xl border border-sky-400/15 bg-sky-400/5 p-4 text-sm text-sky-200">This entry is controlled by its store order. Change the order status to correct or reverse it.</p>
                        @else
                            <form method="POST" action="{{ route('admin.finance.transactions.update', $transaction) }}" class="mt-5 grid gap-3 sm:grid-cols-2 lg:grid-cols-4" x-data="{ editType: '{{ $transaction->transaction_type }}', editCategory: '{{ $transaction->finance_category_id }}' }">
                                @csrf @method('PATCH')
                                <select name="transaction_type" x-model="editType" @change="editCategory = ''" class="rounded-xl border-white/10 bg-black/30 text-sm text-stone-100" required>@foreach (\App\Models\FinanceCategory::TYPES as $value => $label)<option value="{{ $value }}">{{ $label }}</option>@endforeach</select>
                                <select name="finance_category_id" x-model="editCategory" class="rounded-xl border-white/10 bg-black/30 text-sm text-stone-100" required><option value="">Select category</option>@foreach ($categories->filter(fn ($category) => $category->is_active || $category->id === $transaction->finance_category_id) as $category)<option value="{{ $category->id }}" x-show="editType === '{{ $category->transaction_type }}'" :disabled="editType !== '{{ $category->transaction_type }}'">{{ $category->name }}</option>@endforeach</select>
                                <input type="number" name="amount" value="{{ $transaction->amount }}" min="0.01" step="0.01" class="rounded-xl border-white/10 bg-black/30 text-sm text-stone-100" required>
                                <input type="date" name="transaction_date" value="{{ $transaction->transaction_date->toDateString() }}" class="rounded-xl border-white/10 bg-black/30 text-sm text-stone-100" required>
                                <input type="text" name="description" value="{{ $transaction->description }}" maxlength="255" class="rounded-xl border-white/10 bg-black/30 text-sm text-stone-100 lg:col-span-2" required>
                                <input type="text" name="programme_name" value="{{ $transaction->programme_name }}" maxlength="160" placeholder="Programme / service" class="rounded-xl border-white/10 bg-black/30 text-sm text-stone-100">
                                <input type="text" name="supplier_payee" value="{{ $transaction->supplier_payee }}" maxlength="160" placeholder="Supplier / payee" class="rounded-xl border-white/10 bg-black/30 text-sm text-stone-100">
                                <input type="text" name="reference_number" value="{{ $transaction->reference_number }}" maxlength="120" placeholder="Reference" class="rounded-xl border-white/10 bg-black/30 text-sm text-stone-100">
                                <textarea name="notes" rows="2" maxlength="5000" placeholder="Notes" class="rounded-xl border-white/10 bg-black/30 text-sm text-stone-100 lg:col-span-2">{{ $transaction->notes }}</textarea>
                                <button class="rounded-xl bg-white px-4 py-2 text-sm font-black text-black">Save changes</button>
                            </form>
                            <form method="POST" action="{{ route('admin.finance.transactions.destroy', $transaction) }}" class="mt-3" onsubmit="return confirm('Remove this manual entry from financial reports?')">
                                @csrf @method('DELETE')
                                <button class="text-sm font-bold text-rose-300 hover:text-rose-200">Remove from reports</button>
                            </form>
                        @endif
                    </div>
                </details>
            @empty
                <div class="rounded-3xl border border-dashed border-white/10 p-10 text-center"><p class="font-black">No matching transactions</p><p class="mt-2 text-sm text-stone-500">Change the report filters or record the first confirmed transaction.</p></div>
            @endforelse
        </div>
        <div class="mt-6">{{ $transactions->links() }}</div>
    </section>

    <section class="mt-8 rounded-3xl border border-white/10 bg-[#111411] p-5 sm:p-6">
        <details>
            <summary class="cursor-pointer list-none"><p class="text-xs font-black uppercase tracking-[0.18em] text-stone-500">Configuration</p><div class="mt-2 flex items-center justify-between"><h2 class="text-xl font-black">Finance categories</h2><span class="text-sm font-bold text-lime-300">Open manager</span></div></summary>
            <div class="mt-6 border-t border-white/10 pt-6">
                <form method="POST" action="{{ route('admin.finance.categories.store') }}" class="grid gap-3 sm:grid-cols-[1fr_180px_auto_auto] sm:items-end">
                    @csrf
                    <label class="text-sm font-bold text-stone-300">New category name<input type="text" name="name" maxlength="100" class="mt-2 w-full rounded-xl border-white/10 bg-black/30 text-stone-100" required></label>
                    <label class="text-sm font-bold text-stone-300">Type<select name="transaction_type" class="mt-2 w-full rounded-xl border-white/10 bg-black/30 text-stone-100" required>@foreach (\App\Models\FinanceCategory::TYPES as $value => $label)<option value="{{ $value }}">{{ $label }}</option>@endforeach</select></label>
                    <label class="flex items-center gap-2 rounded-xl border border-white/10 px-4 py-3 text-sm"><input type="checkbox" name="is_active" value="1" checked class="rounded border-white/20 bg-black/30 text-lime-400"> Active</label>
                    <button class="rounded-xl bg-lime-400 px-5 py-3 text-sm font-black text-black">Add category</button>
                </form>

                <div class="mt-6 grid gap-3 md:grid-cols-2">
                    @foreach ($categories as $category)
                        <form method="POST" action="{{ route('admin.finance.categories.update', $category) }}" class="grid gap-3 rounded-2xl border border-white/10 p-4 sm:grid-cols-[1fr_130px]">
                            @csrf @method('PATCH')
                            <input type="text" name="name" value="{{ $category->name }}" maxlength="100" class="rounded-xl border-white/10 bg-black/30 text-sm text-stone-100" required>
                            <select name="transaction_type" class="rounded-xl border-white/10 bg-black/30 text-sm text-stone-100" {{ $category->system_code || $category->transactions_count ? 'disabled' : '' }}>@foreach (\App\Models\FinanceCategory::TYPES as $value => $label)<option value="{{ $value }}" @selected($category->transaction_type === $value)>{{ $label }}</option>@endforeach</select>
                            @if ($category->system_code || $category->transactions_count)<input type="hidden" name="transaction_type" value="{{ $category->transaction_type }}">@endif
                            <label class="flex items-center gap-2 text-sm text-stone-300"><input type="checkbox" name="is_active" value="1" @checked($category->is_active) class="rounded border-white/20 bg-black/30 text-lime-400"> Active @if ($category->system_code)<span class="text-xs text-stone-600">· system</span>@endif</label>
                            <button class="justify-self-start text-sm font-black text-lime-300 sm:justify-self-end">Save</button>
                        </form>
                    @endforeach
                </div>
            </div>
        </details>
    </section>
</x-app-layout>
