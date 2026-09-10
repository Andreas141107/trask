<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Team;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;

class GoogleAuthController extends Controller
{
    public function redirect(): RedirectResponse
    {
        try {
            $driver = Socialite::driver('google')
                ->scopes(['openid', 'profile', 'email']);

            // Simpan invite code sementara agar dipakai setelah callback bila user baru
            if (request()->filled('invite_code')) {
                session(['google_invite_code' => strtoupper(trim((string) request()->query('invite_code')))]);
            }

            return $driver->redirect();
        } catch (\Throwable $e) {
            report($e);

            return redirect()->route('login')->with('error', 'Gagal login via Google. Silakan coba lagi.');
        }
    }

    public function callback(): RedirectResponse
    {
        try {
            $googleUser = Socialite::driver('google')->stateless()->user();
        } catch (\Throwable $e) {
            report($e);

            return redirect()->route('login')->with('error', 'Gagal login via Google. Silakan coba lagi.');
        }

        $user = User::where('google_id', $googleUser->getId())
            ->orWhere('email', $googleUser->getEmail())
            ->first();

        if ($user) {
            if (! $user->google_id) {
                $user->update(['google_id' => $googleUser->getId()]);
            }
        } else {
            $user = User::create([
                'name' => $googleUser->getName() ?: $googleUser->getNickname() ?: 'Pengguna Trask',
                'email' => $googleUser->getEmail(),
                'google_id' => $googleUser->getId(),
                'password' => Hash::make(Str::random(40)),
            ]);

            $inviteCode = session()->pull('google_invite_code');
            $invitedTeam = $inviteCode ? Team::where('invite_code', $inviteCode)->first() : null;

            if ($invitedTeam) {
                $user->update(['current_team_id' => $invitedTeam->id]);
                $user->teams()->attach($invitedTeam->id, ['role' => 'member']);
            } else {
                $team = Team::create([
                    'owner_id' => $user->id,
                    'name' => 'Tim '.$user->name,
                    'invite_code' => strtoupper(Str::random(8)),
                    'description' => 'Tim bisnis utama '.$user->name,
                ]);

                $user->update(['current_team_id' => $team->id]);
                $user->teams()->attach($team->id, ['role' => 'owner']);
            }
        }

        Auth::login($user, remember: true);

        return redirect()->intended(route('dashboard'));
    }
}
