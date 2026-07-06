<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateExchangeRatesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('exchange_rates', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('origin_currency_id')->unsigned();
            $table->foreign('origin_currency_id')
                    ->references('id')
                    ->on('currencies')
                    ->onUpdate('cascade')
                    ->onDelete('cascade');
            $table->bigInteger('destination_currency_id')->unsigned();
            $table->foreign('destination_currency_id')
                    ->references('id')
                    ->on('currencies')
                    ->onUpdate('cascade')
                    ->onDelete('cascade');
            $table->double('rate')->nullable(false);
            $table->date('date')->nullable(false); // Date du taux de change
            $table->text('description')->nullable(true);
            $table->string('created_by')->nullable(true);
            $table->string('updated_by')->nullable(true);
            $table->boolean('active')->default(1);
            $table->timestamps();
            
            // Index unique pour éviter les doublons (même paire de devises à la même date)
            $table->unique(['origin_currency_id', 'destination_currency_id', 'date'], 'unique_exchange_rate');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('exchange_rates');
    }
}

