<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateOrdersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->integer('event_id')->nullable();
            $table->integer('user_id')->nullable();
            $table->string('order_user_name')->nullable();
            $table->string('order_user_email')->nullable();
            $table->string('order_user_whatsapp')->nullable();
            $table->integer('order_quantity')->nullable();
            $table->bigInteger('order_price')->nullable();
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
        Schema::dropIfExists('orders');
    }
}
