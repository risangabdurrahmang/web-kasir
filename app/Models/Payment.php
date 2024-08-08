<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Payment extends Model
{
    use HasFactory;

    protected $fillable = [
        'payment_method',
        'image',
        // 'is_active',
    ];

    public function order(): HasMany
    {
        return $this->hashMany(Order::class);
    }
}
