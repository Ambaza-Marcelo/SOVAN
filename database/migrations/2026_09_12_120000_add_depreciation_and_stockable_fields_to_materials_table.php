<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AddDepreciationAndStockableFieldsToMaterialsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('materials', function (Blueprint $table) {
            if (!Schema::hasColumn('materials', 'is_stockable')) {
                $table->boolean('is_stockable')->default(true)->after('status');
            }

            if (!Schema::hasColumn('materials', 'is_amortizable')) {
                // Flag d'actif amortissable (ex: logiciels internes, immobilisations)
                $table->boolean('is_amortizable')->default(false)->after('default_account_id');
            }

            if (!Schema::hasColumn('materials', 'depreciation_base')) {
                $table->decimal('depreciation_base', 15, 2)->nullable()->after('is_amortizable');
            }

            if (!Schema::hasColumn('materials', 'residual_value')) {
                $table->decimal('residual_value', 15, 2)->default(0)->after('depreciation_base');
            }

            if (!Schema::hasColumn('materials', 'service_start_date')) {
                $table->date('service_start_date')->nullable()->after('residual_value');
            }

            if (!Schema::hasColumn('materials', 'duration_years')) {
                $table->unsignedInteger('duration_years')->nullable()->default(3)->after('service_start_date');
            }

            if (!Schema::hasColumn('materials', 'depreciation_expense_account_id')) {
                $table->unsignedBigInteger('depreciation_expense_account_id')->nullable()->after('duration_years');
                $table->foreign('depreciation_expense_account_id')
                    ->references('id')
                    ->on('account_accounts')
                    ->onUpdate('cascade')
                    ->onDelete('set null');
            }

            if (!Schema::hasColumn('materials', 'depreciation_asset_account_id')) {
                $table->unsignedBigInteger('depreciation_asset_account_id')->nullable()->after('depreciation_expense_account_id');
                $table->foreign('depreciation_asset_account_id')
                    ->references('id')
                    ->on('account_accounts')
                    ->onUpdate('cascade')
                    ->onDelete('set null');
            }

            if (!Schema::hasColumn('materials', 'immobilization_account_id')) {
                $table->unsignedBigInteger('immobilization_account_id')->nullable()->after('depreciation_asset_account_id');
                $table->foreign('immobilization_account_id')
                    ->references('id')
                    ->on('account_accounts')
                    ->onUpdate('cascade')
                    ->onDelete('set null');
            }
        });

        // Conversion stricte string -> decimal(15,2)
        $this->convertColumnToDecimal('purchase_price');
        $this->convertColumnToDecimal('cost_price');
        $this->convertColumnToDecimal('selling_price');
        $this->convertColumnToDecimal('quantity');
        $this->convertColumnToDecimal('threshold_quantity');
        $this->convertColumnToDecimal('cump');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('materials', function (Blueprint $table) {
            if (Schema::hasColumn('materials', 'depreciation_expense_account_id')) {
                $table->dropForeign(['depreciation_expense_account_id']);
            }
            if (Schema::hasColumn('materials', 'depreciation_asset_account_id')) {
                $table->dropForeign(['depreciation_asset_account_id']);
            }
            if (Schema::hasColumn('materials', 'immobilization_account_id')) {
                $table->dropForeign(['immobilization_account_id']);
            }

            $cols = [];
            foreach ([
                'is_amortizable',
                'depreciation_base',
                'residual_value',
                'service_start_date',
                'duration_years',
                'depreciation_expense_account_id',
                'depreciation_asset_account_id',
                'immobilization_account_id',
                'is_stockable',
            ] as $col) {
                if (Schema::hasColumn('materials', $col)) {
                    $cols[] = $col;
                }
            }
            if (!empty($cols)) {
                $table->dropColumn($cols);
            }
        });

        DB::statement('ALTER TABLE materials MODIFY purchase_price VARCHAR(255) NULL');
        DB::statement('ALTER TABLE materials MODIFY cost_price VARCHAR(255) NULL');
        DB::statement('ALTER TABLE materials MODIFY selling_price VARCHAR(255) NULL');
        DB::statement('ALTER TABLE materials MODIFY quantity VARCHAR(255) NULL');
        DB::statement('ALTER TABLE materials MODIFY threshold_quantity VARCHAR(255) NULL');
        DB::statement('ALTER TABLE materials MODIFY cump VARCHAR(255) NULL');
    }

    /**
     * Nettoie les valeurs non numériques puis convertit la colonne en DECIMAL(15,2).
     */
    protected function convertColumnToDecimal(string $column): void
    {
        if (!Schema::hasColumn('materials', $column)) {
            return;
        }

        // Skip if already decimal-like
        $type = DB::selectOne("SHOW COLUMNS FROM materials WHERE Field = ?", [$column]);
        if ($type && isset($type->Type) && stripos($type->Type, 'decimal') !== false) {
            return;
        }

        DB::table('materials')
            ->whereNotNull($column)
            ->orderBy('id')
            ->chunkById(200, function ($rows) use ($column) {
                foreach ($rows as $row) {
                    $raw = $row->{$column};
                    if ($raw === null || $raw === '') {
                        continue;
                    }

                    $normalized = str_replace([' ', ','], ['', '.'], (string) $raw);
                    $value = is_numeric($normalized) ? round((float) $normalized, 2) : 0;

                    DB::table('materials')->where('id', $row->id)->update([
                        $column => $value,
                    ]);
                }
            });

        DB::statement("ALTER TABLE materials MODIFY {$column} DECIMAL(15,2) NULL");
    }
}
