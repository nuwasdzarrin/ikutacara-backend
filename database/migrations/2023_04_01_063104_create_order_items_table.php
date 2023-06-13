<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateOrderItemsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->integer('order_id');
            $table->integer('event_id');
            $table->integer('ticket_id');
            $table->bigInteger('ticket_price');
            $table->string('ticket_name')->nullable();
            $table->string('ticket_code');
            $table->enum('ticket_status', \App\Models\OrderItem::STATUS)->default('waiting');
            $table->json('attendee');
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
        Schema::dropIfExists('order_items');
    }
}
