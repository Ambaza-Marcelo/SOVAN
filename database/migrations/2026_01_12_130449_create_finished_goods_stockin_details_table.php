<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateFinishedGoodsStockinDetailsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('finished_goods_stockin_details', function (Blueprint $table) {
            $table->id();
            $table->dateTime('date');
            $table->string('quantity')->nullable(true);
            $table->string('total_cost_value')->nullable(true);
            $table->string('cost_price')->nullable(true);
            $table->string('stockin_no');
            $table->foreign('stockin_no')
                    ->references('stockin_no')
                    ->on('finished_goods_stockins')
                    ->onUpdate('cascade')
                    ->onDelete('cascade');
            $table->string('receptionist')->nullable(true);
            $table->string('handingover')->nullable(true);
            $table->string('origin')->nullable(true);
            $table->string('item_purchase_or_sale_currency')->nullable(true);
            $table->enum('item_movement_type', ['EN', 'ER', 'EAJ', 'EAU'])->nullable(true)->comment('EN: Entrées Normales, ER: Entrées en cas de Retour de marchandise, EAJ: Entrées d\'Ajustement, EAU: Entrées Autres');
            $table->string('stockin_signature')->nullable(true);
            $table->string('store_type')->nullable(true);
            $table->text('description')->nullable(true);
            $table->text('rejected_motif')->nullable(true);
            $table->string('total_value')->nullable(true);
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
            $table->bigInteger('item_id')->unsigned()->nullable(true);
            $table->foreign('item_id')
                            ->references('id')
                            ->on('items')
                            ->onUpdate('cascade')
                            ->onDelete('cascade');
            $table->bigInteger('finished_g_sm_store_id')->unsigned()->nullable(true);
            $table->foreign('finished_g_sm_store_id')
                    ->references('id')
                    ->on('finished_goods_sm_stores')
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
        Schema::dropIfExists('finished_goods_stockin_details');
    }
}
