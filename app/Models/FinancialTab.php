<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FinancialTab extends Model
{
    protected $fillable = ['nama', 'target_anggaran'];

    protected $casts = ['target_anggaran' => 'decimal:2'];

    public function costs(): HasMany
    {
        return $this->hasMany(FinancialTabCost::class);
    }

    public function keuangans(): HasMany
    {
        return $this->hasMany(Keuangan::class, 'financial_tab_id');
    }

    /** TOTAL BUDGET = target anggaran utama + seluruh biaya tambahan. */
    public function totalBudget(): float
    {
        return (float) $this->target_anggaran + (float) $this->costs->sum('nominal');
    }
}
