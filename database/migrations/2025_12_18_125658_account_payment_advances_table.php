<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AccountPaymentAdvancesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('account_payment_advances', function (Blueprint $table) {
            $table->id();
            $table->dateTime('date');
            $table->string('code');
            $table->unique('code');
            $table->string('payment_type');
            $table->string('status')->default(0);
            $table->string('contact_name')->nullable(true);
            $table->string('contact_tel')->nullable(true);
            $table->string('created_email')->nullable(true);
            $table->string('created_by')->nullable(true);
            $table->string('confirmed_by')->nullable(true);
            $table->string('approuved_by')->nullable(true);
            $table->string('document')->nullable(true);
            $table->double('amount');
            $table->text('description');
            $table->bigInteger('client_id')->unsigned()->nullable(true);
            $table->foreign('client_id')
                    ->references('id')
                    ->on('clients')
                    ->onUpdate('cascade')
                    ->onDelete('cascade');
            $table->bigInteger('account_id')->unsigned()->nullable(true);
            $table->foreign('account_id')
                    ->references('id')
                    ->on('account_accounts')
                    ->onUpdate('cascade')
                    ->onDelete('cascade');
            $table->bigInteger('advance_account_id')->unsigned()->nullable(true);
            $table->foreign('advance_account_id')
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
        Schema::dropIfExists('account_payment_advances');
    }
}
