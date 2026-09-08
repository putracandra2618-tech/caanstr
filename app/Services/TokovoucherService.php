<?php

namespace App\Services;

use App\Models\Category;
use App\Models\Product;
use App\Models\SyncedProduct;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class TokovoucherService
{
    protected string $memberCode;

    protected string $secretKey;

    protected string $baseUrl;

    public function __construct()
    {
        $this->memberCode = (string) config('tokovoucher.member_code');
        $this->secretKey = (string) config('tokovoucher.secret_key');
        $this->baseUrl = rtrim((string) config('tokovoucher.base_url'), '/');
    }

    protected function generateSignature(string $refId = ''): string
    {
        return filled($refId)
            ? md5($this->memberCode.':'.$this->secretKey.':'.$refId)
            : md5($this->memberCode.':'.$this->secretKey);
    }

    protected function defaultQuery(string $refId = ''): array
    {
        return [
            'member_code' => $this->memberCode,
            'signature' => $this->generateSignature($refId),
        ];
    }

    protected array $gameCategoryIds = [1, 2, 19, 21, 22, 23, 24];

    protected function isGameOperator(?array $operator, string $brand): bool
    {
        if (Str::slug($brand) === 'produk-nonaktif') {
            return false;
        }

        return in_array($operator['category_id'] ?? null, $this->gameCategoryIds, true);
    }

    public function getProductTree(): array
    {
        return Cache::remember('tokovoucher_product_tree', config('tokovoucher.cache_ttl', 3600), function () {
            $response = Http::get("{$this->baseUrl}/member/produk/full", $this->defaultQuery());

            if ($response->failed() || ($response->json('status') ?? 0) !== 1) {
                Log::error('Tokovoucher product tree failed', [
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
        $tree = $this->getProductTree();
        $synced = ['created' => 0, 'updated' => 0, 'skipped' => 0];

        $operators = collect($tree['operator'] ?? [])->keyBy('id');
        $produks = $tree['produk'] ?? [];

        foreach ($produks as $item) {
            $operator = $operators->get($item['operator_id'] ?? null);
            $brand = $operator['nama'] ?? ($item['operator_produk'] ?? '');
            $kodeProduk = $item['kode_produk'] ?? '';

            if (blank($kodeProduk)) {
                $synced['skipped']++;

                continue;
            }

            $categoryModel = Category::updateOrCreate(
                ['slug' => Str::slug($brand)],
                [
                    'name' => $brand,
                    'image' => null,
                    'description' => "Produk {$brand} dari Tokovoucher",
                    'is_active' => true,
                    'is_game' => $this->isGameOperator($operator, $brand),
                    'sort_order' => 0,
                ]
            );

            $slug = Str::slug($item['nama'] ?? $kodeProduk);
            $existingProduct = Product::where('slug', $slug)->first();

            if ($existingProduct) {
                $existingProduct->update([
                    'cost_price' => $item['price'],
                    'price' => $item['price'] + ($item['price'] * 0.1),
                ]);

                SyncedProduct::updateOrCreate(
                    ['tokovoucher_sku' => $kodeProduk],
                    [
                        'category_id' => $categoryModel->id,
                        'product_id' => $existingProduct->id,
                        'tokovoucher_price' => $item['price'],
                        'brand' => $brand,
                        'type' => $item['jenis_id'] ?? '',
                        'is_active' => ($item['status'] ?? 1) === 1,
                        'last_synced_at' => now(),
                    ]
                );

                $synced['updated']++;
            } else {
                $newProduct = Product::create([
                    'category_id' => $categoryModel->id,
                    'name' => $item['nama'] ?? $kodeProduk,
                    'slug' => $slug,
                    'description' => $item['deskripsi'] ?? '',
                    'price' => $item['price'] + ($item['price'] * 0.1),
                    'cost_price' => $item['price'],
                    'product_code' => $kodeProduk,
                    'is_active' => ($item['status'] ?? 1) === 1,
                    'sort_order' => 0,
                ]);

                SyncedProduct::create([
                    'category_id' => $categoryModel->id,
                    'product_id' => $newProduct->id,
                    'tokovoucher_sku' => $kodeProduk,
                    'tokovoucher_price' => $item['price'],
                    'brand' => $brand,
                    'type' => $item['jenis_id'] ?? '',
                    'is_active' => ($item['status'] ?? 1) === 1,
                    'last_synced_at' => now(),
                ]);

                $synced['created']++;
            }
        }

        return $synced;
    }

    public function topUp(string $kodeProduk, string $tujuan, ?string $serverId, string $refId): array
    {
        $dryRun = match (strtolower((string) config('tokovoucher.dry_run', 'off'))) {
            'sukses' => 'sukses',
            'pending' => 'pending',
            'gagal' => 'gagal',
            default => 'off',
        };

        if ($dryRun !== 'off') {
            Log::info('Tokovoucher topup dry-run', [
                'produk' => $kodeProduk,
                'tujuan' => $tujuan,
                'ref_id' => $refId,
                'mode' => $dryRun,
            ]);

            return $this->result(
                success: $dryRun === 'sukses',
                pending: $dryRun === 'pending',
                status: $dryRun,
                message: "Dry-run Tokovoucher: {$dryRun}",
                data: [
                    'ref_id' => $refId,
                    'status' => $dryRun,
                    'sn' => "DRYRUN-{$refId}",
                    'dry_run' => true,
                ],
            );
        }

        $payload = [
            'ref_id' => $refId,
            'produk' => $kodeProduk,
            'tujuan' => $tujuan,
            'member_code' => $this->memberCode,
            'signature' => $this->generateSignature($refId),
        ];

        if (filled($serverId)) {
            $payload['server_id'] = $serverId;
        }

        $response = Http::post("{$this->baseUrl}/v1/transaksi", $payload);

        if ($response->failed() || ($response->json('status') ?? '') === 0) {
            $message = $response->json('error_msg')
                ?? data_get($response->json(), 'message')
                ?? 'Request ke Tokovoucher gagal';

            Log::error('Tokovoucher topup failed', [
                'produk' => $kodeProduk,
                'tujuan' => $tujuan,
                'ref_id' => $refId,
                'status' => $response->status(),
                'message' => $message,
                'body' => $response->body(),
            ]);

            return $this->result(false, false, 'Gagal', 'Tokovoucher: '.$message, null);
        }

        return $this->result(
            success: $response->json('status') === 'sukses',
            pending: $response->json('status') === 'pending',
            status: $response->json('status') ?? 'Unknown',
            message: $response->json('message') ?? '',
            data: $response->json(),
        );
    }

    public function checkStatus(string $refId): array
    {
        $response = Http::post("{$this->baseUrl}/v1/transaksi/status", [
            'ref_id' => $refId,
            'member_code' => $this->memberCode,
            'signature' => $this->generateSignature($refId),
        ]);

        if ($response->failed() || ($response->json('status') ?? '') === 0) {
            return ['success' => false, 'status' => 'Unknown', 'data' => null];
        }

        return $this->result(
            success: $response->json('status') === 'sukses',
            pending: $response->json('status') === 'pending',
            status: $response->json('status') ?? 'Unknown',
            message: $response->json('message') ?? '',
            data: $response->json(),
        );
    }

    public function getBalance(): ?float
    {
        $response = Http::get("{$this->baseUrl}/member", $this->defaultQuery());

        if ($response->failed() || ($response->json('status') ?? 0) !== 1) {
            return null;
        }

        return (float) ($response->json('data.saldo', 0));
    }

    public function verifyWebhookSignature(string $refId, string $providedSignature): bool
    {
        if (blank($refId) || blank($providedSignature)) {
            return false;
        }

        return hash_equals(
            $this->generateSignature($refId),
            $providedSignature,
        );
    }

    public function handleCallback(array $payload): array
    {
        $refId = (string) ($payload['ref_id'] ?? '');
        $status = (string) ($payload['status'] ?? '');
        $sn = (string) ($payload['sn'] ?? '');

        Log::info('Tokovoucher callback received', [
            'ref_id' => $refId,
            'status' => $status,
            'sn' => $sn,
        ]);

        return [
            'ref_id' => $refId,
            'status' => $status,
            'sn' => $sn,
            'raw' => $payload,
        ];
    }

    protected function result(
        bool $success,
        bool $pending,
        string $status,
        string $message,
        ?array $data,
    ): array {
        return [
            'success' => $success,
            'pending' => $pending,
            'status' => $status,
            'message' => $message,
            'data' => $data,
        ];
    }
}
