<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AutoTopupLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'provider',
        'request_data',
        'response_data',
        'status',
        'attempts',
        'error_message',
        'processed_at',
    ];

    protected function casts(): array
    {
        return [
            'request_data' => 'array',
            'response_data' => 'array',
            'attempts' => 'integer',
            'processed_at' => 'datetime',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }
}
