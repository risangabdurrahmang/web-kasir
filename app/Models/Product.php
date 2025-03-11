<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

class Product extends Model
{
    use SoftDeletes;
    use HasFactory;

    protected $fillable = [
        'category_id',
        'name',
        'sku',
        'slug',
        'price',
        'stock',
        'description',
        'image',
        'is_active',
    ];

    protected static function boot()
    {
        parent::boot();

        // Saat model diperbarui dan gambar diubah, hapus gambar lama
        static::updating(function ($model) {
            if ($model->isDirty('image') && ($model->getOriginal('image') !== null)) {
                $filePath = $model->getOriginal('image');
                if (Storage::disk('public')->exists($filePath)) {
                    Storage::disk('public')->delete($filePath);
                }
            }
        });

        // Saat model dihapus (soft delete), jangan hapus gambar
        static::deleting(function ($model) {
            if ($model->forceDeleting && $model->image !== null) {
                // Jika force delete, hapus gambar dari storage
                $filePath = $model->image;
                if (Storage::disk('public')->exists($filePath)) {
                    Storage::disk('public')->delete($filePath);
                }
            }
        });
    }

    protected $with = ['category'];

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }
}
