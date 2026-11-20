<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CreditLedgerEntry extends Model
{
    protected $fillable = ['user_id', 'generation_id', 'type', 'amount_units', 'description'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
