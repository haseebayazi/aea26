<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    protected $fillable = ['name', 'slug', 'color', 'description', 'sort_order'];

    public function students()
    {
        return $this->hasMany(Student::class);
    }

    public function reviewerAssignments()
    {
        return $this->hasMany(ReviewerAssignment::class);
    }

    public function assignedReviewers()
    {
        return $this->belongsToMany(User::class, 'reviewer_assignments', 'category_id', 'user_id')
                    ->withTimestamps();
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order');
    }
}
