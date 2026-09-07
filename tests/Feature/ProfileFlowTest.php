<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ProfileFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_update_profile(): void
    {
        $user = User::factory()->create(['name' => 'Old Name']);

        $this->actingAs($user)->put('/profile', [
            'name' => 'New Name',
            'email' => $user->email,
            'phone' => '08123',
        ])->assertSessionHas('success');

        $this->assertSame('New Name', $user->fresh()->name);
        $this->assertSame('08123', $user->fresh()->phone);
    }

    public function test_user_can_change_password_with_correct_current_password(): void
    {
        $user = User::factory()->create(['password' => 'old-password']);

        $this->actingAs($user)->put('/profile', [
            'name' => $user->name,
            'email' => $user->email,
            'current_password' => 'old-password',
            'password' => 'new-password123',
            'password_confirmation' => 'new-password123',
        ])->assertSessionHas('success');

        $this->assertTrue(Hash::check('new-password123', $user->fresh()->password));
    }

    public function test_cannot_change_password_with_wrong_current_password(): void
    {
        $user = User::factory()->create(['password' => 'old-password']);

        $this->actingAs($user)->put('/profile', [
            'name' => $user->name,
            'email' => $user->email,
            'current_password' => 'wrong',
            'password' => 'new-password123',
            'password_confirmation' => 'new-password123',
        ])->assertSessionHasErrors('current_password');

        $this->assertTrue(Hash::check('old-password', $user->fresh()->password));
    }
}
