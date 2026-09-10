<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class TransactionController extends Controller
{
    public function index(Request $request): View
    {
        $team = Auth::user()->currentTeam;

        $query = $team ? $team->transactions()->with('user') : Transaction::whereNull('id');

        if ($request->filled('search')) {
            $search = (string) $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('description', 'like', "%{$search}%")
                    ->orWhere('category', 'like', "%{$search}%");
            });
        }

        if ($request->filled('type')) {
            $query->where('type', $request->input('type'));
        }

        if ($request->filled('source')) {
            $query->where('source', $request->input('source'));
        }

        $transactions = $query->orderBy('transacted_at', 'desc')->paginate(15);

        return view('transactions.index', compact('transactions', 'team'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'type' => 'required|in:income,expense',
            'amount' => 'required|numeric|min:1',
            'description' => 'required|string|max:255',
            'category' => 'nullable|string|max:50',
            'transacted_at' => 'nullable|date',
        ]);

        $team = Auth::user()->currentTeam;
        abort_unless($team, 404);

        Transaction::create([
            'team_id' => $team->id,
            'user_id' => Auth::id(),
            'type' => $validated['type'],
            'amount' => $validated['amount'],
            'description' => $validated['description'],
            'category' => str_replace('#', '', $validated['category'] ?? ''),
            'source' => 'web',
            'transacted_at' => $validated['transacted_at'] ?? now(),
        ]);

        return redirect()
            ->route('transactions.index')
            ->with('success', 'Transaksi berhasil disimpan.');
    }

    public function exportCsv(Request $request)
    {
        $team = Auth::user()->currentTeam;
        abort_unless($team, 404);

        $query = $team->transactions()->with(['user', 'items.product']);

        if ($request->filled('search')) {
            $search = (string) $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('description', 'like', "%{$search}%")
                    ->orWhere('category', 'like', "%{$search}%");
            });
        }

        if ($request->filled('type')) {
            $query->where('type', $request->input('type'));
        }

        if ($request->filled('source')) {
            $query->where('source', $request->input('source'));
        }

        $transactions = $query->orderBy('transacted_at', 'desc')->get();

        $totalIncome = (float) $transactions->where('type', 'income')->sum('amount');
        $totalExpense = (float) $transactions->where('type', 'expense')->sum('amount');
        $netFlow = $totalIncome - $totalExpense;

        $safeTeamSlug = preg_replace('/[^a-zA-Z0-9_-]/', '_', $team->name);

        // Jika minta format excel bergaya outline tabel
        if ($request->query('format') === 'excel') {
            $filename = 'laporan_transaksi_'.$safeTeamSlug.'_'.now()->format('Ymd_His').'.xls';

            return response()->view('exports.transactions-excel', [
                'team' => $team,
                'transactions' => $transactions,
                'totalIncome' => $totalIncome,
                'totalExpense' => $totalExpense,
                'netFlow' => $netFlow,
                'filters' => [
                    'type' => $request->input('type') ?: 'Semua',
                    'source' => $request->input('source') ?: 'Semua',
                    'search' => $request->input('search') ?: 'Semua',
                ],
            ], 200, [
                'Content-Type' => 'application/vnd.ms-excel; charset=UTF-8',
                'Content-Disposition' => "attachment; filename=\"{$filename}\"",
                'Pragma' => 'no-cache',
                'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
                'Expires' => '0',
            ]);
        }

        // Default: CSV bersih dengan UTF-8 BOM dan header metadata
        $filename = 'laporan_transaksi_'.$safeTeamSlug.'_'.now()->format('Ymd_His').'.csv';

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ];

        $callback = function () use ($team, $transactions, $totalIncome, $totalExpense, $netFlow, $request) {
            $file = fopen('php://output', 'w');
            fwrite($file, "\xEF\xBB\xBF");

            $sanitize = static function (?string $value): string {
                if ($value === null || $value === '') {
                    return '-';
                }
                $trimmed = trim($value);
                if (in_array(substr($trimmed, 0, 1), ['=', '+', '-', '@'], true)) {
                    return "'".$trimmed;
                }

                return $trimmed;
            };

            fputcsv($file, ['========================================================================================================================']);
            fputcsv($file, ['                                      LAPORAN ARUS KEUANGAN & TRANSAKSI TRASK                                          ']);
            fputcsv($file, ['========================================================================================================================']);
            fputcsv($file, ['Nama Tim / Bisnis   :', $sanitize($team->name)]);
            fputcsv($file, ['Dicetak Oleh        :', $sanitize(Auth::user()->name.' ('.Auth::user()->email.')')]);
            fputcsv($file, ['Waktu Export        :', now()->translatedFormat('d F Y, H:i:s').' WIB']);
            fputcsv($file, ['Filter Transaksi    :', sprintf('Tipe: %s | Sumber: %s | Cari: %s', $request->input('type') ?: 'Semua', $request->input('source') ?: 'Semua', $request->input('search') ?: 'Semua')]);
            fputcsv($file, ['------------------------------------------------------------------------------------------------------------------------']);
            fputcsv($file, ['RINGKASAN']);
            fputcsv($file, ['  Total Transaksi   :', $transactions->count().' baris']);
            fputcsv($file, ['  Total Pemasukan   :', 'Rp '.number_format($totalIncome, 2, ',', '.')]);
            fputcsv($file, ['  Total Pengeluaran :', 'Rp '.number_format($totalExpense, 2, ',', '.')]);
            fputcsv($file, ['  Arus Kas Bersih   :', 'Rp '.number_format($netFlow, 2, ',', '.')]);
            fputcsv($file, ['========================================================================================================================']);
            fputcsv($file, []);

            fputcsv($file, [
                'No',
                'ID Transaksi',
                'Tanggal',
                'Jam (WIB)',
                'Tipe Transaksi',
                'Deskripsi',
                'Kategori',
                'Sumber Input',
                'Pencatat / Pengirim',
                'Pemasukan (IDR)',
                'Pengeluaran (IDR)',
                'Detail Item Produk',
            ]);

            $index = 1;
            foreach ($transactions as $tx) {
                $incomeCol = $tx->type === 'income' ? number_format((float) $tx->amount, 2, ',', '.') : '0,00';
                $expenseCol = $tx->type === 'expense' ? number_format((float) $tx->amount, 2, ',', '.') : '0,00';

                $itemDetails = $tx->items->map(function ($item) {
                    $name = $item->product?->name ?? 'Item';

                    return "{$name} (x{$item->quantity} @".number_format((float) $item->unit_price, 0, ',', '.').')';
                })->implode('; ');

                fputcsv($file, [
                    $index++,
                    '#TX-'.str_pad((string) $tx->id, 5, '0', STR_PAD_LEFT),
                    $tx->transacted_at->format('Y-m-d'),
                    $tx->transacted_at->format('H:i'),
                    $tx->type === 'income' ? 'Pemasukan' : 'Pengeluaran',
                    $sanitize($tx->description),
                    $sanitize($tx->category ? '#'.$tx->category : '-'),
                    $tx->source === 'whatsapp' ? 'WhatsApp Bot' : 'Web Dashboard',
                    $sanitize($tx->source === 'whatsapp' ? ($tx->whatsapp_sender ?? 'Bot') : ($tx->user?->name ?? 'Web')),
                    $incomeCol,
                    $expenseCol,
                    $itemDetails !== '' ? $itemDetails : '-',
                ]);
            }

            fputcsv($file, ['------------------------------------------------------------------------------------------------------------------------']);
            fputcsv($file, [
                '',
                '',
                '',
                '',
                'TOTAL',
                '',
                '',
                '',
                '',
                number_format($totalIncome, 2, ',', '.'),
                number_format($totalExpense, 2, ',', '.'),
                '',
            ]);
            fputcsv($file, ['========================================================================================================================']);

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}
