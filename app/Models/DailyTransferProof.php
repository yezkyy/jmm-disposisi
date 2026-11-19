<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DailyTransferProof extends Model
{
    use HasFactory;

    protected $fillable = [
        'transfer_date',
        'title',
        'notes',
        'file_path',
        'file_name',
        'file_size',
        'uploaded_by',
    ];

    protected $casts = [
        'transfer_date' => 'date',
        'file_size' => 'integer',
    ];

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function getFileUrlAttribute(): string
    {
        return asset('storage/' . $this->file_path);
    }

    public function getFormattedFileSizeAttribute(): string
    {
        if (!$this->file_size) {
            return '-';
        }
        if ($this->file_size >= 1048576) {
            return number_format($this->file_size / 1048576, 1) . ' MB';
        }
        return number_format($this->file_size / 1024, 0) . ' KB';
    }
}
