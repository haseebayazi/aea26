<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ActivityLog extends Model
{
    protected $fillable = [
        'user_id', 'action', 'subject_type', 'subject_id', 'details', 'ip_address',
    ];

    protected function casts(): array
    {
        return ['details' => 'array'];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public static function record(string $action, $subject = null, array $details = []): void
    {
        $userId = auth()->id();
        static::create([
            'user_id'      => $userId,
            'action'       => $action,
            'subject_type' => $subject ? get_class($subject) : null,
            'subject_id'   => $subject?->id,
            'details'      => $details ?: null,
            'ip_address'   => request()->ip(),
        ]);
    }
}
