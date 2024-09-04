<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Payment extends Model
{
    use SoftDeletes;
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
