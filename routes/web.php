<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::get('/', HomeController::class)->name('home');

Route::get('/category/{slug}', [ProductController::class, 'category'])->name('category');
Route::get('/product/{slug}', [ProductController::class, 'detail'])->name('product.detail');

Route::get('/login', [LoginController::class, 'show'])->name('login');
Route::post('/login', [LoginController::class, 'login']);
Route::get('/register', [RegisterController::class, 'show'])->name('register');
Route::post('/register', [RegisterController::class, 'register']);
Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

Route::middleware('auth')->group(function () {
    Route::post('/order', [OrderController::class, 'store'])->name('order.store');
    Route::get('/order/{order_number}', [OrderController::class, 'detail'])->name('order.detail');
    Route::get('/order/{order_number}/pay', [OrderController::class, 'payment'])->name('order.payment');
    Route::get('/history', [OrderController::class, 'history'])->name('history');

    Route::get('/profile', [ProfileController::class, 'show'])->name('profile');
    Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');

    Route::get('/topup/success/{order_number}', [PaymentController::class, 'success'])->name('payment.success');
    Route::get('/topup/failed/{order_number}', [PaymentController::class, 'failed'])->name('payment.failed');
});

Route::post('/midtrans/callback', [PaymentController::class, 'callback'])->name('midtrans.callback');
Route::post('/api/midtrans/callback', [PaymentController::class, 'callback']);
Route::post('/digiflazz/callback', [PaymentController::class, 'digiflazzCallback'])->name('digiflazz.callback');
