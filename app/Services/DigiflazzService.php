<?php

namespace App\Services;

use App\Models\Category;
use App\Models\Product;
use App\Models\SyncedProduct;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class DigiflazzService
{
    protected string $username;

    protected string $apiKey;

    protected string $baseUrl;

    protected bool $development;

    public function __construct()
    {
        $this->username = config('digiflazz.username');
        $this->apiKey = config('digiflazz.api_key');
        $this->baseUrl = config('digiflazz.base_url');
        $this->development = filter_var(config('digiflazz.development', true), FILTER_VALIDATE_BOOL);
    }

    protected function generateSignature(string $refId = ''): string
    {
        return md5($this->username.$this->apiKey.$refId);
    }

    protected function devFlag(): array
    {
        return $this->development ? ['testing' => true] : [];
    }

    public function getPriceList(string $cmd = 'prepaid'): array
    {
        $cacheKey = "digiflazz_pricelist_{$cmd}";

        return Cache::remember($cacheKey, config('digiflazz.cache_ttl', 3600), function () use ($cmd) {
            $response = Http::post("{$this->baseUrl}/price-list", [
                'cmd' => $cmd,
                'username' => $this->username,
                'sign' => $this->generateSignature('pricelist'),
            ]);

            if ($response->failed()) {
                Log::error('DigiFlazz price list failed', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                return [];
            }

            return $response->json('data', []);
        });
    }

    public function syncProducts(): array
    {
        $products = $this->getPriceList('prepaid');
        $synced = ['created' => 0, 'updated' => 0, 'skipped' => 0];

        $gameBrands = [
            'Mobile Legends', 'Free Fire', 'PUBG Mobile', 'Genshin Impact',
            'Arena of Valor', 'Roblox', 'Steam Wallet', 'Google Play',
            'Apple iTunes', 'PlayStation', 'Xbox', 'Nintendo',
            'Valorant', 'Apex Legends', 'Call of Duty Mobile',
        ];

        foreach ($products as $item) {
            $brand = $item['brand'] ?? '';
            $category = $item['category'] ?? '';

            if (! in_array($brand, $gameBrands) && $category !== 'Game') {
                $synced['skipped']++;

                continue;
            }

            $categoryModel = Category::firstOrCreate(
                ['slug' => \Str::slug($brand)],
                [
                    'name' => $brand,
                    'image' => null,
                    'description' => "Produk {$brand} dari DigiFlazz",
                    'is_active' => true,
                    'sort_order' => 0,
                ]
            );

            $slug = \Str::slug($item['product_name'] ?? $item['buyer_sku_code']);
            $existingProduct = Product::where('slug', $slug)->first();

            if ($existingProduct) {
                $existingProduct->update([
                    'cost_price' => $item['price'],
                    'price' => $item['price'] + ($item['price'] * 0.1),
                ]);

                SyncedProduct::updateOrCreate(
                    ['digiflazz_sku' => $item['buyer_sku_code']],
                    [
                        'category_id' => $categoryModel->id,
                        'product_id' => $existingProduct->id,
                        'digiflazz_price' => $item['price'],
                        'brand' => $brand,
                        'type' => $item['type'] ?? '',
                        'is_active' => $item['buyer_product_status'] ?? true,
                        'last_synced_at' => now(),
                    ]
                );

                $synced['updated']++;
            } else {
                $newProduct = Product::create([
                    'category_id' => $categoryModel->id,
                    'name' => $item['product_name'] ?? $item['buyer_sku_code'],
                    'slug' => $slug,
                    'description' => $item['desc'] ?? '',
                    'price' => $item['price'] + ($item['price'] * 0.1),
                    'cost_price' => $item['price'],
                    'product_code' => $item['buyer_sku_code'],
                    'is_active' => $item['buyer_product_status'] ?? true,
                    'sort_order' => 0,
                ]);

                SyncedProduct::create([
                    'category_id' => $categoryModel->id,
                    'product_id' => $newProduct->id,
                    'digiflazz_sku' => $item['buyer_sku_code'],
                    'digiflazz_price' => $item['price'],
                    'brand' => $brand,
                    'type' => $item['type'] ?? '',
                    'is_active' => $item['buyer_product_status'] ?? true,
                    'last_synced_at' => now(),
                ]);

                $synced['created']++;
            }
        }

        return $synced;
    }

    public function topUp(string $skuCode, string $customerNo, string $refId): array
    {
        $response = Http::post("{$this->baseUrl}/transaction", [
            'username' => $this->username,
            'buyer_sku_code' => $skuCode,
            'customer_no' => $customerNo,
            'ref_id' => $refId,
            'sign' => $this->generateSignature($refId),
            ...$this->devFlag(),
        ]);

        if ($response->failed()) {
            Log::error('DigiFlazz topup failed', [
                'sku' => $skuCode,
                'customer' => $customerNo,
                'ref_id' => $refId,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return [
                'success' => false,
                'status' => 'Gagal',
                'message' => 'Request ke DigiFlazz gagal',
                'data' => null,
            ];
        }

        $data = $response->json('data', []);

        return [
            'success' => ($data['rc'] ?? '') === '00',
            'pending' => ($data['status'] ?? '') === 'Pending',
            'status' => $data['status'] ?? 'Unknown',
            'message' => $data['message'] ?? '',
            'data' => $data,
        ];
    }

    public function checkStatus(string $refId, string $skuCode, string $customerNo): array
    {
        $response = Http::post("{$this->baseUrl}/transaction", [
            'username' => $this->username,
            'buyer_sku_code' => $skuCode,
            'customer_no' => $customerNo,
            'ref_id' => $refId,
            'sign' => $this->generateSignature($refId),
            ...$this->devFlag(),
        ]);

        if ($response->failed()) {
            return ['success' => false, 'status' => 'Unknown', 'data' => null];
        }

        $data = $response->json('data', []);

        return [
            'success' => ($data['rc'] ?? '') === '00',
            'status' => $data['status'] ?? 'Unknown',
            'data' => $data,
        ];
    }

    public function getBalance(): ?float
    {
        $response = Http::post("{$this->baseUrl}/cek-saldo", [
            'cmd' => 'deposit',
            'username' => $this->username,
            'sign' => $this->generateSignature('depo'),
        ]);

        if ($response->failed()) {
            return null;
        }

        return (float) ($response->json('data.deposit', 0));
    }

    public function handleCallback(array $payload): array
    {
        $refId = $payload['ref_id'] ?? '';
        $status = $payload['status'] ?? '';
        $sn = $payload['sn'] ?? '';
        $rc = $payload['rc'] ?? '';

        Log::info('DigiFlazz callback received', [
            'ref_id' => $refId,
            'status' => $status,
            'sn' => $sn,
            'rc' => $rc,
        ]);

        return [
            'ref_id' => $refId,
            'status' => $status,
            'sn' => $sn,
            'rc' => $rc,
            'raw' => $payload,
        ];
    }
}
