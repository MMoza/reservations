<?php

namespace App\Architectures\A03_StrategyPolymorphism\Phase_02\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Reservation extends Model
{
    protected $fillable = [
        'type',
        'base_price',
        'discount_amount',
        'discount_reason',
    ];

    protected $appends = ['total'];

    public function extras(): HasMany
    {
        return $this->hasMany(Extra::class);
    }

    public function getTotalAttribute(): float
    {
        return $this->base_price - $this->discount_amount + $this->extras->sum('price');
    }
}
