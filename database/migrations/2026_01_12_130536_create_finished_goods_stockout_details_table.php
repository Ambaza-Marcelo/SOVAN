<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateFinishedGoodsStockoutDetailsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('finished_goods_stockout_details', function (Blueprint $table) {
            $table->id();
            $table->dateTime('date');
            $table->double('quantity')->nullable(true);
            $table->string('stockout_no');
            $table->foreign('stockout_no')
                    ->references('stockout_no')
                    ->on('finished_goods_stockouts')
                    ->onUpdate('cascade')
                    ->onDelete('cascade');
            $table->string('stockout_signature')->nullable(true);
            $table->string('requisition_no')->nullable(true);
            $table->string('asker')->nullable(true);
            $table->string('destination')->nullable(true);
            $table->string('store_type')->nullable(true);
            $table->enum('item_movement_type', ['SN', 'SP', 'SV', 'SC', 'SAJ', 'SAU'])->nullable(true)->comment('SN: Sorties Normales, SP: Sorties Perte, SV: Sorties Vol, SC: Sorties Casse, SAJ: Sorties Ajustement, SAU: Sorties Autres');
            $table->text('description')->nullable(true);
            $table->text('rejected_motif')->nullable(true);
            $table->double('cost_price')->nullable(true);
            $table->double('total_cost_value')->nullable(true);
            $table->string('created_by')->nullable(true);
            $table->string('updated_by')->nullable(true);
            $table->string('validated_by')->nullable(true);
            $table->string('confirmed_by')->nullable(true);
            $table->string('approuved_by')->nullable(true);
            $table->string('rejected_by')->nullable(true);
            $table->string('reseted_by')->nullable(true);
            $table->string('status')->default('1');
            $table->bigInteger('finished_g_store_id')->unsigned()->nullable(true);
            $table->foreign('finished_g_store_id')
                    ->references('id')
                    ->on('finished_goods_stores')
                    ->onUpdate('cascade')
                    ->onDelete('cascade');
            $table->bigInteger('finished_g_sm_store_id')->unsigned()->nullable(true);
            $table->foreign('finished_g_sm_store_id')
                    ->references('id')
                    ->on('finished_goods_sm_stores')
                    ->onUpdate('cascade')
                    ->onDelete('cascade');
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
        Schema::dropIfExists('finished_goods_stockout_details');
    }
}
