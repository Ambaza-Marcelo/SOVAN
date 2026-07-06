<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateFundTransfersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('fund_transfers', function (Blueprint $table) {
            $table->id();
            $table->dateTime('date');
            $table->string('code');
            $table->unique('code');
            $table->string('transfer_type');
            $table->string('status')->default(0);
            $table->string('created_by')->nullable(true);
            $table->string('confirmed_by')->nullable(true);
            $table->string('approuved_by')->nullable(true);
            $table->double('amount');
            $table->text('description');
            $table->bigInteger('origin_account_id')->unsigned()->nullable(true);
            $table->foreign('origin_account_id')
                    ->references('id')
                    ->on('account_accounts')
                    ->onUpdate('cascade')
                    ->onDelete('cascade');
            $table->bigInteger('destination_account_id')->unsigned()->nullable(true);
            $table->foreign('destination_account_id')
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
        Schema::dropIfExists('fund_transfers');
    }
}
