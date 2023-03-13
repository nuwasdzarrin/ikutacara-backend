<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Event extends Model
{
    use HasFactory;

    protected $casts = [
        'date' => 'array',
        'location' => 'array',
        'setting' => 'array',
    ];

    public function scopeSearch($query, $value)
    {
        if (!$value) return $query;
        return $query->where('name', 'like', '%' . $value . '%')
            ->orWhere('description', 'like', '%' . $value . '%')
            ->orWhere('organizer_logo', 'like', '%' . $value . '%');
    }

    public function tickets() {
        return $this->hasMany(Ticket::class)->orderBy('price');
    }
}
