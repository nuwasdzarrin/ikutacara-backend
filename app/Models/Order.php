<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory;
    protected $hidden = ['payment'];
    protected $appends = ['payment_name'];

    public function event() {
        return $this->belongsTo(Event::class);
    }
    public function payment() {
        return $this->belongsTo(Payment::class);
    }
    public function order_items() {
        return $this->hasMany(OrderItem::class);
    }
    public function payment_instructions() {
        return $this->hasMany(PaymentInstruction::class, 'payment_id', 'payment_id');
    }

    public function getPaymentNameAttribute() {
        return $this->payment ? $this->payment->name : '';
    }
}
