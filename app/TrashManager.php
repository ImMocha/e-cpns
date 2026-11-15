<?php

declare(strict_types=1);

namespace App;

use App\Models\Article;
use App\Models\Exam;
use App\Models\ExamAttempt;
use App\Models\ExamClaim;
use App\Models\Material;
use App\Models\Package;
use App\Models\Question;
use App\Models\Section;
use App\Models\Testimonial;
use App\Models\Topic;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Voucher;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class TrashManager
{
    /** @var array<string, array{0: class-string<Model>, 1: string, 2: string}> */
    public const RESOURCES = [
        'topics' => [Topic::class, 'Topik', 'name'],
        'materials' => [Material::class, 'Materi', 'title'],
        'exams' => [Exam::class, 'Tryout & Latihan', 'title'],
        'questions' => [Question::class, 'Bank soal', 'title'],
        'sections' => [Section::class, 'Bidang', 'name'],
        'articles' => [Article::class, 'Artikel', 'title'],
        'packages' => [Package::class, 'Paket', 'name'],
        'users' => [User::class, 'Pengguna', 'name'],
        'testimonials' => [Testimonial::class, 'Testimoni', 'name'],
        'vouchers' => [Voucher::class, 'Voucher', 'code'],
        'transactions' => [Transaction::class, 'Transaksi', 'order_id'],
        'exam_attempts' => [ExamAttempt::class, 'Riwayat pengerjaan', 'title'],
        'exam_claims' => [ExamClaim::class, 'Unlock sesi gratis', 'exam_title'],
    ];

    /** Ownership flows downward; shared bank questions and audit authors are not owned children. */
    private const CHILDREN = [
        'sections' => ['topics' => 'section_id', 'materials' => 'section_id', 'questions' => 'section_id', 'articles' => 'section_id'],
        'topics' => ['materials' => 'topic_id', 'exams' => 'topic_id'],
        'materials' => ['material_progress' => 'material_id', 'material_package' => 'material_id', 'material_user_package' => 'material_id'],
        'exams' => ['exam_claims' => 'exam_id', 'exam_question' => 'exam_id', 'exam_package' => 'exam_id', 'exam_user_package' => 'exam_id'],
        'questions' => ['exam_question' => 'question_id'],
        'articles' => ['article_views' => 'article_id'],
        'packages' => ['user_packages' => 'package_id', 'transactions' => 'package_id', 'vouchers' => 'package_id', 'package_claims' => 'package_id', 'material_package' => 'package_id', 'exam_package' => 'package_id'],
        'users' => ['user_packages' => 'user_id', 'transactions' => 'user_id', 'exam_attempts' => 'user_id', 'exam_claims' => 'user_id', 'package_claims' => 'user_id', 'material_progress' => 'user_id', 'sessions' => 'user_id'],
        'user_packages' => ['transactions' => 'user_package_id', 'exam_attempts' => 'user_package_id', 'package_claims' => 'user_package_id', 'material_user_package' => 'user_package_id', 'exam_user_package' => 'user_package_id'],
        'vouchers' => ['transactions' => 'voucher_id'],
    ];

    public function resolve(string $type, int $id, bool $lock = false): Model
    {
        abort_unless(isset(self::RESOURCES[$type]), 404);

        return self::RESOURCES[$type][0]::withTrashed()->when($lock, fn ($query) => $query->lockForUpdate())->findOrFail($id);
    }

    /** @return array<string, array{table: string, key: array<string, mixed>, row: array<string, mixed>}> */
    public function impact(Model $model, bool $lock = false): array
    {
        $nodes = [];
        $this->visit($model->getTable(), $model->getRawOriginal(), $nodes, $lock);
        $examIds = collect($nodes)->where('table', 'exams')->pluck('row.id')->all();
        if ($examIds !== []) {
            $questionIds = DB::table('exam_question')->whereIn('exam_id', $examIds)->pluck('question_id');
            $questions = DB::table('questions')->whereIn('id', $questionIds)->orderBy('id')->when($lock, fn ($query) => $query->lockForUpdate())->get();
            $sharedIds = DB::table('exam_question')->whereIn('question_id', $questionIds)->whereNotIn('exam_id', $examIds)
                ->when($lock, fn ($query) => $query->lockForUpdate())->pluck('question_id');
            foreach ($questions as $question) {
                if (! $sharedIds->contains($question->id)) {
                    $this->visit('questions', (array) $question, $nodes, $lock);
                }
            }
        }

        return $nodes;
    }

    /** @param array<string, array{table: string, key: array<string, mixed>, row: array<string, mixed>}> $nodes */
    private function visit(string $table, array $row, array &$nodes, bool $lock): void
    {
        $key = isset($row['id']) ? ['id' => $row['id']] : $row;
        $identity = $table.':'.json_encode($key);
        if (isset($nodes[$identity])) {
            return;
        }
        foreach (self::CHILDREN[$table] ?? [] as $child => $column) {
            $query = DB::table($child)->where($column, $row['id']);
            foreach ($query->when($lock, fn ($query) => $query->lockForUpdate())->get() as $record) {
                $this->visit($child, (array) $record, $nodes, $lock);
            }
        }
        if ($table === 'exams') {
            $query = DB::table('exam_attempts')->where('kind', $row['kind'])->where('bank_slug', $row['slug']);
            foreach ($query->when($lock, fn ($query) => $query->lockForUpdate())->get() as $record) {
                $this->visit('exam_attempts', (array) $record, $nodes, $lock);
            }
        }
        if ($table === 'users') {
            foreach (DB::table('password_reset_tokens')->where('email', $row['email'])->get() as $record) {
                $this->visit('password_reset_tokens', (array) $record, $nodes, $lock);
            }
        }
        $nodes[$identity] = ['table' => $table, 'key' => $key, 'row' => $row];
    }

    /** @param array<string, array{table: string, key: array<string, mixed>, row: array<string, mixed>}> $nodes */
    public function fingerprint(array $nodes): string
    {
        ksort($nodes);

        return hash_hmac('sha256', json_encode($nodes, JSON_THROW_ON_ERROR), config('app.key'));
    }

    public function archive(Model $model): void
    {
        DB::transaction(function () use ($model): void {
            $model = $this->resolve($model->getTable(), (int) $model->getKey(), true);
            abort_if($model->trashed(), 409);
            $items = [];
            $nodes = in_array($model->getTable(), ['topics', 'sections'], true) ? $this->impact($model, true) : [
                ['table' => $model->getTable(), 'row' => $model->getRawOriginal()],
            ];
            foreach ($nodes as $node) {
                if (isset(self::RESOURCES[$node['table']]) && $node['row']['deleted_at'] === null) {
                    $isRoot = $node['table'] === $model->getTable() && (int) $node['row']['id'] === (int) $model->id;
                    if (! $isRoot && isset($node['row']['topic_id']) && Topic::onlyTrashed()->whereKey($node['row']['topic_id'])->exists()) {
                        continue;
                    }
                    if (! $isRoot && $node['table'] === 'exam_attempts' && Exam::onlyTrashed()->where('kind', $node['row']['kind'])->where('slug', $node['row']['bank_slug'])->exists()) {
                        continue;
                    }
                    $record = $this->resolve($node['table'], (int) $node['row']['id'], true);
                    $record->delete();
                    $items[] = ['type' => $node['table'], 'id' => $record->id, 'deleted_at' => $record->getRawOriginal('deleted_at')];
                }
            }
            DB::table('trash_batches')->updateOrInsert(['resource' => $model->getTable(), 'record_id' => $model->id], ['items' => json_encode($items), 'created_at' => now()]);
        });
    }

    public function restore(Model $model): void
    {
        DB::transaction(function () use ($model): void {
            $model = $this->resolve($model->getTable(), (int) $model->id, true);
            abort_unless($model->trashed(), 409);
            $batch = DB::table('trash_batches')->where('resource', $model->getTable())->where('record_id', $model->id)->lockForUpdate()->first();
            $items = $batch ? json_decode($batch->items, true) : [['type' => $model->getTable(), 'id' => $model->id, 'deleted_at' => $model->getRawOriginal('deleted_at')]];
            $restoring = collect($items)->map(fn (array $item): string => $item['type'].':'.$item['id'])->all();
            foreach ($items as $item) {
                $record = self::RESOURCES[$item['type']][0]::onlyTrashed()->whereKey($item['id'])->where('deleted_at', $item['deleted_at'])->lockForUpdate()->first();
                if (! $record) {
                    continue;
                }
                if ($record instanceof ExamAttempt) {
                    $parentExam = Exam::onlyTrashed()->where('kind', $record->kind)->where('slug', $record->bank_slug)->first();
                    if ($parentExam && ! in_array('exams:'.$parentExam->id, $restoring, true)) {
                        throw ValidationException::withMessages(['restore' => 'Pulihkan sesi ujian terlebih dahulu.']);
                    }
                }
                foreach (['section_id' => 'sections', 'topic_id' => 'topics', 'user_id' => 'users', 'package_id' => 'packages', 'exam_id' => 'exams'] as $column => $parentType) {
                    $parentId = $record->getAttribute($column);
                    if ($parentId && ! in_array($parentType.':'.$parentId, $restoring, true) && self::RESOURCES[$parentType][0]::onlyTrashed()->whereKey($parentId)->exists()) {
                        throw ValidationException::withMessages(['restore' => 'Pulihkan data induk terlebih dahulu: '.self::RESOURCES[$parentType][1].'.']);
                    }
                }
                $record->restore();
            }
            DB::table('trash_batches')->where('resource', $model->getTable())->where('record_id', $model->id)->delete();
        });
    }

    public function destroy(Model $model, string $fingerprint): void
    {
        DB::transaction(function () use ($model, $fingerprint): void {
            $model = $this->resolve($model->getTable(), (int) $model->id, true);
            abort_unless($model->trashed(), 409);
            $nodes = $this->impact($model, true);
            if (! hash_equals($this->fingerprint($nodes), $fingerprint)) {
                throw ValidationException::withMessages(['impact' => 'Data terkait berubah. Buka ulang rincian dampak sebelum menghapus.']);
            }
            foreach ($nodes as $node) {
                DB::table($node['table'])->where($node['key'])->delete();
                if (isset($node['row']['id'])) {
                    DB::table('trash_batches')->where('resource', $node['table'])->where('record_id', $node['row']['id'])->delete();
                }
            }
            DB::afterCommit(function () use ($nodes): void {
                try {
                    $this->removeUnusedFiles($nodes);
                } catch (\Throwable $exception) {
                    report($exception);
                }
            });
        });
    }

    /** @param array<string, array{table: string, key: array<string, mixed>, row: array<string, mixed>}> $nodes */
    private function removeUnusedFiles(array $nodes): void
    {
        $columns = [
            'materials' => ['local', ['file_path']],
            'questions' => ['local', ['image_path', 'video_path', 'explanation_image_path', 'option_image_paths', 'explanation_inline_image_paths']],
            'exam_claims' => ['local', ['proof_paths']],
            'package_claims' => ['local', ['proof_paths']],
            'articles' => ['public', ['cover_path']],
            'exams' => ['public', ['cover_path']],
            'users' => ['public', ['avatar_path']],
        ];
        foreach ($nodes as $node) {
            if (! isset($columns[$node['table']])) {
                continue;
            }
            [$disk, $fields] = $columns[$node['table']];
            foreach ($fields as $field) {
                $value = $node['row'][$field] ?? null;
                $paths = str_ends_with($field, '_paths') ? json_decode($value ?? '[]', true) : [$value];
                foreach ($paths ?? [] as $path) {
                    if (! is_string($path) || $path === '' || str_contains($path, '..') || str_starts_with($path, '/')) {
                        continue;
                    }
                    $referenced = false;
                    foreach ($columns as $table => [$otherDisk, $otherFields]) {
                        if ($otherDisk !== $disk) {
                            continue;
                        }
                        foreach ($otherFields as $otherField) {
                            if (str_ends_with($otherField, '_paths')) {
                                $exists = DB::table($table)->whereJsonContains($otherField, $path)->exists();
                            } else {
                                $exists = DB::table($table)->where($otherField, $path)->exists();
                            }
                            $referenced = $referenced || $exists;
                        }
                    }
                    $referenced = $referenced || DB::table('exam_attempts')->where(function ($query) use ($path): void {
                        $query->where('question_snapshot', 'like', '%'.$path.'%')
                            ->orWhere('question_snapshot', 'like', '%'.str_replace('/', '\\/', $path).'%');
                    })->exists();
                    $referenced = $referenced || DB::table('articles')->where('body', 'like', '%'.$path.'%')->exists();
                    if (! $referenced) {
                        try {
                            if (! Storage::disk($disk)->delete($path)) {
                                report(new \RuntimeException('Berkas terhapus dari katalog tetapi gagal dibersihkan: '.$path));
                            }
                        } catch (\Throwable $exception) {
                            report($exception);
                        }
                    }
                }
            }
        }
    }
}
