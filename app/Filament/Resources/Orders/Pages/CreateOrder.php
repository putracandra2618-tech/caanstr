<?php

namespace App\Filament\Resources\Orders\Pages;

use App\Filament\Resources\Orders\OrderResource;
use App\Models\Order;
use App\Models\Product;
use Filament\Resources\Pages\CreateRecord;

class CreateOrder extends CreateRecord
{
    protected static string $resource = OrderResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $product = Product::find($data['product_id'] ?? null);
        $quantity = $data['quantity'] ?? 1;
        $subtotal = $product?->price * $quantity;
        $discount = (int) ($data['discount'] ?? 0);
        $adminFee = (int) ($data['admin_fee'] ?? 0);

        $data['subtotal'] = $subtotal;
        $data['total'] = max(0, $subtotal + $adminFee - $discount);

        if (blank($data['order_number'] ?? null)) {
            $data['order_number'] = Order::generateOrderNumber();
        }

        return $data;
    }
}
