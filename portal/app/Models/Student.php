<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Student extends Model
{
    protected $fillable = [
        'submission_id', 'name', 'email', 'phone', 'batch', 'department',
        'campus', 'category_id', 'citation', 'additional_info', 'cv_path', 'citation_path',
    ];

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function selfScores()
    {
        return $this->hasMany(SelfScore::class);
    }

    public function reviews()
    {
        return $this->hasMany(Review::class);
    }

    public function files()
    {
        return $this->hasMany(StudentFile::class);
    }

    public function reviewByUser(int $userId)
    {
        return $this->reviews()->where('reviewer_id', $userId)->first();
    }

    public function selfScoreTotal(): float
    {
        return (float) $this->selfScores()->sum('score');
    }

    public function avgReviewerScore(): ?float
    {
        $completed = $this->reviews()->where('status', 'completed')->pluck('id');
        if ($completed->isEmpty()) {
            return null;
        }
        return ReviewScore::whereIn('review_id', $completed)->avg('score');
    }

    public function completedReviewsCount(): int
    {
        return $this->reviews()->where('status', 'completed')->count();
    }

    public function scopeForCategory($query, int $categoryId)
    {
        return $query->where('category_id', $categoryId);
    }

    public function scopeSearch($query, string $term)
    {
        return $query->where(function ($q) use ($term) {
            $q->where('name', 'like', "%{$term}%")
              ->orWhere('submission_id', 'like', "%{$term}%")
              ->orWhere('department', 'like', "%{$term}%");
        });
    }
}
