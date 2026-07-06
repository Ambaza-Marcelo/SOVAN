<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAccountParentAccountsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('account_parent_accounts', function (Blueprint $table) {
            $table->id();
            $table->string('account_name');
            $table->unique('account_name');
            $table->string('account_number');
            $table->unique('account_number');
            $table->string('code')->nullable(true);
            $table->string('account_type')->nullable(true);
            $table->boolean('active')->default(1);
            $table->string('balance_must_be')->nullable(true);//credit or debit
            $table->double('balance')->nullable(true);
            $table->text('description')->nullable(true);
            $table->bigInteger('currency_id')->unsigned()->nullable(true);
            $table->foreign('currency_id')
                    ->references('id')
                    ->on('currencies')
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
        Schema::dropIfExists('account_parent_accounts');
    }
}
