<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FinancialTabCost extends Model
{
    protected $fillable = ['financial_tab_id', 'nama', 'nominal'];

    protected $casts = ['nominal' => 'decimal:2'];

    public function tab(): BelongsTo
    {
        return $this->belongsTo(FinancialTab::class, 'financial_tab_id');
    }
}
