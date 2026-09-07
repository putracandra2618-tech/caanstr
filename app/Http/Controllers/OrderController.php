<?php

namespace App\Http\Controllers;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\Promo;
use App\Models\Product;
use App\Services\MidtransService;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    public function store(Request $request)
    {
        if (!auth()->check()) {
            return redirect()->route('login')->with('error', 'Silakan login terlebih dahulu.');
        }

        $validated = $request->validate([
            'product_id' => 'required|exists:products,id',
            'game_id' => 'required|string|max:50',
            'game_zone' => 'nullable|string|max:20',
            'quantity' => 'required|integer|min:1|max:10',
            'promo_code' => 'nullable|string|exists:promos,code',
        ]);

        $product = Product::findOrFail($validated['product_id']);
        $subtotal = $product->price * $validated['quantity'];
        $adminFee = 0;
        $discount = 0;

        if (!empty($validated['promo_code'])) {
            $promo = Promo::where('code', $validated['promo_code'])->first();
            if ($promo && $promo->isValid($subtotal)) {
                $discount = $promo->calculateDiscount($subtotal);
                $promo->increment('used_count');
            }
        }

        $total = max(0, $subtotal + $adminFee - $discount);

        $order = Order::create([
            'user_id' => auth()->id(),
            'order_number' => Order::generateOrderNumber(),
            'product_id' => $product->id,
            'game_id' => $validated['game_id'],
            'game_zone' => $validated['game_zone'] ?? null,
            'quantity' => $validated['quantity'],
            'subtotal' => $subtotal,
            'admin_fee' => $adminFee,
            'discount' => $discount,
            'total' => $total,
            'status' => OrderStatus::Pending,
        ]);

        return redirect()->route('order.payment', $order->order_number);
    }

    public function detail(string $orderNumber)
    {
        $order = Order::where('order_number', $orderNumber)
            ->where('user_id', auth()->id())
            ->with('product.category', 'transaction')
            ->firstOrFail();

        return view('pages.order-detail', compact('order'));
    }

    public function history()
    {
        $orders = Order::where('user_id', auth()->id())
            ->with('product.category')
            ->latest()
            ->paginate(10);

        return view('pages.history', compact('orders'));
    }

    public function payment(string $orderNumber, MidtransService $midtrans)
    {
        $order = Order::where('order_number', $orderNumber)
            ->where('user_id', auth()->id())
            ->where('status', OrderStatus::Pending)
            ->firstOrFail();

        if (blank($order->snap_token)) {
            try {
                $order->loadMissing(['product', 'user']);
                $order->update(['snap_token' => $midtrans->createSnapToken($order)]);
            } catch (\RuntimeException $exception) {
                return redirect()
                    ->route('order.detail', $order->order_number)
                    ->with('error', $exception->getMessage());
            }
        }

        return view('pages.payment', compact('order'));
    }
}
