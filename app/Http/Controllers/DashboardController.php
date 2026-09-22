<?php

namespace App\Http\Controllers;

use App\Models\Team;
use App\Models\Transaction;
use App\Services\FinancialMetricService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        $user = Auth::user();
        $team = $user->currentTeam;
        if (! $team) {
            $team = $user->teams()->with('users')->first();
            if ($team) {
                $user->update(['current_team_id' => $team->id]);
                $team = $team->fresh();
            } else {
                // Buat tim default otomatis untuk user tanpa tim
                $team = Team::create([
                    'owner_id' => $user->id,
                    'name' => 'Tim '.$user->name,
                    'invite_code' => strtoupper(Str::random(8)),
                    'description' => 'Tim bisnis utama',
                ]);
                $user->update(['current_team_id' => $team->id]);
                $user->teams()->attach($team->id, ['role' => 'owner']);
            }
        }

        $metrics = new FinancialMetricService($team);

        // Weekly cashflow for chart (last 7 days)
        $cashflowDays = [];
        for ($i = 6; $i >= 0; $i--) {
            $day = now()->subDays($i)->format('Y-m-d');
            $cashflowDays[$day] = [
                'income' => $team->transactions()
                    ->where('type', 'income')
                    ->whereDate('transacted_at', $day)
                    ->sum('amount'),
                'expense' => $team->transactions()
                    ->where('type', 'expense')
                    ->whereDate('transacted_at', $day)
                    ->sum('amount'),
            ];
        }

        // Recent transactions (last 10)
        $recentTransactions = $team->transactions()
            ->with('user')
            ->orderBy('transacted_at', 'desc')
            ->limit(10)
            ->get();

        // Low stock products (stock_current <= stock_minimum)
        $lowStockProducts = $team->products()
            ->whereColumn('stock_current', '<=', 'stock_minimum')
            ->orderBy('stock_current')
            ->get();

        // Recent stock changes (via transaction items)
        $recentStockChanges = $team->products()
            ->whereHas('transactionItems', function ($q) {
                $q->whereHas('transaction', function ($q2) {
                    $q2->where('type', 'income');
                });
            })
            ->with(['transactionItems' => function ($q) {
                $q->limit(3);
            }])
            ->limit(3)
            ->get();

        // Telegram bot status
        $telegramActive = $team->telegramBot()->exists();

        return view('dashboard', [
            'team' => $team,
            'metrics' => $metrics,
            'cashflowDays' => $cashflowDays,
            'recentTransactions' => $recentTransactions,
            'lowStockProducts' => $lowStockProducts,
            'telegramActive' => $telegramActive,
            'telegramLogsCount' => $team->telegramLogs()->count(),
        ]);
    }
}
