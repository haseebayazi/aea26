<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SelfScore extends Model
{
    protected $fillable = ['student_id', 'rubric_item_id', 'score', 'remarks'];

    protected function casts(): array
    {
        return ['score' => 'float'];
    }

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function rubricItem()
    {
        return $this->belongsTo(RubricItem::class);
    }
}
