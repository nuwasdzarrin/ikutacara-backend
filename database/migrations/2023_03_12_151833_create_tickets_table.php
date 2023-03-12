<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTicketsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('tickets', function (Blueprint $table) {
            $table->id();
            $table->integer('event_id')->nullable();
            $table->enum('category', ['free','paid'])->nullable();
            $table->string('name');
            $table->integer('quota')->nullable();
            $table->integer('price')->nullable();
            $table->text('desc')->nullable();
            $table->timestamp('sale_started')->nullable();
            $table->timestamp('sale_end')->nullable();
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
        Schema::dropIfExists('tickets');
    }
}
