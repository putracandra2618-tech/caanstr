<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SyncedProduct extends Model
{
    use HasFactory;

    protected $fillable = [
        'category_id',
        'product_id',
        'digiflazz_sku',
        'digiflazz_price',
        'brand',
        'type',
        'is_active',
        'last_synced_at',
    ];

    protected function casts(): array
    {
        return [
            'digiflazz_price' => 'decimal:2',
            'is_active' => 'boolean',
            'last_synced_at' => 'datetime',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
