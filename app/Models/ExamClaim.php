<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\ExamClaimFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['user_id', 'exam_id', 'exam_title', 'kind', 'terms', 'instagram_url', 'instagram_username', 'customer_note', 'proof_paths', 'status', 'review_note', 'reviewed_by', 'reviewed_at'])]
class ExamClaim extends Model
{
    /** @use HasFactory<ExamClaimFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return ['proof_paths' => 'array', 'reviewed_at' => 'datetime'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class)->withTrashed();
    }

    public function exam(): BelongsTo
    {
        return $this->belongsTo(Exam::class)->withTrashed();
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by')->withTrashed();
    }

    public function statusLabel(): string
    {
        return match ($this->status) {
            'approved' => 'Sudah terbuka',
            'rejected' => 'Ditolak',
            default => 'Menunggu verifikasi',
        };
    }
}
