<?php

namespace App\Models;

use App\Http\Controllers\Api\CommitteeController;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\URL;

class Event extends Model
{
    use HasFactory;

    protected $casts = [
        'date' => 'array',
        'location' => 'array',
        'setting' => 'array',
    ];
    protected $appends = ['banner_url', 'organizer_logo_url'];

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

    public function getBannerUrlAttribute() {
        if (!$this->banner) return '';
        $url = parse_url($this->banner);
        if ($url['path']) return URL::to($url['path']);
        return '';
    }
    public function getOrganizerLogoUrlAttribute() {
        if (!$this->organizer_logo) return '';
        $url = parse_url($this->organizer_logo);
        if ($url['path']) return URL::to($url['path']);
        return '';
    }
}
