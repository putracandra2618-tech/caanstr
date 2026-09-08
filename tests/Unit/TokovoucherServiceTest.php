<?php

namespace Tests\Unit;

use App\Models\Category;
use App\Services\TokovoucherService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class TokovoucherServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('tokovoucher.member_code', 'MEMBER-TEST');
        config()->set('tokovoucher.secret_key', 'secret-key');
        config()->set('tokovoucher.base_url', 'https://api.tokovoucher.net');
        config()->set('tokovoucher.dry_run', 'off');
    }

    public function test_top_up_posts_transaction_with_member_code_and_signature(): void
    {
        Http::fake([
            'https://api.tokovoucher.net/v1/transaksi' => Http::response([
                'status' => 'sukses',
                'message' => 'Transaksi berhasil',
                'sn' => 'SN-123',
                'ref_id' => 'ORDER-001',
            ]),
        ]);

        $result = app(TokovoucherService::class)
            ->topUp('FF5', '123456', '2001', 'ORDER-001');

        Http::assertSent(function ($request): bool {
            return $request->url() === 'https://api.tokovoucher.net/v1/transaksi'
                && $request['produk'] === 'FF5'
                && $request['tujuan'] === '123456'
                && $request['server_id'] === '2001'
                && $request['ref_id'] === 'ORDER-001'
                && $request['member_code'] === 'MEMBER-TEST'
                && $request['signature'] === md5('MEMBER-TEST:secret-key:ORDER-001');
        });

        $this->assertTrue($result['success']);
        $this->assertFalse($result['pending']);
        $this->assertSame('SN-123', $result['data']['sn']);
    }

    public function test_top_up_omits_server_id_when_zone_is_null(): void
    {
        Http::fake([
            'https://api.tokovoucher.net/v1/transaksi' => Http::response([
                'status' => 'sukses',
                'message' => 'ok',
            ]),
        ]);

        app(TokovoucherService::class)->topUp('FF5', '123456', null, 'ORDER-002');

        Http::assertSent(fn ($request) => ! array_key_exists('server_id', $request->data()));
    }

    public function test_balance_uses_member_endpoint_with_default_signature(): void
    {
        Http::fake([
            'https://api.tokovoucher.net/member*' => Http::response([
                'status' => 1,
                'rc' => 200,
                'data' => ['saldo' => 500000],
            ]),
        ]);

        $balance = app(TokovoucherService::class)->getBalance();

        Http::assertSent(function ($request): bool {
            return str_starts_with($request->url(), 'https://api.tokovoucher.net/member')
                && $request['member_code'] === 'MEMBER-TEST'
                && $request['signature'] === md5('MEMBER-TEST:secret-key');
        });

        $this->assertSame(500000.0, $balance);
    }

    public function test_pending_top_up_is_not_reported_as_failed(): void
    {
        Http::fake([
            '*' => Http::response([
                'status' => 'pending',
                'message' => 'Menunggu proses',
            ]),
        ]);

        $result = app(TokovoucherService::class)
            ->topUp('FF5', '123456', null, 'ORDER-003');

        $this->assertFalse($result['success']);
        $this->assertTrue($result['pending']);
        $this->assertSame('pending', $result['status']);
    }

    public function test_top_up_surfaces_provider_message_on_error(): void
    {
        Http::fake([
            '*' => Http::response([
                'status' => 0,
                'error_msg' => 'Signature Invalid',
            ], 400),
        ]);

        $result = app(TokovoucherService::class)
            ->topUp('FF5', '123456', null, 'ORDER-004');

        $this->assertFalse($result['success']);
        $this->assertFalse($result['pending']);
        $this->assertStringContainsString('Signature Invalid', $result['message']);
    }

    public function test_webhook_signature_is_verified_with_member_secret_and_ref_id(): void
    {
        $service = app(TokovoucherService::class);

        $valid = md5('MEMBER-TEST:secret-key:ORDER-005');
        $this->assertTrue($service->verifyWebhookSignature('ORDER-005', $valid));
        $this->assertFalse($service->verifyWebhookSignature('ORDER-005', 'forged'));
        $this->assertFalse($service->verifyWebhookSignature('', $valid));
    }

    public function test_top_up_honors_dry_run_success_without_http(): void
    {
        config()->set('tokovoucher.dry_run', 'sukses');
        Http::preventStrayRequests();

        $result = app(TokovoucherService::class)
            ->topUp('FF5', '123456', '2001', 'ORDER-DRY-001');

        Http::assertNothingSent();

        $this->assertTrue($result['success']);
        $this->assertFalse($result['pending']);
        $this->assertSame('sukses', $result['status']);
        $this->assertSame('DRYRUN-ORDER-DRY-001', $result['data']['sn']);
        $this->assertTrue($result['data']['dry_run']);
    }

    public function test_top_up_honors_dry_run_pending_without_http(): void
    {
        config()->set('tokovoucher.dry_run', 'pending');
        Http::preventStrayRequests();

        $result = app(TokovoucherService::class)
            ->topUp('FF5', '123456', null, 'ORDER-DRY-002');

        Http::assertNothingSent();

        $this->assertFalse($result['success']);
        $this->assertTrue($result['pending']);
        $this->assertSame('pending', $result['status']);
    }

    public function test_top_up_honors_dry_run_failure_without_http(): void
    {
        config()->set('tokovoucher.dry_run', 'gagal');
        Http::preventStrayRequests();

        $result = app(TokovoucherService::class)
            ->topUp('FF5', '123456', null, 'ORDER-DRY-003');

        Http::assertNothingSent();

        $this->assertFalse($result['success']);
        $this->assertFalse($result['pending']);
        $this->assertSame('gagal', $result['status']);
    }

    public function test_sync_marks_game_and_non_game_categories_from_tokovoucher_category(): void
    {
        Cache::flush();
        config()->set('tokovoucher.cache_ttl', 3600);

        Http::fake([
            'https://api.tokovoucher.net/member/produk/full*' => Http::response([
                'status' => 1,
                'rc' => 200,
                'data' => [
                    'category' => [
                        ['id' => 1, 'nama' => 'Topup Game'],
                        ['id' => 4, 'nama' => 'Pulsa'],
                    ],
                    'operator' => [
                        ['id' => 10, 'nama' => 'Mobile Legends', 'category_id' => 1, 'logo' => '', 'status' => 1],
                        ['id' => 20, 'nama' => 'Pulsa Telkomsel', 'category_id' => 4, 'logo' => '', 'status' => 1],
                        ['id' => 30, 'nama' => 'Produk Nonaktif', 'category_id' => 1, 'logo' => '', 'status' => 1],
                    ],
                    'jenis' => [],
                    'produk' => [
                        [
                            'id' => 1,
                            'kode_produk' => 'ML100',
                            'nama' => 'ML 100',
                            'deskripsi' => '',
                            'price' => 10000,
                            'status' => 1,
                            'kategori_id' => 1,
                            'operator_id' => 10,
                            'jenis_id' => 1,
                        ],
                        [
                            'id' => 2,
                            'kode_produk' => 'TSEL10',
                            'nama' => 'Pulsa 10K',
                            'deskripsi' => '',
                            'price' => 10000,
                            'status' => 1,
                            'kategori_id' => 4,
                            'operator_id' => 20,
                            'jenis_id' => 1,
                        ],
                        [
                            'id' => 3,
                            'kode_produk' => 'PN001',
                            'nama' => 'Barang Rusak',
                            'deskripsi' => '',
                            'price' => 10000,
                            'status' => 1,
                            'kategori_id' => 1,
                            'operator_id' => 30,
                            'jenis_id' => 1,
                        ],
                    ],
                ],
            ]),
        ]);

        app(TokovoucherService::class)->syncProducts();

        $this->assertTrue(Category::where('slug', 'mobile-legends')->first()->is_game);
        $this->assertFalse(Category::where('slug', 'pulsa-telkomsel')->first()->is_game);
        $this->assertFalse(Category::where('slug', 'produk-nonaktif')->first()->is_game);
    }
}
