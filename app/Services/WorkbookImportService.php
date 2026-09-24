<?php

namespace App\Services;

use App\Models\Category;
use App\Models\Expense;
use App\Models\ImportBatch;
use App\Models\ImportRow;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;

class WorkbookImportService
{
    private const HEADER_ALIASES = [
        'date' => 'expense_date', 'expense date' => 'expense_date',
        'cost type' => 'description', 'details' => 'description', 'description' => 'description',
        'cost' => 'amount', 'amount' => 'amount',
        'paid by' => 'payer', 'payer' => 'payer',
        'category' => 'category', 'payment status' => 'payment_status',
        'status' => 'payment_status', 'payment method' => 'payment_method', 'method' => 'payment_method',
    ];

    public function analyze(UploadedFile $file, User $actor): array
    {
        $hash = hash_file('sha256', $file->getRealPath());
        $batch = ImportBatch::create([
            'source_name' => $file->getClientOriginalName(), 'file_sha256' => $hash,
            'status' => 'analyzed', 'uploaded_by' => $actor->id,
        ]);
        Storage::disk('local')->putFileAs("imports/{$batch->id}", $file, 'source.xlsx');
        $workbook = IOFactory::load($file->getRealPath());
        $sheets = [];
        foreach ($workbook->getWorksheetIterator() as $sheet) {
            [$headerRow, $mapping] = $this->detectHeader($sheet);
            $highestRow = $mapping === [] ? $sheet->getHighestDataRow() : $this->lastMappedRow($sheet, $mapping, $headerRow ?? 1);
            $sheets[] = [
                'name' => $sheet->getTitle(), 'highest_row' => $highestRow,
                'header_row' => $headerRow, 'suggested_mapping' => $mapping,
                'importable' => isset($mapping['description'], $mapping['amount']),
            ];
        }

        return [
            'batch' => $this->batchData($batch), 'sheets' => $sheets,
            'duplicate_file' => ImportBatch::query()->where('file_sha256', $hash)->where('status', 'completed')->exists(),
        ];
    }

    public function preview(ImportBatch $batch, array $config): array
    {
        $this->ensureOpen($batch);
        $path = Storage::disk('local')->path("imports/{$batch->id}/source.xlsx");
        abort_unless(is_file($path), 404, 'The uploaded workbook is no longer available.');
        $workbook = IOFactory::load($path);
        $batch->rows()->delete();
        $preview = [];

        foreach ($config['sheets'] as $selection) {
            $sheet = $workbook->getSheetByName($selection['name']);
            if (! $sheet) {
                throw ValidationException::withMessages(['sheets' => ["Sheet {$selection['name']} does not exist."]]);
            }
            $mapping = $selection['mapping'];
            $headerRow = (int) $selection['header_row'];
            $lastRow = $this->lastMappedRow($sheet, $mapping, $headerRow);
            for ($rowNumber = $headerRow + 1; $rowNumber <= $lastRow; $rowNumber++) {
                $values = $this->mappedRow($sheet, $rowNumber, $mapping);
                if (collect($values)->every(fn (mixed $value): bool => $value === null)) {
                    continue;
                }
                if (empty($values['expense_date']) && in_array(mb_strtolower((string) ($values['description'] ?? '')), ['total', 'subtotal', 'grand total'], true)) {
                    continue;
                }
                $errors = $this->validateRow($values);
                $status = $errors === [] ? ($this->isDuplicate($values) ? 'duplicate' : 'valid') : 'invalid';
                $row = $batch->rows()->create([
                    'sheet_name' => $sheet->getTitle(), 'row_number' => $rowNumber,
                    'raw_values' => $values, 'raw_formulas' => $this->formulas($sheet, $rowNumber, $mapping),
                    'validation_errors' => $errors ?: null, 'status' => $status,
                ]);
                $preview[] = $this->rowData($row);
            }
        }
        $batch->update(['status' => 'previewed']);

        $knownCategories = Category::query()->pluck('name')->map(fn (string $name): string => mb_strtolower($name));
        $unknownCategories = collect($preview)->where('status', 'valid')->pluck('values.category')->filter()->unique()
            ->reject(fn (string $name): bool => $knownCategories->contains(mb_strtolower($name)))->values()->all();

        return ['batch' => $this->batchData($batch), ...$this->previewSummary($preview),
            'unknown_categories' => $unknownCategories, 'rows' => $preview];
    }

