<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ServiceVariant extends Model
{
    use HasFactory;

    public const UNIT_KG = 'kg';

    public const UNIT_PCS = 'pcs';

    public const UNIT_METER = 'meter';

    protected $fillable = [
        'service_id',
        'name',
        'unit',
        'price_per_unit',
        'tat_duration_hours',
        'image_path',
        'note',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'price_per_unit' => 'decimal:2',
            'tat_duration_hours' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }
}
