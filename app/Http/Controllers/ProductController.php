<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Services\FinancialMetricService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class ProductController extends Controller
{
    public function index(Request $request): View
    {
        $team = Auth::user()->currentTeam;

        $query = $team->products();

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('sku', 'like', "%{$search}%");
            });
        }

        if ($request->filled('category')) {
            $query->where('category', $request->input('category'));
        }

        $products = $query->orderBy('stock_current')->get();

        $metrics = new FinancialMetricService($team);

        return view('products.index', compact('products', 'team', 'metrics'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'sku' => 'required|string|max:50|unique:products,sku',
            'name' => 'required|string|max:100',
            'description' => 'nullable|string',
            'stock_initial' => 'required|integer|min:0',
            'stock_minimum' => 'required|integer|min:0',
            'capital_price' => 'required|numeric|min:0',
            'selling_price' => 'required|numeric|min:0',
            'category' => 'nullable|string|max:50',
        ]);

        $team = Auth::user()->currentTeam;

        Product::create([
            'team_id' => $team->id,
            'sku' => $validated['sku'],
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'stock_initial' => $validated['stock_initial'],
            'stock_current' => $validated['stock_initial'],
            'stock_minimum' => $validated['stock_minimum'],
            'capital_price' => $validated['capital_price'],
            'selling_price' => $validated['selling_price'],
            'category' => $validated['category'] ?? null,
        ]);

        return redirect()
            ->route('products.index')
            ->with('success', 'Produk berhasil ditambahkan.');
    }

    public function update(Request $request, Product $product): RedirectResponse
    {
        $this->authorizeTeam($product);
        $validated = $request->validate([
            'name' => 'sometimes|string|max:100',
            'description' => 'sometimes|nullable|string',
            'stock_current' => 'sometimes|integer|min:0',
            'stock_minimum' => 'sometimes|integer|min:0',
            'capital_price' => 'sometimes|numeric|min:0',
            'selling_price' => 'sometimes|numeric|min:0',
            'category' => 'sometimes|nullable|string|max:50',
        ]);

        $product->update($validated);

        return redirect()
            ->route('products.index')
            ->with('success', 'Produk berhasil diperbarui.');
    }

    protected function authorizeTeam(Product $product): void
    {
        $teamId = Auth::user()->current_team_id;
        if ($product->team_id !== $teamId) {
            abort(403, 'Unauthorized');
        }
    }
}
