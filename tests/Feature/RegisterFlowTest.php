<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegisterFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_register_form_action_matches_app_url_scheme(): void
    {
        $response = $this->get('/register');

        $response->assertOk();

        preg_match('/<form[^>]*action="([^"]+)"/', $response->getContent(), $matches);

        $this->assertSame(config('app.url').'/register', $matches[1] ?? null);
        $this->assertStringStartsWith('https://', $matches[1] ?? '');
    }

    public function test_user_can_register(): void
    {
        $this
            ->post('/register', [
                'name' => 'Bambang',
                'email' => 'bambang@example.com',
                'phone' => '08123456789',
                'password' => 'rahasia123',
                'password_confirmation' => 'rahasia123',
            ])
            ->assertRedirect(route('home'));

        $this->assertDatabaseHas('users', [
            'email' => 'bambang@example.com',
            'phone' => '08123456789',
        ]);

        $this->assertAuthenticated();
    }

    public function test_register_validates_unique_email(): void
    {
        $this->post('/register', [
            'name' => 'Bambang',
            'email' => 'bambang@example.com',
            'password' => 'rahasia123',
            'password_confirmation' => 'rahasia123',
        ]);

        $this
            ->post('/register', [
                'name' => 'Bambang Lagi',
                'email' => 'bambang@example.com',
                'password' => 'rahasia123',
                'password_confirmation' => 'rahasia123',
            ])
            ->assertRedirect()
            ->assertSessionHasErrors('email');

        $this->assertDatabaseCount('users', 1);
    }

    public function test_trusted_proxy_header_marks_request_secure(): void
    {
        $this
            ->withServerVariables(['HTTP_X_FORWARDED_PROTO' => 'https'])
            ->get('/login');

        $this->assertTrue($this->app['request']->isSecure());
    }

    public function test_trusted_proxies_are_registered(): void
    {
        $this->assertNotSame([], $this->app['request']->getTrustedProxies());
    }
}
