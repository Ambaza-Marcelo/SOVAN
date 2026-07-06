<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateGoodsStockoutsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('goods_stockouts', function (Blueprint $table) {
            $table->id();
            $table->dateTime('date');
            $table->string('stockout_no');
            $table->unique('stockout_no');
            $table->string('stockout_signature')->nullable(true);
            $table->string('requisition_no')->nullable(true);
            $table->string('asker')->nullable(true);
            $table->string('destination')->nullable(true);
            $table->string('store_type')->nullable(true);
            $table->string('item_purchase_or_sale_currency')->nullable(true);
            $table->enum('item_movement_type', ['SN', 'SP', 'SV', 'SC', 'SAJ', 'SAU'])->nullable(true)->comment('SN: Sorties Normales, SP: Sorties Perte, SV: Sorties Vol, SC: Sorties Casse, SAJ: Sorties Ajustement, SAU: Sorties Autres');
            $table->text('description')->nullable(true);
            $table->text('rejected_motif')->nullable(true);
            $table->string('created_by')->nullable(true);
            $table->string('updated_by')->nullable(true);
            $table->string('validated_by')->nullable(true);
            $table->string('confirmed_by')->nullable(true);
            $table->string('approuved_by')->nullable(true);
            $table->string('rejected_by')->nullable(true);
            $table->string('reseted_by')->nullable(true);
            $table->string('status')->default('1');
            $table->bigInteger('goods_store_id')->unsigned()->nullable(true);
            $table->foreign('goods_store_id')
                    ->references('id')
                    ->on('goods_stores')
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
        Schema::dropIfExists('goods_stockouts');
    }
}
