<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class TaxRate extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'state_code',
        'rate',
        'name',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'rate'      => 'decimal:4',
            'is_active' => 'boolean',
        ];
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByState($query, string $stateCode)
    {
        return $query->where('state_code', strtoupper($stateCode));
    }
}
