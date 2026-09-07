<?php

namespace App\Enums;

enum OrderStatus: string
{
    case Pending = 'pending';
    case Paid = 'paid';
    case Processing = 'processing';
    case Completed = 'completed';
    case Failed = 'failed';
    case Refunded = 'refunded';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Menunggu Pembayaran',
            self::Paid => 'Sudah Dibayar',
            self::Processing => 'Sedang Diproses',
            self::Completed => 'Berhasil',
            self::Failed => 'Gagal',
            self::Refunded => 'Dikembalikan',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Pending => 'yellow',
            self::Paid => 'blue',
            self::Processing => 'indigo',
            self::Completed => 'green',
            self::Failed => 'red',
            self::Refunded => 'gray',
        };
    }
}
