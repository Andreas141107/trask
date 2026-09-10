<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Team;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules;
use Illuminate\View\View;

class RegisteredUserController extends Controller
{
    public function create(): View
    {
        return view('auth.register');
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'invite_code' => ['nullable', 'string', 'size:8'],
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        // Prioritaskan gabung tim via invite code bila diisi saat registrasi
        if ($request->filled('invite_code')) {
            $invitedTeam = Team::where('invite_code', strtoupper(trim($request->invite_code)))->first();

            if (! $invitedTeam) {
                $user->delete();

                return back()->withErrors(['invite_code' => 'Kode undangan tidak ditemukan.'])->withInput();
            }

            $user->update(['current_team_id' => $invitedTeam->id]);
            $user->teams()->attach($invitedTeam->id, ['role' => 'member']);
        } else {
            // Buat tim otomatis untuk user baru sebagai owner
            $team = Team::create([
                'owner_id' => $user->id,
                'name' => 'Tim '.$user->name,
                'invite_code' => strtoupper(Str::random(8)),
                'description' => 'Tim bisnis utama '.$user->name,
            ]);

            $user->update(['current_team_id' => $team->id]);
            $user->teams()->attach($team->id, ['role' => 'owner']);
        }

        event(new Registered($user));

        Auth::login($user);

        return redirect()->route('dashboard');
    }
}
