<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Service extends Model
{
    use HasFactory;

    protected $fillable = [
        'outlet_id',
        'name',
        'priority_score',
        'process_steps_json',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'process_steps_json' => 'array',
            'is_active' => 'boolean',
        ];
    }

    public function outlet(): BelongsTo
    {
        return $this->belongsTo(Outlet::class);
    }

    public function serviceVariants(): HasMany
    {
        return $this->hasMany(ServiceVariant::class);
    }
}
