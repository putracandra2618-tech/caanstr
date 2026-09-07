<?php

namespace App\Models;

use App\Enums\OrderStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Str;

class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'order_number',
        'product_id',
        'game_id',
        'game_zone',
        'quantity',
        'subtotal',
        'admin_fee',
        'discount',
        'total',
        'status',
        'payment_method',
        'snap_token',
        'paid_at',
        'completed_at',
        'failed_reason',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'subtotal' => 'decimal:2',
            'admin_fee' => 'decimal:2',
            'discount' => 'decimal:2',
            'total' => 'decimal:2',
            'status' => OrderStatus::class,
            'paid_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function transaction(): HasOne
    {
        return $this->hasOne(Transaction::class);
    }

    public function autoTopupLogs(): HasMany
    {
        return $this->hasMany(AutoTopupLog::class);
    }

    public static function generateOrderNumber(): string
    {
        $date = now()->format('Ymd');
        $lastOrder = static::where('order_number', 'like', "ORD-{$date}-%")
            ->orderByDesc('order_number')
            ->value('order_number');

        if ($lastOrder) {
            $sequence = (int) substr($lastOrder, -4) + 1;
        } else {
            $sequence = 1;
        }

        return sprintf('ORD-%s-%04d', $date, $sequence);
    }

    public function isPaid(): bool
    {
        return $this->status === OrderStatus::Paid;
    }

    public function isCompleted(): bool
    {
        return $this->status === OrderStatus::Completed;
    }

    public function isPending(): bool
    {
        return $this->status === OrderStatus::Pending;
    }
}
