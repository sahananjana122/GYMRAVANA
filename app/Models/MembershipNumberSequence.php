<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MembershipNumberSequence extends Model
{
    protected $fillable = ['year', 'current_number'];

    protected function casts(): array
    {
        return [
            'year' => 'integer',
            'current_number' => 'integer',
        ];
    }
}
