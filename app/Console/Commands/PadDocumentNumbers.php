<?php

namespace App\Console\Commands;

use App\Support\Accounting\DocumentSequence;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class PadDocumentNumbers extends Command
{
    protected $signature = 'accounting:pad-document-numbers {--force : Apply the changes (without it the command only lists them)}';

    protected $description = 'Rewrite old-style document numbers (INV/2026/1) into the padded format (INV/2026/00001)';

    /**
     * Tables and columns that store a document number or quote one.
     *
     * @var array<string, array<int, string>>
     */
    protected const COLUMNS = [
        'accounts_account_moves'      => ['name', 'payment_reference', 'reference'],
        'accounts_account_payments'   => ['name', 'memo'],
        'accounts_account_move_lines' => ['name'],
    ];

    public function handle(): int
    {
        $map = $this->numberMap();

        if ($map === []) {
            $this->info('Every document number already has the padded format.');

            return self::SUCCESS;
        }

        foreach ($map as $old => $new) {
            $this->line("{$old}  ->  {$new}");
        }

        if (! $this->option('force')) {
            $this->warn(count($map).' number(s) would change. Run again with --force to apply them.');

            return self::SUCCESS;
        }

        DB::transaction(function () use ($map): void {
            foreach (self::COLUMNS as $table => $columns) {
                foreach ($columns as $column) {
                    $this->rewrite($table, $column, $map);
                }
            }
        });

        $this->info(count($map).' number(s) rewritten.');

        return self::SUCCESS;
    }

    /**
     * Old number => padded number, for every move and payment whose last segment is shorter than the padding.
     * A number whose padded form is already in use is reported and left as it is.
     *
     * @return array<string, string>
     */
    protected function numberMap(): array
    {
        $map = [];
        $taken = [];

        foreach (['accounts_account_moves', 'accounts_account_payments'] as $table) {
            if (! Schema::hasTable($table)) {
                continue;
            }

            foreach (DB::table($table)->whereNotNull('name')->pluck('name') as $name) {
                $taken[$name] = true;
            }
        }

        foreach (array_keys($taken) as $name) {
            if (preg_match('#^([A-Z]+/\d{4})/(\d{1,'.(DocumentSequence::PAD - 1).'})$#', $name, $match) !== 1) {
                continue;
            }

            $padded = $match[1].'/'.str_pad($match[2], DocumentSequence::PAD, '0', STR_PAD_LEFT);

            if (isset($taken[$padded])) {
                $this->warn("{$name} kept: {$padded} is already used.");

                continue;
            }

            $map[$name] = $padded;
        }

        ksort($map);

        return $map;
    }

    /**
     * @param  array<string, string>  $map
     */
    protected function rewrite(string $table, string $column, array $map): void
    {
        if (! Schema::hasTable($table) || ! Schema::hasColumn($table, $column)) {
            return;
        }

        foreach ($map as $old => $new) {
            DB::table($table)
                ->where($column, 'like', '%'.$old.'%')
                ->get(['id', $column])
                ->each(function (object $row) use ($table, $column, $old, $new): void {
                    $value = preg_replace('#(?<![A-Za-z0-9/])'.preg_quote($old, '#').'(?!\d)#', $new, (string) $row->{$column});

                    if ($value !== $row->{$column}) {
                        DB::table($table)->where('id', $row->id)->update([$column => $value]);
                    }
                });
        }
    }
}
