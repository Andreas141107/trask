<?php

namespace App\Http\Controllers;

use App\Models\Team;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class TeamController extends Controller
{
    public function index(): View
    {
        $user = Auth::user();
        $team = $user->currentTeam;

        if (! $team) {
            $team = $user->teams()->first();
            if ($team) {
                $user->update(['current_team_id' => $team->id]);
            }
        }

        if ($team) {
            $team->load('users');
        }

        $allTeams = $user->teams()->with('owner')->get();

        return view('teams.index', compact('team', 'allTeams'));
    }

    public function join(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'invite_code' => ['required', 'string', 'size:8'],
        ]);

        $team = Team::where('invite_code', strtoupper(trim($validated['invite_code'])))->first();

        if (! $team) {
            return back()->withErrors(['invite_code' => 'Kode undangan tidak ditemukan.']);
        }

        $user = Auth::user();
        $alreadyMember = $user->teams()->whereKey($team->id)->exists();

        if (! $alreadyMember) {
            $user->teams()->attach($team->id, ['role' => 'member']);
        }

        $user->update(['current_team_id' => $team->id]);

        return redirect()->route('teams.index')->with('success', 'Berhasil masuk ke tim '.$team->name.'.');
    }

    public function switchTeam(Request $request, Team $team): RedirectResponse
    {
        $user = Auth::user();

        if (! $user->teams()->whereKey($team->id)->exists()) {
            return back()->with('error', 'Anda bukan anggota dari tim ini.');
        }

        $user->update(['current_team_id' => $team->id]);

        return redirect()->route('dashboard')->with('success', 'Berhasil beralih ke tim '.$team->name.'.');
    }
}