    public function commit(ImportBatch $batch, array $config, User $actor, AuditLogger $audit): array
    {
        $this->ensureOpen($batch);
        $categoryMap = $config['category_map'] ?? [];
        $created = DB::transaction(function () use ($batch, $categoryMap, $actor, $audit): int {
            $created = 0;
            foreach ($batch->rows()->where('status', 'valid')->orderBy('id')->get() as $row) {
                $values = $row->raw_values;
                if ($this->isDuplicate($values)) {
                    $row->update(['status' => 'duplicate']);

                    continue;
                }
                $categoryId = $this->resolveCategory($values['category'] ?? null, $categoryMap);
                $expense = Expense::create([
                    'period_month' => substr($values['expense_date'], 0, 7).'-01',
                    'expense_date' => $values['expense_date'], 'description' => $values['description'],
                    'amount' => $values['amount'], 'category_id' => $categoryId,
                    'payment_status' => $this->paymentStatus($values['payment_status'] ?? null),
                    'payment_method' => $values['payment_method'] ?? null,
                    'created_by' => $actor->id, 'updated_by' => $actor->id,
                    'import_batch_id' => $batch->id, 'source_sheet' => $row->sheet_name, 'source_row' => $row->row_number,
                ]);
                if (! empty($values['payer'])) {
                    $expense->payerAllocations()->create(['payer_name' => $values['payer'], 'amount' => $values['amount']]);
                }
                $row->update(['status' => 'imported', 'expense_id' => $expense->id]);
                $created++;
            }
            $batch->update(['status' => 'completed']);
            $audit->record($actor, 'import.completed', $batch, null, ['imported_rows' => $created, 'source_name' => $batch->source_name]);

            return $created;
        });

        return $this->result($batch->fresh(), $created);
    }

    public function result(ImportBatch $batch, ?int $created = null): array
    {
        $sourceRows = $batch->rows()->whereIn('status', ['valid', 'imported', 'duplicate'])->get();
        $expenses = $batch->expenses();
        $sourceTotal = $sourceRows->sum(fn (ImportRow $row): float => (float) ($row->raw_values['amount'] ?? 0));
        $databaseTotal = (float) $expenses->sum('amount');

        return [
            'batch' => $this->batchData($batch), 'imported_count' => $created ?? $expenses->count(),
            'invalid_count' => $batch->rows()->where('status', 'invalid')->count(),
            'duplicate_count' => $batch->rows()->where('status', 'duplicate')->count(),
            'verification' => [
                'source_count' => $sourceRows->count(), 'database_count' => $expenses->count(),
                'source_total' => number_format($sourceTotal, 2, '.', ''),
                'database_total' => number_format($databaseTotal, 2, '.', ''),
                'difference' => number_format($sourceTotal - $databaseTotal, 2, '.', ''),
                'payment_totals' => $expenses->selectRaw("COALESCE(payment_status, 'unspecified') as status, SUM(amount) as total")->groupBy('payment_status')->pluck('total', 'status'),
            ],
        ];
    }

    private function detectHeader(object $sheet): array
    {
        for ($row = 1; $row <= min(20, $sheet->getHighestDataRow()); $row++) {
            $mapping = [];
            $highestColumn = Coordinate::columnIndexFromString($sheet->getHighestDataColumn());
            for ($column = 1; $column <= $highestColumn; $column++) {
                $label = mb_strtolower(trim((string) $sheet->getCell([$column, $row])->getValue()));
                if (isset(self::HEADER_ALIASES[$label])) {
                    $mapping[self::HEADER_ALIASES[$label]] = Coordinate::stringFromColumnIndex($column);
                }
            }
            if (count($mapping) >= 2) {
                return [$row, $mapping];
            }
        }

        return [null, []];
    }

