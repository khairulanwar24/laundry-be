<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaymentMethod extends Model
{
    use HasFactory;

    public const CATEGORY_CASH = 'cash';

    public const CATEGORY_TRANSFER = 'transfer';

    public const CATEGORY_E_WALLET = 'e_wallet';

    protected $fillable = [
        'outlet_id',
        'category',
        'name',
        'logo',
        'owner_name',
        'tags',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'tags' => 'array',
            'is_active' => 'boolean',
        ];
    }

    public function outlet(): BelongsTo
    {
        return $this->belongsTo(Outlet::class);
    }
}
