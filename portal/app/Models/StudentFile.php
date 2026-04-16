<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StudentFile extends Model
{
    protected $fillable = [
        'student_id', 'file_type', 'original_name', 'file_path',
        'file_size', 'mime_type', 'uploaded_by',
    ];

    protected function casts(): array
    {
        return ['file_size' => 'integer'];
    }

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function uploader()
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function humanSize(): string
    {
        $bytes = $this->file_size;
        if ($bytes < 1024) return "{$bytes} B";
        if ($bytes < 1048576) return round($bytes / 1024, 1) . ' KB';
        return round($bytes / 1048576, 1) . ' MB';
    }
}
