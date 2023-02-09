<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class ColumnCell extends Model
{
    use HasFactory;

    protected $fillable = [
        'column_id',
        'sub_position_row_id',
        'value',
        'linked_money_source_id',
        'linked_type',
        'verified_value',
        'commented'
    ];

    protected $casts = [
        'commented' => 'boolean'
    ];

    protected $primaryKey = 'id';

    protected $table = 'column_sub_position_row';

    public function subPositionRows(): BelongsToMany
    {
        return $this->belongsToMany(SubPositionRow::class);
    }

    public function column(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Column::class);
    }

    public function comments(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(CellComment::class);
    }

    public function calculations(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(CellCalculations::class,'cell_id');
    }
}