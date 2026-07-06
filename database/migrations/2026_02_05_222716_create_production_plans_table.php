<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateProductionPlansTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('productions_plans', function (Blueprint $table) {
            $table->id();
            $table->string('prodution_plan_number')->nullable(true);
            $table->unique('prodution_plan_number');
            $table->timestamp('production_plan_date')->default(DB::raw('CURRENT_TIMESTAMP'));
            $table->timestamp('start_date')->nullable(true);;
            $table->timestamp('end_date')->nullable(true);;
            $table->string('production_signature')->nullable(true);
            $table->string('status')->default(0);
            $table->string('flag1')->default(0);
            $table->string('flag2')->default(0);
            $table->text('description')->nullable(true);
            $table->string('created_by')->nullable(true);
            $table->string('validated_by')->nullable(true);
            $table->string('confirmed_by')->nullable(true);
            $table->string('approuved_by')->nullable(true);
            $table->string('reseted_by')->nullable(true);
            $table->string('rejected_by')->nullable(true);
            $table->text('rejected_motif')->nullable(true);
            $table->string('cancelled_by')->nullable(true);
            $table->string('updated_by')->nullable(true);
            $table->string('work_center')->nullable(true);
            $table->string('operation_sequence')->nullable(true);
            $table->text('routing_notes')->nullable(true);
            $table->double('estimated_labor_cost')->default(0);
            $table->double('estimated_material_cost')->default(0);
            $table->double('estimated_overhead_cost')->default(0);
            $table->double('total_estimated_cost')->default(0);
            $table->timestamp('planned_start_time')->nullable(true);
            $table->timestamp('planned_finish_time')->nullable(true);
            $table->timestamp('actual_start_time')->nullable(true);
            $table->timestamp('actual_finish_time')->nullable(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('productions_plans');
    }
}
