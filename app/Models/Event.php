<?php

namespace App\Models;

use App\Http\Controllers\Api\CommitteeController;
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
//            ->orWhere('description', 'like', '%' . $value . '%')
            ->orWhere('organizer_name', 'like', '%' . $value . '%');
    }

    public function tickets() {
        return $this->hasMany(Ticket::class)->orderBy('price');
    }

    public function committees() {
        return $this->hasMany(Committee::class);
    }

    public function members() {
        return $this->belongsToMany(User::class, 'committees', 'event_id', 'user_id');
    }

    public function getCommitteeRuleAttribute() {
        $committee = $this->hasOne(Committee::class, 'event_id', 'id')
            ->where('user_id', auth()->user() ? auth()->user()->id : 0)->first();
        return $committee ? $committee->committee_rule : '';
    }
}
