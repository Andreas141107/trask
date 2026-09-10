<?php

namespace Tests\Feature\Auth;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\InvalidStateException;
use Mockery;
use Tests\TestCase;

class GoogleLoginTest extends TestCase
{
    use RefreshDatabase;

    public function test_google_redirect_failure_returns_to_login_with_error(): void
    {
        Socialite::shouldReceive('driver')->with('google')->andThrow(new \Exception('Simulated Google API error'));

        $response = $this->get('/auth/google/redirect');

        $response->assertRedirect(route('login'));
        $response->assertSessionHas('error', 'Gagal login via Google. Silakan coba lagi.');
    }

    public function test_google_callback_failure_returns_to_login_with_error(): void
    {
        $provider = Mockery::mock();
        $provider->shouldReceive('stateless')->andReturnSelf();
        $provider->shouldReceive('user')->andThrow(new InvalidStateException('Invalid state'));
        Socialite::shouldReceive('driver')->with('google')->andReturn($provider);

        $response = $this->get('/auth/google/callback');

        $response->assertRedirect(route('login'));
        $response->assertSessionHas('error', 'Gagal login via Google. Silakan coba lagi.');
        $this->assertGuest();
    }
}
