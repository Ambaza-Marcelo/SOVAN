<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAccountLedgersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('account_ledgers', function (Blueprint $table) {
            $table->id();
            $table->dateTime('date')->default(\Carbon\Carbon::now());
            $table->year('fiscal_year')->default(\Carbon\Carbon::now()->year);
            $table->double('credit')->nullable(true);
            $table->double('debit')->nullable(true);
            $table->double('balance')->nullable(true);
            $table->bigInteger('account_id')->unsigned()->nullable(true);
            $table->foreign('account_id')
                    ->references('id')
                    ->on('account_accounts')
                    ->onUpdate('cascade')
                    ->onDelete('cascade');
            $table->bigInteger('exercise_id')->unsigned()->nullable(true);
            $table->foreign('exercise_id')
                    ->references('id')
                    ->on('account_exercises')
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
        Schema::dropIfExists('account_ledgers');
    }
}
