<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReviewerAssignment extends Model
{
    protected $fillable = ['user_id', 'category_id', 'assigned_by'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function assignedByUser()
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }
}
