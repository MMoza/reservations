<?php

namespace App\Architectures\A01_MonolithicEloquent\Phase_02\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Extra extends Model
{
    protected $fillable = [
        'reservation_id',
        'name',
        'price',
    ];

    public function reservation(): BelongsTo
    {
        return $this->belongsTo(Reservation::class);
    }
}
