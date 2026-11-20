<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PixelforgeEvent extends Model
{
    protected $fillable = ['event_id', 'event_type', 'generation_id', 'payload', 'processed_at'];

    protected function casts(): array
    {
        return ['payload' => 'array', 'processed_at' => 'immutable_datetime'];
    }
}
