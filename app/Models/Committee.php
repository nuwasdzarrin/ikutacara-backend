<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Committee extends Model
{
    use HasFactory;
    const COMMITTEE_RULES = ['owner', 'committee'];

    public function user() {
        return $this->hasOne(User::class, 'id', 'user_id');
    }
}
