<?php

namespace App\Http\Controllers;

use App\Models\Capital;
use App\Services\FinancialMetricService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class CapitalController extends Controller
{
    public function index(): View
    {
        $team = Auth::user()->currentTeam;

        $capitals = $team->capitals()
            ->with('user')
            ->orderBy('created_at', 'desc')
            ->get();

        $metrics = new FinancialMetricService($team);

        return view('capitals.index', compact('capitals', 'team', 'metrics'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'type' => 'required|in:awal,tetap',
            'amount' => 'required|numeric|min:1',
            'description' => 'required|string|max:255',
        ]);

        $team = Auth::user()->currentTeam;

        Capital::create([
            'team_id' => $team->id,
            'user_id' => Auth::id(),
            'type' => $validated['type'],
            'amount' => $validated['amount'],
            'description' => $validated['description'],
        ]);

        return redirect()
            ->route('capitals.index')
            ->with('success', 'Data modal/beban berhasil disimpan.');
    }
}
