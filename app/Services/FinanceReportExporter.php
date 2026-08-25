<?php

namespace App\Services;

use App\Models\FinanceCategory;
use App\Models\FinancialTransaction;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Common\Entity\Style\Color;
use OpenSpout\Common\Entity\Style\Style;
use OpenSpout\Writer\XLSX\Writer;
use Throwable;

class FinanceReportExporter
{
    private Style $titleStyle;

    private Style $headerStyle;

    private Style $currencyStyle;

    private Style $dateStyle;

    public function __construct()
    {
        $this->titleStyle = (new Style)
            ->withFontBold(true)
            ->withFontSize(16)
            ->withFontColor(Color::WHITE)
            ->withBackgroundColor(Color::rgb(27, 54, 39));
        $this->headerStyle = (new Style)
            ->withFontBold(true)
            ->withFontColor(Color::WHITE)
            ->withBackgroundColor(Color::rgb(63, 98, 18));
        $this->currencyStyle = (new Style)->withFormat('"LKR" #,##0.00;[Red]-"LKR" #,##0.00');
        $this->dateStyle = (new Style)->withFormat('yyyy-mm-dd');
    }

    public function create(Collection $transactions, array $summary, array $filters): string
    {
        $directory = storage_path('app/private/reports');
        File::ensureDirectoryExists($directory);
        $path = $directory.DIRECTORY_SEPARATOR.'gymravana-finance-'.now()->format('Ymd-His').'-'.bin2hex(random_bytes(4)).'.xlsx';
        $writer = new Writer;

        try {
            $writer->openToFile($path);
            $this->writeSummarySheet($writer, $summary, $filters);
            $this->writeTransactionSheet($writer, 'Income', $transactions->where('transaction_type', FinanceCategory::TYPE_INCOME));
            $this->writeTransactionSheet($writer, 'Expenses', $transactions->where('transaction_type', FinanceCategory::TYPE_EXPENSE));
            $this->writeIncomeBySourceSheet($writer, $summary['income_by_source']);
            $writer->close();
        } catch (Throwable $exception) {
            try {
                $writer->close();
            } catch (Throwable) {
                // The original export exception is more useful.
            }

            File::delete($path);
            throw $exception;
        }

        return $path;
    }

    private function writeSummarySheet(Writer $writer, array $summary, array $filters): void
    {
        $sheet = $writer->getCurrentSheet();
        $sheet->setName('Summary');
        $sheet->setColumnWidth(26, 1);
        $sheet->setColumnWidth(20, 2);

        $writer->addRow(Row::fromValuesWithStyle(['GymRAVANA Finance Report'], $this->titleStyle));
        $writer->addRow(Row::fromValues(['Reporting period', $this->reportingPeriod($filters)]));
        $writer->addRow(Row::fromValues(['Generated at', now()->format('Y-m-d H:i:s')]));
        $writer->addRow(Row::fromValues([]));
        $writer->addRow(Row::fromValuesWithStyle(['Summary', 'Amount (LKR)'], $this->headerStyle));
        $writer->addRow(Row::fromValuesWithStyles(['Total income', $summary['total_income']], [1 => $this->currencyStyle]));
        $writer->addRow(Row::fromValuesWithStyles(['Total expenses', $summary['total_expenses']], [1 => $this->currencyStyle]));
        $writer->addRow(Row::fromValuesWithStyles(['Net income', $summary['net_income']], [1 => $this->currencyStyle]));
        $writer->addRow(Row::fromValues([]));
        $writer->addRow(Row::fromValuesWithStyle(['Income breakdown', 'Amount (LKR)'], $this->headerStyle));

        if ($summary['income_by_source']->isEmpty()) {
            $writer->addRow(Row::fromValues(['No income in this reporting period.']));
        } else {
            foreach ($summary['income_by_source'] as $source => $amount) {
                $writer->addRow(Row::fromValuesWithStyles([$source, $amount], [1 => $this->currencyStyle]));
            }
        }
    }

    private function writeTransactionSheet(Writer $writer, string $name, Collection $transactions): void
    {
        $sheet = $writer->addNewSheetAndMakeItCurrent();
        $sheet->setName($name);
        $sheet->setColumnWidth(14, 1);
        $sheet->setColumnWidth(24, 2);
        $sheet->setColumnWidth(42, 3);
        $sheet->setColumnWidth(18, 4);
        $sheet->setColumnWidth(25, 5, 6);
        $sheet->setColumnWidth(20, 7);
        $sheet->setColumnWidth(12, 8);

        $writer->addRow(Row::fromValuesWithStyle([$name.' Transactions'], $this->titleStyle));
        $writer->addRow(Row::fromValuesWithStyle([
            'Date', 'Category', 'Description', 'Amount (LKR)', 'Programme', 'Supplier / Payee', 'Reference', 'Entry type',
        ], $this->headerStyle));

        if ($transactions->isEmpty()) {
            $writer->addRow(Row::fromValues(['No transactions in this reporting period.']));

            return;
        }

        foreach ($transactions as $transaction) {
            /** @var FinancialTransaction $transaction */
            $writer->addRow(Row::fromValuesWithStyles([
                $transaction->transaction_date->toDateTime(),
                $transaction->category?->name ?? 'Uncategorised',
                $transaction->description,
                (float) $transaction->amount,
                $transaction->programme_name,
                $transaction->supplier_payee,
                $transaction->reference_number,
                $transaction->is_automatic ? 'Automatic' : 'Manual',
            ], [
                0 => $this->dateStyle,
                3 => $this->currencyStyle,
            ]));
        }
    }

    private function writeIncomeBySourceSheet(Writer $writer, Collection $incomeBySource): void
    {
        $sheet = $writer->addNewSheetAndMakeItCurrent();
        $sheet->setName('Income by Source');
        $sheet->setColumnWidth(30, 1);
        $sheet->setColumnWidth(20, 2);

        $writer->addRow(Row::fromValuesWithStyle(['Income by Source'], $this->titleStyle));
        $writer->addRow(Row::fromValuesWithStyle(['Source', 'Amount (LKR)'], $this->headerStyle));

        if ($incomeBySource->isEmpty()) {
            $writer->addRow(Row::fromValues(['No income in this reporting period.']));

            return;
        }

        foreach ($incomeBySource as $source => $amount) {
            $writer->addRow(Row::fromValuesWithStyles([$source, $amount], [1 => $this->currencyStyle]));
        }
    }

    private function reportingPeriod(array $filters): string
    {
        if (! empty($filters['from_date']) || ! empty($filters['to_date'])) {
            return ($filters['from_date'] ?? 'Beginning').' to '.($filters['to_date'] ?? 'Present');
        }

        if (! empty($filters['month'])) {
            $year = $filters['year'] ?? now()->year;

            return now()->setDate((int) $year, (int) $filters['month'], 1)->format('F Y');
        }

        if (! empty($filters['year'])) {
            return (string) $filters['year'];
        }

        return 'All recorded transactions';
    }
}
