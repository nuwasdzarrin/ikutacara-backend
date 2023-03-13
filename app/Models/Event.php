<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Event extends Model
{
    use HasFactory;

    protected $casts = [
        'organizer' => 'array',
        'date' => 'array',
        'location' => 'array',
        'setting' => 'array',
    ];

    public function tickets() {
        return $this->hasMany(Ticket::class)->orderBy('price');
    }
}
