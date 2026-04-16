<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name', 'email', 'password', 'role', 'is_active', 'last_login_at',
    ];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return [
            'password'      => 'hashed',
            'is_active'     => 'boolean',
            'last_login_at' => 'datetime',
        ];
    }

    public function reviewerAssignments()
    {
        return $this->hasMany(ReviewerAssignment::class);
    }

    public function assignedCategories()
    {
        return $this->belongsToMany(Category::class, 'reviewer_assignments', 'user_id', 'category_id')
                    ->withTimestamps();
    }

    public function reviews()
    {
        return $this->hasMany(Review::class, 'reviewer_id');
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function isReviewer(): bool
    {
        return $this->role === 'reviewer';
    }

    public function canAccessCategory(int $categoryId): bool
    {
        if ($this->isAdmin()) {
            return true;
        }
        return $this->assignedCategories()->where('categories.id', $categoryId)->exists();
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeReviewers($query)
    {
        return $query->where('role', 'reviewer');
    }
}
