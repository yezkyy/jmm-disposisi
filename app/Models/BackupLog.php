<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BackupLog extends Model
{
    use HasFactory;

    public $timestamps = false;

    public const TYPE_SQL = 'sql';
    public const TYPE_EXCEL = 'excel';
    public const TYPE_ATTACHMENTS = 'attachments';
    public const TYPE_ZIP = 'zip';

    protected $fillable = [
        'backup_type',
        'file_name',
        'file_path',
        'file_size',
        'status',
        'created_by',
        'started_at',
        'completed_at',
        'notes',
        'created_at',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'created_at' => 'datetime',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function getFormattedSizeAttribute(): string
    {
        $bytes = $this->file_size;
        if ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 2) . ' MB';
        } elseif ($bytes >= 1024) {
            return number_format($bytes / 1024, 2) . ' KB';
        }
        return $bytes . ' B';
    }
}

