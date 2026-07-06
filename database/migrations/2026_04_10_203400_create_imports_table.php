<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateImportsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('imports', function (Blueprint $table) {
            $table->id();
            $table->dateTime('date');
            $table->string('import_no');
            $table->unique('import_no');
            $table->string('import_signature')->nullable(true);
            $table->string('store_type')->nullable(true);
            $table->string('receptionist')->nullable(true);
            $table->string('handingover')->nullable(true);
            $table->string('origin')->nullable(true);
            $table->string('item_purchase_or_sale_currency')->nullable(true);
            $table->enum('item_movement_type', ['EN'])->nullable(true)->comment('EN: Entrées Normales');
            $table->text('description')->nullable(true);
            $table->text('rejected_motif')->nullable(true);
            $table->string('created_by')->nullable(true);
            $table->string('updated_by')->nullable(true);
            $table->string('validated_by')->nullable(true);
            $table->string('confirmed_by')->nullable(true);
            $table->string('approuved_by')->nullable(true);
            $table->string('rejected_by')->nullable(true);
            $table->string('reseted_by')->nullable(true);
            $table->string('reference_dmc')->nullable(true);
            $table->string('rubrique_tarifaire')->nullable(true);
            $table->double('nombre_par_paquet')->nullable(true);
            $table->string('description_paquet')->nullable(true);
            $table->string('status')->default('1');
            $table->double('transport_cost')->nullable(true);
            $table->double('clearance_cost')->nullable(true);
            $table->double('total_amount')->nullable(true);
            $table->bigInteger('goods_store_id')->unsigned()->nullable(true);
            $table->foreign('goods_store_id')
                    ->references('id')
                    ->on('goods_stores')
                    ->onUpdate('cascade')
                    ->onDelete('cascade');
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
        Schema::dropIfExists('imports');
    }
}
