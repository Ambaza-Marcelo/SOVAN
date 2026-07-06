<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateProductionPlanDetailsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('productions_plan_details', function (Blueprint $table) {
            $table->id();
            $table->string('production_number')->nullable(true);
            $table->timestamp('production_date')->default(DB::raw('CURRENT_TIMESTAMP'));
            $table->timestamp('start_date');
            $table->timestamp('end_date');
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
            $table->string('lot_number')->nullable(true);
            $table->string('batch_number')->nullable(true);
            $table->double('total_material_cost')->default(0);
            $table->double('total_item_cost')->default(0);
            //raw materials
            $table->double('material_quantity')->nullable(true);
            $table->double('material_cost')->nullable(true);
            $table->bigInteger('r_material_id')->unsigned()->nullable(true);
            $table->foreign('r_material_id')
                    ->references('id')
                    ->on('raw_materials')
                    ->onUpdate('cascade')
                    ->onDelete('cascade');
            //finished goods
            $table->double('item_quantity')->nullable(true);
            $table->double('cost_price')->nullable(true);
            $table->bigInteger('item_id')->unsigned()->nullable(true);
            $table->foreign('item_id')
                    ->references('id')
                    ->on('items')
                    ->onUpdate('cascade')
                    ->onDelete('cascade');
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
        Schema::dropIfExists('productions_plan_details');
    }
}
