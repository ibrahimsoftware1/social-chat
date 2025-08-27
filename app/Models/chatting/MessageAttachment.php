<?php

namespace App\Models\chatting;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class MessageAttachment extends Model
{
    use HasFactory;

    protected $fillable = [
        'message_id',
        'user_id',
        'file_name',
        'file_path',
        'file_type',
        'file_size',
        'file_url',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
        'file_size' => 'integer',
    ];

    protected $appends = ['full_url', 'size_formatted'];

    // Relationships
    public function message()
    {
        return $this->belongsTo(Message::class, 'message_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class,'user_id');
    }

    // Accessors
    public function getFullUrlAttribute()
    {
        return $this->file_url ?: Storage::url($this->file_path);
    }

    public function getSizeFormattedAttribute()
    {
        $bytes = $this->file_size;
        $units = ['B', 'KB', 'MB', 'GB'];

        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }

        return round($bytes, 2) . ' ' . $units[$i];
    }

    // Helpers
    public function isImage()
    {
        return str_starts_with($this->file_type, 'image/');
    }

    public function isVideo()
    {
        return str_starts_with($this->file_type, 'video/');
    }

    public function isAudio()
    {
        return str_starts_with($this->file_type, 'audio/');
    }
}
