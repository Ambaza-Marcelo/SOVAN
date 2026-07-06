<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRawMaterialExtraBigStoresTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('raw_material_extra_big_stores', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable(true);
            $table->string('code')->nullable(true);
            $table->unique('code');
            $table->unique('name');
            $table->string('store_signature')->nullable(true);
            $table->string('emplacement')->nullable(true);
            $table->string('manager')->nullable(true);
            $table->boolean('verified')->default(false);
            $table->string('created_by')->nullable(true);
            $table->string('updated_by')->nullable(true);
            $table->text('description')->nullable(true);
            $table->bigInteger('default_account_id')->unsigned()->nullable(true);
            $table->foreign('default_account_id')
                    ->references('id')
                    ->on('account_accounts')
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
        Schema::dropIfExists('raw_material_extra_big_stores');
    }
}