    private function mappedRow(object $sheet, int $row, array $mapping): array
    {
        $values = [];
        foreach ($mapping as $field => $column) {
            $cell = $sheet->getCell("{$column}{$row}");
            $value = $cell->getCalculatedValue();
            if ($field === 'expense_date') {
                $value = $this->dateValue($value);
            } elseif ($field === 'amount') {
                $value = is_numeric($value) ? number_format((float) $value, 2, '.', '') : null;
            } elseif (is_string($value)) {
                $value = trim($value);
            }
            $values[$field] = $value === '' ? null : $value;
        }

        return $values;
    }

    private function lastMappedRow(object $sheet, array $mapping, int $minimum): int
    {
        for ($row = $sheet->getHighestDataRow(); $row > $minimum; $row--) {
            foreach ($mapping as $column) {
                $value = $sheet->getCell("{$column}{$row}")->getCalculatedValue();
                if ($value !== null && trim((string) $value) !== '') {
                    return $row;
                }
            }
        }

        return $minimum;
    }

    private function dateValue(mixed $value): ?string
    {
        try {
            if (is_numeric($value)) {
                return Carbon::instance(ExcelDate::excelToDateTimeObject((float) $value))->toDateString();
            }

            return $value ? Carbon::parse((string) $value)->toDateString() : null;
        } catch (\Throwable) {
            return null;
        }
    }

    private function formulas(object $sheet, int $row, array $mapping): ?array
    {
        $formulas = [];
        foreach ($mapping as $field => $column) {
            $value = $sheet->getCell("{$column}{$row}")->getValue();
            if (is_string($value) && str_starts_with($value, '=')) {
                $formulas[$field] = $value;
            }
        }

        return $formulas ?: null;
    }

    private function validateRow(array $values): array
    {
        $errors = [];
        if (empty($values['description'])) {
            $errors[] = 'Description is required.';
        }
        if (! isset($values['amount']) || ! is_numeric($values['amount']) || (float) $values['amount'] <= 0) {
            $errors[] = 'Amount must be greater than zero.';
        }
        if (empty($values['expense_date'])) {
            $errors[] = 'A valid date is required.';
        }

        return $errors;
    }

    private function isDuplicate(array $values): bool
    {
        return Expense::query()->whereDate('expense_date', $values['expense_date'])
            ->whereRaw('LOWER(description) = ?', [mb_strtolower($values['description'])])
            ->where('amount', $values['amount'])->exists();
    }

    private function resolveCategory(?string $name, array $map): ?int
    {
        if (! $name) {
            return null;
        }
        $existing = Category::query()->whereRaw('LOWER(name) = ?', [mb_strtolower($name)])->first();
        if ($existing) {
            return $existing->id;
        }
        $choice = $map[$name] ?? null;
        if ($choice === 'create') {
            return Category::firstOrCreate(['name' => $name], ['is_active' => true])->id;
        }
        if (is_numeric($choice) && Category::query()->whereKey($choice)->exists()) {
            return (int) $choice;
        }

        return null;
    }

    private function paymentStatus(?string $status): ?string
    {
        $status = $status ? mb_strtolower(trim($status)) : null;

        return in_array($status, ['paid', 'unpaid', 'pending'], true) ? $status : null;
    }

    private function ensureOpen(ImportBatch $batch): void
    {
        if ($batch->status === 'completed') {
            throw ValidationException::withMessages(['batch' => ['This import has already been completed.']]);
        }
    }

    private function rowData(ImportRow $row): array
    {
        return ['id' => $row->id, 'sheet_name' => $row->sheet_name, 'row_number' => $row->row_number,
            'values' => $row->raw_values, 'formulas' => $row->raw_formulas,
            'errors' => $row->validation_errors ?? [], 'status' => $row->status];
    }

    private function previewSummary(array $rows): array
    {
        $collection = collect($rows);

        return ['valid_count' => $collection->where('status', 'valid')->count(),
            'invalid_count' => $collection->where('status', 'invalid')->count(),
            'duplicate_count' => $collection->where('status', 'duplicate')->count(),
            'valid_total' => number_format($collection->where('status', 'valid')->sum(fn (array $row): float => (float) ($row['values']['amount'] ?? 0)), 2, '.', '')];
    }

    private function batchData(ImportBatch $batch): array
    {
        return ['id' => $batch->id, 'source_name' => $batch->source_name, 'status' => $batch->status,
            'created_at' => $batch->created_at?->toIso8601String()];
    }
}
