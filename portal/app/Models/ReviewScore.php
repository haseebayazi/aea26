<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReviewScore extends Model
{
    protected $fillable = ['review_id', 'rubric_item_id', 'score', 'remarks'];

    protected function casts(): array
    {
        return ['score' => 'float'];
    }

    public function review()
    {
        return $this->belongsTo(Review::class);
    }

    public function rubricItem()
    {
        return $this->belongsTo(RubricItem::class);
    }
}
