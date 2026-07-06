<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAccountJournalEntriesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('account_journal_entries', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable(true);
            $table->string('date')->nullable(true);
            $table->string('type')->nullable(true);
            $table->string('code')->nullable(true);
            $table->string('fiscal_year')->nullable(true);
            $table->text('description')->nullable(true);
            $table->boolean('active')->default(0);
            $table->bigInteger('loss_account_id')->unsigned()->nullable(true);
            $table->foreign('loss_account_id')
                    ->references('id')
                    ->on('account_accounts')
                    ->onUpdate('cascade')
                    ->onDelete('cascade');
            $table->bigInteger('profit_account_id')->unsigned()->nullable(true);
            $table->foreign('profit_account_id')
                    ->references('id')
                    ->on('account_accounts')
                    ->onUpdate('cascade')
                    ->onDelete('cascade');
            $table->bigInteger('default_debit_account_id')->unsigned()->nullable(true);
            $table->foreign('default_debit_account_id')
                    ->references('id')
                    ->on('account_accounts')
                    ->onUpdate('cascade')
                    ->onDelete('cascade');
            $table->bigInteger('default_credit_account_id')->unsigned()->nullable(true);
            $table->foreign('default_credit_account_id')
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
        Schema::dropIfExists('account_journal_entries');
    }
}
