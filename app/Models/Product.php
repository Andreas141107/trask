<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'team_id',
        'sku',
        'name',
        'description',
        'stock_initial',
        'stock_current',
        'stock_minimum',
        'capital_price',
        'selling_price',
        'category',
    ];

    protected function casts(): array
    {
        return [
            'stock_initial' => 'integer',
            'stock_current' => 'integer',
            'stock_minimum' => 'integer',
            'capital_price' => 'decimal:2',
            'selling_price' => 'decimal:2',
        ];
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function transactionItems(): HasMany
    {
        return $this->hasMany(TransactionItem::class);
    }
}
