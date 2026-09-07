<?php

namespace Tests\Unit;

use App\Services\DigiflazzService;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class DigiflazzServiceTest extends TestCase
{
    public function test_top_up_uses_sandbox_endpoint_and_signature(): void
    {
        config()->set('digiflazz.username', 'sandbox-user');
        config()->set('digiflazz.api_key', 'sandbox-key');
        config()->set('digiflazz.base_url', 'https://api.digiflazz.com/v1');
        config()->set('digiflazz.development', true);

        Http::fake([
            'https://api.digiflazz.com/v1/transaction' => Http::response([
                'data' => [
                    'rc' => '00',
                    'status' => 'Sukses',
                    'message' => 'Transaksi berhasil',
                ],
            ]),
        ]);

        $result = app(DigiflazzService::class)->topUp('ML-10', '123456(9876)', 'ORDER-001');

        Http::assertSent(function ($request): bool {
            return $request->url() === 'https://api.digiflazz.com/v1/transaction'
                && $request['username'] === 'sandbox-user'
                && $request['buyer_sku_code'] === 'ML-10'
                && $request['customer_no'] === '123456(9876)'
                && $request['ref_id'] === 'ORDER-001'
                && $request['sign'] === md5('sandbox-usersandbox-keyORDER-001')
                && $request['testing'] === true;
        });

        $this->assertTrue($result['success']);
        $this->assertFalse($result['pending']);
    }

    public function test_top_up_omits_testing_flag_when_production(): void
    {
        config()->set('digiflazz.username', 'prod-user');
        config()->set('digiflazz.api_key', 'prod-key');
        config()->set('digiflazz.base_url', 'https://api.digiflazz.com/v1');
        config()->set('digiflazz.development', false);

        Http::fake([
            'https://api.digiflazz.com/v1/transaction' => Http::response([
                'data' => [
                    'rc' => '00',
                    'status' => 'Sukses',
                    'message' => 'Transaksi berhasil',
                ],
            ]),
        ]);

        app(DigiflazzService::class)->topUp('ML-10', '123456', 'ORDER-003');

        Http::assertSent(fn ($request) => ! array_key_exists('testing', $request->data()));
    }

    public function test_balance_uses_cek_saldo_endpoint_and_depo_signature(): void
    {
        config()->set('digiflazz.username', 'sandbox-user');
        config()->set('digiflazz.api_key', 'sandbox-key');
        config()->set('digiflazz.base_url', 'https://api.digiflazz.com/v1');

        Http::fake([
            'https://api.digiflazz.com/v1/cek-saldo' => Http::response([
                'data' => ['deposit' => 500000],
            ]),
        ]);

        $balance = app(DigiflazzService::class)->getBalance();

        Http::assertSent(function ($request): bool {
            return $request->url() === 'https://api.digiflazz.com/v1/cek-saldo'
                && $request['cmd'] === 'deposit'
                && $request['sign'] === md5('sandbox-usersandbox-keydepo');
        });

        $this->assertSame(500000.0, $balance);
    }

    public function test_pending_top_up_is_not_reported_as_failed(): void
    {
        Http::fake([
            '*' => Http::response([
                'data' => [
                    'rc' => '03',
                    'status' => 'Pending',
                    'message' => 'Menunggu proses',
                ],
            ]),
        ]);

        $result = app(DigiflazzService::class)->topUp('ML-10', '123456', 'ORDER-002');

        $this->assertFalse($result['success']);
        $this->assertTrue($result['pending']);
        $this->assertSame('Pending', $result['status']);
    }
}
