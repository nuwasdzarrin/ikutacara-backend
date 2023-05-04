<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\URL;

class Payment extends Model
{
    use HasFactory;

    protected $appends = ['logo_url'];

    public function getLogoUrlAttribute() {
        return $this->logo ? URL::to('/storage/'.$this->logo) : '';
    }
}
