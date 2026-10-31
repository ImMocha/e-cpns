<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $fields = [
            'sections' => ['name', 'description'],
            'topics' => ['name', 'description'],
            'materials' => ['title', 'summary'],
            'articles' => ['title', 'excerpt', 'body'],
            'questions' => ['title', 'body', 'options', 'explanation'],
            'exams' => ['title', 'description'],
            'packages' => ['name', 'description'],
            'user_packages' => ['name'],
            'testimonials' => ['institution', 'content'],
            'exam_attempts' => ['title', 'question_snapshot'],
        ];

        DB::transaction(function () use ($fields): void {
            foreach ($fields as $table => $columns) {
                DB::table($table)->where(function (Builder $query) use ($columns): void {
                    foreach ($columns as $column) {
                        $query->orWhere($column, 'like', '%cpns%')->orWhere($column, 'like', '%pns%');
                    }
                })->chunkById(100, function (Collection $rows) use ($table, $columns): void {
                    foreach ($rows as $row) {
                        $changes = [];
                        foreach ($columns as $column) {
                            $original = $row->{$column};
                            $updated = match ($column) {
                                'options' => json_encode(array_map($this->normalizeText(...), json_decode($original, true, flags: JSON_THROW_ON_ERROR)), JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR),
                                'question_snapshot' => $this->normalizeSnapshot($original),
                                default => $original === null ? null : $this->normalizeText($original),
                            };
                            if ($updated !== $original) {
                                $changes[$column] = $updated;
                            }
                        }
                        if (isset($changes['name']) && $table === 'topics') {
                            $name = $changes['name'];
                            $suffix = '';
                            $counter = 0;
                            while (DB::table('topics')->where('section_id', $row->section_id)->where('name', $name.$suffix)->where('id', '!=', $row->id)->exists()) {
                                $suffix = ' ('.$row->id.($counter > 0 ? '-'.$counter : '').')';
                                $counter++;
                            }
                            $changes['name'] = $name.$suffix;
                        }
                        if ($changes !== []) {
                            DB::table($table)->where('id', $row->id)->update($changes);
                        }
                    }
                });
            }

            foreach ([
                'articles' => ['mulai-belajar-cpns-dengan-rencana', 'mulai-belajar-dengan-rencana'],
                'materials' => ['checklist-persiapan-seleksi-cpns', 'checklist-persiapan-ujian'],
            ] as $table => [$original, $updated]) {
                if (! DB::table($table)->where('slug', $updated)->exists()) {
                    DB::table($table)->where('slug', $original)->update(['slug' => $updated]);
                }
            }
        });
    }

    /** Content wording is intentionally retained on rollback. */
    public function down(): void {}

    private function normalizeText(string $text): string
    {
        $parts = preg_split('/(<[^>]+>)/u', $text, flags: PREG_SPLIT_DELIM_CAPTURE);

        return implode('', array_map(function (string $part): string {
            if (str_starts_with($part, '<')) {
                return $part;
            }
            $part = strtr($part, [
                'Persiapan Seleksi CPNS' => 'Persiapan Ujian',
                'persiapan seleksi cpns' => 'persiapan ujian',
                'Mulai Belajar CPNS dengan Rencana' => 'Mulai Belajar dengan Rencana',
                'alur belajar CPNS' => 'alur belajar',
                'bukan pembahasan CPNS asli' => 'bukan materi pembelajaran asli',
                'PNS-nya' => 'petugasnya',
            ]);

            return preg_replace(['/\bCPNS\b/iu', '/\bPNS\b/iu'], ['ujian', 'petugas'], $part);
        }, $parts));
    }

    private function normalizeSnapshot(string $json): string
    {
        $snapshot = json_decode($json, true, flags: JSON_THROW_ON_ERROR);
        foreach (['title', 'subtitle'] as $key) {
            if (is_string($snapshot[$key] ?? null)) {
                $snapshot[$key] = $this->normalizeText($snapshot[$key]);
            }
        }
        foreach ($snapshot['questions'] ?? [] as $index => $question) {
            foreach (['topic', 'stem', 'stemHtml', 'explain', 'explainHtml'] as $key) {
                if (is_string($question[$key] ?? null)) {
                    $snapshot['questions'][$index][$key] = $this->normalizeText($question[$key]);
                }
            }
            if (isset($question['options'])) {
                $snapshot['questions'][$index]['options'] = array_map($this->normalizeText(...), $question['options']);
            }
        }

        return json_encode($snapshot, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
    }
};
