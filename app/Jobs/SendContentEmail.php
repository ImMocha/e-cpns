<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Enums\MaterialAccess;
use App\Enums\UserRole;
use App\Models\Article;
use App\Models\Exam;
use App\Models\Material;
use App\Models\Package;
use App\Models\User;
use App\Notifications\AccountEmail;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Queue\Queueable;

class SendContentEmail implements ShouldQueue
{
    use Queueable;

    /** @param class-string<Exam|Material|Package|Article> $contentType */
    public function __construct(public string $contentType, public int $contentId) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $query = match ($this->contentType) {
            Exam::class => Exam::query()->visibleToMembers()->where('kind', 'tryout'),
            Material::class => Material::query()->visibleToMembers(),
            Package::class => Package::query()->active(),
            Article::class => Article::query()->visibleToMembers(),
        };
        $content = $query->find($this->contentId);
        if ($content === null || ($content instanceof Material && ! $content->hasAvailableContent())) {
            return;
        }

        [$preference, $headline, $intro, $url] = match ($this->contentType) {
            Exam::class => ['new_tryout', 'Tryout baru tersedia', $content->title, route('tryouts.index')],
            Material::class => ['updated_materials', 'Materi belajar diperbarui', $content->title, route('materials.show', $content)],
            Package::class => ['package_recommendations', 'Paket belajar tersedia', $content->name, route('packages.index')],
            Article::class => ['platform_announcements', 'Berita terbaru Ganesha Nusa', $content->title, route('articles.show', $content)],
        };

        $recipients = User::query()->active()->role(UserRole::User)->whereNotNull('email_verified_at');
        if (($content instanceof Material || $content instanceof Exam) && $content->access_level === MaterialAccess::Premium) {
            $relation = $content instanceof Material ? 'materials' : 'exams';
            $recipients->whereHas('userPackages', fn (Builder $grants): Builder => $grants->active()
                ->whereHas($relation, fn (Builder $items): Builder => $items->whereKey($content->getKey())));
        }

        $recipients->chunkById(100, function (Collection $users) use ($preference, $headline, $intro, $url): void {
            foreach ($users as $user) {
                if ($user->wantsEmailNotification($preference)) {
                    $user->notify(new AccountEmail($headline, $intro, 'Lihat selengkapnya', $url, $preference));
                }
            }
        });
    }
}
