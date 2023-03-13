<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateEventsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('events', function (Blueprint $table) {
            $table->id();
            $table->integer('user_id')->nullable();
            $table->string('name');
            $table->string('slug');
            $table->string('banner')->nullable();
            $table->string('organizer_name')->nullable();
            $table->string('organizer_logo')->nullable();
            $table->json('date')->nullable();
            $table->json('location')->nullable();
            $table->json('setting')->nullable();
            $table->text('description')->nullable();
            $table->enum('type', ['online', 'offline'])->nullable();
            $table->string('status')->nullable();
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
        Schema::dropIfExists('events');
    }
}
