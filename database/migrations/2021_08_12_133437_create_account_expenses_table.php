<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAccountExpensesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('account_expenses', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code');
            $table->unique('name');
            $table->unique('code');
            $table->string('specification')->nullable(true);
            $table->string('quantity')->nullable(true);
            $table->string('cost_price')->nullable(true);
            $table->string('status')->nullable(true);
            $table->string('total_cost')->nullable(true);
            $table->string('updated_by')->nullable(true);
            $table->string('created_by')->nullable(true);
            $table->bigInteger('expense_category_id')->unsigned()->nullable(true);
            $table->foreign('expense_category_id')
                    ->references('id')
                    ->on('account_expense_categories')
                    ->onUpdate('cascade')
                    ->onDelete('cascade');
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
        Schema::dropIfExists('account_expenses');
    }
}
