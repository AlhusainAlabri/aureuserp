<?php

namespace App\Console\Commands;

use App\Support\Performance\RecommendedIndexCatalog;
use Illuminate\Console\Command;

class PerformanceAnalyzeIndexesCommand extends Command
{
    protected $signature = 'performance:analyze-indexes
                            {--missing-only : Only show missing recommended indexes}';

    protected $description = 'Audit recommended database indexes for common ERP hot paths';

    public function handle(): int
    {
        $definitions = $this->option('missing-only')
            ? RecommendedIndexCatalog::missing()
            : RecommendedIndexCatalog::audit();

        if ($definitions === []) {
            $this->info('No recommended indexes to audit (tables may not be installed yet).');

            return self::SUCCESS;
        }

        $this->table(
            ['Priority', 'Table', 'Index', 'Columns', 'Status', 'Reason'],
            collect($definitions)->map(fn (array $definition): array => [
                $definition['priority'],
                $definition['table'],
                $definition['name'],
                implode(', ', $definition['columns']),
                $definition['present'] ? 'present' : 'MISSING',
                $definition['reason'],
            ])->all(),
        );

        $missingCount = collect($definitions)->where('present', false)->count();

        if ($missingCount > 0) {
            $this->newLine();
            $this->warn("{$missingCount} recommended index(es) are missing.");
            $this->line('Run `php artisan migrate` to apply database/migrations/*add_performance_indexes*.php');
        } else {
            $this->info('All recommended indexes are present.');
        }

        return self::SUCCESS;
    }
}
