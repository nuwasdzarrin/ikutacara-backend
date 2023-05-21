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
            $table->bigInteger('admin_fee')->nullable()->after('order_price');
            $table->string('payment_url')->nullable()->after('admin_fee');
            $table->string('payment_status')->nullable()->after('order_status');
            $table->string('payment_account_name')->nullable()->after('payment_status');
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
            $table->dropColumn(['payment_id','xendit_payment_id','admin_fee','payment_url','payment_status',
                'payment_account_name','expired_at']);
        });
    }
}
