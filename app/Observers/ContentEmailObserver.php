<?php

declare(strict_types=1);

namespace App\Observers;

use App\Enums\ArticleStatus;
use App\Enums\MaterialStatus;
use App\Enums\QuestionStatus;
use App\Jobs\SendContentEmail;
use App\Models\Article;
use App\Models\Exam;
use App\Models\Material;
use App\Models\Package;

class ContentEmailObserver
{
    public function created(Exam|Material|Package|Article $content): void
    {
        $this->queue($content);
    }

    public function updated(Exam|Material|Package|Article $content): void
    {
        $changedFields = $content instanceof Material
            ? ['status', 'title', 'summary', 'file_path', 'video_url', 'type']
            : ($content instanceof Package ? ['is_active'] : ['status']);

        if ($content->wasChanged($changedFields)) {
            $this->queue($content);
        }
    }

    private function queue(Exam|Material|Package|Article $content): void
    {
        $published = match (true) {
            $content instanceof Exam => $content->kind === 'tryout' && $content->status === QuestionStatus::Published,
            $content instanceof Material => $content->status === MaterialStatus::Published,
            $content instanceof Package => $content->is_active,
            $content instanceof Article => $content->status === ArticleStatus::Published,
        };
        if ($published && ($content->created_by !== null || $content->updated_by !== null)) {
            SendContentEmail::dispatch($content::class, (int) $content->getKey())->afterCommit();
        }
    }
}
