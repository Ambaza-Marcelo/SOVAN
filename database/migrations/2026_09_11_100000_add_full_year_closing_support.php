<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Clôture d'exercice en 4 étapes (amortissements → résultat → RAN → verrouillage).
 * Conserve le statut entier existant (périodes) via closing_status dédié.
 */
class AddFullYearClosingSupport extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('account_settings', function (Blueprint $table) {
            if (!Schema::hasColumn('account_settings', 'result_account_id')) {
                $table->unsignedBigInteger('result_account_id')->nullable()->after('retained_earnings_account_id');
            }
            if (!Schema::hasColumn('account_settings', 'ran_profit_account_id')) {
                $table->unsignedBigInteger('ran_profit_account_id')->nullable()->after('result_account_id');
            }
            if (!Schema::hasColumn('account_settings', 'ran_loss_account_id')) {
                $table->unsignedBigInteger('ran_loss_account_id')->nullable()->after('ran_profit_account_id');
            }
        });

        Schema::table('account_exercises', function (Blueprint $table) {
            if (!Schema::hasColumn('account_exercises', 'closing_status')) {
                $table->string('closing_status', 40)->default('open')->after('status');
            }
            if (!Schema::hasColumn('account_exercises', 'label')) {
                $table->string('label')->nullable()->after('name');
            }
            if (!Schema::hasColumn('account_exercises', 'posting_date')) {
                $table->date('posting_date')->nullable();
            }
            if (!Schema::hasColumn('account_exercises', 'document_no')) {
                $table->string('document_no')->nullable();
            }
            if (!Schema::hasColumn('account_exercises', 'depreciation_total')) {
                $table->decimal('depreciation_total', 15, 2)->default(0);
            }
            if (!Schema::hasColumn('account_exercises', 'depreciation_lines')) {
                $table->unsignedInteger('depreciation_lines')->default(0);
            }
            if (!Schema::hasColumn('account_exercises', 'result_total')) {
                $table->decimal('result_total', 15, 2)->default(0);
            }
            if (!Schema::hasColumn('account_exercises', 'result_lines')) {
                $table->unsignedInteger('result_lines')->default(0);
            }
            if (!Schema::hasColumn('account_exercises', 'result_document_no')) {
                $table->string('result_document_no')->nullable();
            }
            if (!Schema::hasColumn('account_exercises', 'ran_total')) {
                $table->decimal('ran_total', 15, 2)->default(0);
            }
            if (!Schema::hasColumn('account_exercises', 'ran_document_no')) {
                $table->string('ran_document_no')->nullable();
            }
            if (!Schema::hasColumn('account_exercises', 'result_is_profit')) {
                $table->boolean('result_is_profit')->nullable();
            }
            if (!Schema::hasColumn('account_exercises', 'notes')) {
                $table->text('notes')->nullable();
            }
            if (!Schema::hasColumn('account_exercises', 'locked_at')) {
                $table->timestamp('locked_at')->nullable();
            }
            if (!Schema::hasColumn('account_exercises', 'locked_by')) {
                $table->string('locked_by')->nullable();
            }
            if (!Schema::hasColumn('account_exercises', 'next_fiscal_year')) {
                $table->string('next_fiscal_year', 10)->nullable();
            }
        });

        if (!Schema::hasTable('account_exercise_details')) {
            Schema::create('account_exercise_details', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('exercise_id')->nullable()->index();
                $table->string('step', 40)->nullable()->index();
                $table->unsignedBigInteger('account_id')->nullable()->index();
                $table->string('document_no')->nullable();
                $table->string('libelle')->nullable();
                $table->decimal('debit', 15, 2)->default(0);
                $table->decimal('credit', 15, 2)->default(0);
                $table->string('fiscal_year', 10)->nullable();
                $table->timestamps();
            });
        } else {
            Schema::table('account_exercise_details', function (Blueprint $table) {
                if (!Schema::hasColumn('account_exercise_details', 'exercise_id')) {
                    $table->unsignedBigInteger('exercise_id')->nullable()->index();
                }
                if (!Schema::hasColumn('account_exercise_details', 'step')) {
                    $table->string('step', 40)->nullable()->index();
                }
                if (!Schema::hasColumn('account_exercise_details', 'account_id')) {
                    $table->unsignedBigInteger('account_id')->nullable()->index();
                }
                if (!Schema::hasColumn('account_exercise_details', 'document_no')) {
                    $table->string('document_no')->nullable();
                }
                if (!Schema::hasColumn('account_exercise_details', 'libelle')) {
                    $table->string('libelle')->nullable();
                }
                if (!Schema::hasColumn('account_exercise_details', 'debit')) {
                    $table->decimal('debit', 15, 2)->default(0);
                }
                if (!Schema::hasColumn('account_exercise_details', 'credit')) {
                    $table->decimal('credit', 15, 2)->default(0);
                }
                if (!Schema::hasColumn('account_exercise_details', 'fiscal_year')) {
                    $table->string('fiscal_year', 10)->nullable();
                }
            });
        }

        if (!Schema::hasTable('material_depreciation_postings')) {
            Schema::create('material_depreciation_postings', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('material_id');
                $table->unsignedSmallInteger('fiscal_year');
                $table->unsignedSmallInteger('schedule_year');
                $table->decimal('amount', 15, 2);
                $table->date('posting_date');
                $table->string('document_no')->nullable();
                $table->unsignedBigInteger('journal_entry_detail_id')->nullable();
                $table->string('status')->default('draft');
                $table->string('posted_by')->nullable();
                $table->timestamp('posted_at')->nullable();
                $table->timestamps();

                $table->unique(['material_id', 'fiscal_year', 'schedule_year'], 'mat_dep_post_unique');
                $table->foreign('material_id')->references('id')->on('materials')
                    ->onUpdate('cascade')->onDelete('cascade');
            });
        }

        // Mappe profit / RAN existants vers les nouveaux champs
        try {
            DB::table('account_settings')->whereNull('result_account_id')->whereNotNull('profit_account_id')
                ->update(['result_account_id' => DB::raw('profit_account_id')]);
            DB::table('account_settings')->whereNull('ran_profit_account_id')->whereNotNull('retained_earnings_account_id')
                ->update(['ran_profit_account_id' => DB::raw('retained_earnings_account_id')]);
        } catch (\Throwable $e) {
            // ignore
        }

        try {
            DB::table('account_exercises')
                ->whereNull('label')
                ->whereNotNull('name')
                ->update(['label' => DB::raw('name')]);

            DB::table('account_exercises')
                ->where(function ($q) {
                    $q->whereNull('closing_status')->orWhere('closing_status', '');
                })
                ->update(['closing_status' => 'open']);

            // Exercices déjà clôturés (statut entier) → verrouillés côté clôture 4 étapes
            DB::table('account_exercises')
                ->where('status', 3)
                ->whereNull('locked_at')
                ->update([
                    'closing_status' => 'locked',
                    'locked_at' => DB::raw('COALESCE(closed_at, NOW())'),
                ]);
        } catch (\Throwable $e) {
            // ignore
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('account_settings', function (Blueprint $table) {
            foreach (['result_account_id', 'ran_profit_account_id', 'ran_loss_account_id'] as $col) {
                if (Schema::hasColumn('account_settings', $col)) {
                    $table->dropColumn($col);
                }
            }
        });

        Schema::table('account_exercises', function (Blueprint $table) {
            $cols = [
                'closing_status', 'label', 'posting_date', 'document_no',
                'depreciation_total', 'depreciation_lines',
                'result_total', 'result_lines', 'result_document_no',
                'ran_total', 'ran_document_no', 'result_is_profit',
                'notes', 'locked_at', 'locked_by', 'next_fiscal_year',
            ];
            $toDrop = [];
            foreach ($cols as $col) {
                if (Schema::hasColumn('account_exercises', $col)) {
                    $toDrop[] = $col;
                }
            }
            if (!empty($toDrop)) {
                $table->dropColumn($toDrop);
            }
        });

        Schema::dropIfExists('material_depreciation_postings');
    }
}
