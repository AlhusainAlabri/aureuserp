<?php

use App\Support\Performance\RecommendedIndexCatalog;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        foreach (RecommendedIndexCatalog::definitions() as $definition) {
            if (! Schema::hasTable($definition['table'])) {
                continue;
            }

            if (RecommendedIndexCatalog::indexExists($definition['table'], $definition['name'])) {
                continue;
            }

            Schema::table($definition['table'], function (Blueprint $table) use ($definition): void {
                $table->index($definition['columns'], $definition['name']);
            });
        }
    }

    public function down(): void
    {
        foreach (RecommendedIndexCatalog::definitions() as $definition) {
            if (! Schema::hasTable($definition['table'])) {
                continue;
            }

            if (! RecommendedIndexCatalog::indexExists($definition['table'], $definition['name'])) {
                continue;
            }

            Schema::table($definition['table'], function (Blueprint $table) use ($definition): void {
                $table->dropIndex($definition['name']);
            });
        }
    }
};
