<?php

namespace App\Architectures\A04_DecoratorDomain\Phase_01\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Reservation extends Model
{
    protected $fillable = [
        'type',
        'base_price',
    ];

    protected $appends = ['total'];

    public function extras(): HasMany
    {
        return $this->hasMany(Extra::class);
    }

    public function getTotalAttribute(): float
    {
        return $this->base_price + $this->extras->sum('price');
    }
}
