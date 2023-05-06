<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AlterOrdersTableAddPaymentId extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->unsignedInteger('payment_id')->nullable()->after('event_id');
            $table->string('xendit_payment_id')->nullable()->after('payment_id');
            $table->string('payment_url')->nullable()->after('order_price');
            $table->string('payment_status')->nullable()->after('order_status');
            $table->timestamp('expired_at')->nullable()->after('payment_status');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['payment_id','xendit_payment_id','payment_url','payment_status','expired_at']);
        });
    }
}
