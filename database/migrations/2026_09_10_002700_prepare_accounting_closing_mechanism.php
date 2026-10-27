<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Prépare le schéma de clôture comptable automatique :
 * - exercices fiscaux (account_exercises)
 * - périodes mensuelles (account_periods)
 * - journal des clôtures (account_closing_runs)
 * - comptes de résultat sur account_settings
 */
class PrepareAccountingClosingMechanism extends Migration
{
    public function up()
    {
        Schema::table('account_exercises', function (Blueprint $table) {
            if (!Schema::hasColumn('account_exercises', 'code')) {
                $table->string('code', 30)->nullable()->after('id');
                $table->string('name')->nullable()->after('code');
                $table->string('fiscal_year', 10)->nullable()->after('name');
                $table->date('date_start')->nullable()->after('fiscal_year');
                $table->date('date_end')->nullable()->after('date_start');
                // 0=brouillon, 1=ouvert, 2=périodes verrouillables, 3=clôturé
                $table->tinyInteger('status')->default(0)->after('date_end');
                $table->boolean('is_current')->default(false)->after('status');
                $table->unsignedBigInteger('profit_account_id')->nullable()->after('is_current');
                $table->unsignedBigInteger('loss_account_id')->nullable()->after('profit_account_id');
                $table->unsignedBigInteger('retained_earnings_account_id')->nullable()->after('loss_account_id');
                $table->text('description')->nullable()->after('retained_earnings_account_id');
                $table->string('created_by')->nullable()->after('description');
                $table->string('closed_by')->nullable()->after('created_by');
                $table->timestamp('closed_at')->nullable()->after('closed_by');
                $table->string('reopened_by')->nullable()->after('closed_at');
                $table->timestamp('reopened_at')->nullable()->after('reopened_by');
            }
        });

        if (!Schema::hasTable('account_periods')) {
            Schema::create('account_periods', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('exercise_id');
                $table->unsignedTinyInteger('period_no'); // 1..12
                $table->string('name', 50);
                $table->date('date_start');
                $table->date('date_end');
                // 0=ouvert, 1=verrouillé, 2=clôturé
                $table->tinyInteger('status')->default(0);
                $table->string('locked_by')->nullable();
                $table->timestamp('locked_at')->nullable();
                $table->timestamps();

                $table->foreign('exercise_id')
                    ->references('id')->on('account_exercises')
                    ->onDelete('cascade');
                $table->unique(['exercise_id', 'period_no']);
            });
        }

        if (!Schema::hasTable('account_closing_runs')) {
            Schema::create('account_closing_runs', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('exercise_id');
                $table->unsignedBigInteger('period_id')->nullable();
                // period_lock | year_close | year_reopen | opening_balance
                $table->string('type', 40);
                // 0=en cours, 1=ok, -1=échec
                $table->tinyInteger('status')->default(0);
                $table->unsignedBigInteger('journal_entry_id')->nullable();
                $table->string('document_no')->nullable();
                $table->text('notes')->nullable();
                $table->json('snapshot')->nullable();
                $table->string('created_by')->nullable();
                $table->timestamps();

                $table->foreign('exercise_id')
                    ->references('id')->on('account_exercises')
                    ->onDelete('cascade');
            });
        }

        Schema::table('account_settings', function (Blueprint $table) {
            if (!Schema::hasColumn('account_settings', 'profit_account_id')) {
                $table->unsignedBigInteger('profit_account_id')->nullable()->after('exercise_id');
                $table->unsignedBigInteger('loss_account_id')->nullable()->after('profit_account_id');
                $table->unsignedBigInteger('retained_earnings_account_id')->nullable()->after('loss_account_id');
            }
        });

        if (Schema::hasTable('account_journal_entry_details')
            && !Schema::hasColumn('account_journal_entry_details', 'period_id')) {
            Schema::table('account_journal_entry_details', function (Blueprint $table) {
                $table->unsignedBigInteger('period_id')->nullable()->after('exercise_id');
            });
        }
    }

    public function down()
    {
        if (Schema::hasTable('account_journal_entry_details')
            && Schema::hasColumn('account_journal_entry_details', 'period_id')) {
            Schema::table('account_journal_entry_details', function (Blueprint $table) {
                $table->dropColumn('period_id');
            });
        }

        Schema::table('account_settings', function (Blueprint $table) {
            if (Schema::hasColumn('account_settings', 'profit_account_id')) {
                $table->dropColumn(['profit_account_id', 'loss_account_id', 'retained_earnings_account_id']);
            }
        });

        Schema::dropIfExists('account_closing_runs');
        Schema::dropIfExists('account_periods');

        Schema::table('account_exercises', function (Blueprint $table) {
            $columns = [
                'code', 'name', 'fiscal_year', 'date_start', 'date_end', 'status', 'is_current',
                'profit_account_id', 'loss_account_id', 'retained_earnings_account_id',
                'description', 'created_by', 'closed_by', 'closed_at', 'reopened_by', 'reopened_at',
            ];
            foreach ($columns as $column) {
                if (Schema::hasColumn('account_exercises', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
}
