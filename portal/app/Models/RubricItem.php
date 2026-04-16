<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RubricItem extends Model
{
    protected $fillable = [
        'rubric_type', 'dimension', 'dimension_label', 'dimension_weight',
        'sub_indicator_key', 'sub_indicator_label', 'max_score', 'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'max_score'        => 'float',
            'dimension_weight' => 'integer',
            'sort_order'       => 'integer',
        ];
    }

    public function selfScores()
    {
        return $this->hasMany(SelfScore::class);
    }

    public function reviewScores()
    {
        return $this->hasMany(ReviewScore::class);
    }

    public function scopeCaac($query)
    {
        return $query->where('rubric_type', 'caac');
    }

    public function scopeForDimension($query, string $dimension)
    {
        return $query->where('dimension', $dimension);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order');
    }
}
