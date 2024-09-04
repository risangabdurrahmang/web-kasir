<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Customer extends Model
{
    use SoftDeletes;
    use HasFactory;

    protected $fillable = [
        'name',
        'gender',
        'email',
        'phone',
    ];

    public function order(): HasMany
    {
        return $this->hasMany(Order::class);
    }
}
