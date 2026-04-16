<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Review extends Model
{
    protected $fillable = [
        'student_id', 'reviewer_id', 'status', 'overall_remarks',
        'started_at', 'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'started_at'   => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function reviewer()
    {
        return $this->belongsTo(User::class, 'reviewer_id');
    }

    public function scores()
    {
        return $this->hasMany(ReviewScore::class);
    }

    public function totalScore(): float
    {
        return (float) $this->scores()->whereNotNull('score')->sum('score');
    }

    public function isPending(): bool   { return $this->status === 'pending'; }
    public function isInProgress(): bool { return $this->status === 'in_progress'; }
    public function isCompleted(): bool { return $this->status === 'completed'; }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeForReviewer($query, int $reviewerId)
    {
        return $query->where('reviewer_id', $reviewerId);
    }
}
