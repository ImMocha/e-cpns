<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Enums\ExamKind;
use App\Http\Controllers\Controller;
use App\TrashManager;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class TrashController extends Controller
{
    public function __construct(private TrashManager $trash) {}

    public function index(Request $request): View
    {
        $filters = $request->validate([
            'type' => ['nullable', Rule::in(['all', ...array_keys(TrashManager::RESOURCES)])],
            'state' => ['nullable', Rule::in(['trash', 'active'])],
            'q' => ['nullable', 'string', 'max:120'],
        ]);
        $type = $filters['type'] ?? 'all';
        $state = $filters['state'] ?? 'trash';
        $resources = TrashManager::RESOURCES;
        $selectedResources = $type === 'all' ? $resources : [$type => $resources[$type]];
        $categories = [];
        $total = 0;

        foreach ($selectedResources as $category => [$class, $label, $column]) {
            $query = $state === 'trash' ? $class::onlyTrashed() : $class::query();
            $records = $query->when($filters['q'] ?? '', fn ($query, $term) => $query->where($column, 'like', '%'.$term.'%'))
                ->orderByDesc('id')->paginate(20, ['*'], $type === 'all' ? $category.'_page' : 'page')->withQueryString();

            if ($records->total() > 0) {
                $categories[$category] = compact('label', 'column', 'records');
                $total += $records->total();
            }
        }

        return view('Dashboard.Admin.trash.index', compact('type', 'state', 'resources', 'categories', 'total'));
    }

    public function show(string $type, int $id): View
    {
        $record = $this->trash->resolve($type, $id);
        $nodes = $this->trash->impact($record);
        $labels = array_map(fn (array $resource): string => $resource[1], TrashManager::RESOURCES) + [
            'material_progress' => 'Progres belajar', 'material_package' => 'Relasi materi dalam paket',
            'material_user_package' => 'Hak akses materi peserta', 'exam_question' => 'Relasi soal dalam sesi',
            'exam_package' => 'Relasi sesi dalam paket', 'exam_user_package' => 'Hak akses sesi peserta',
            'user_packages' => 'Kepemilikan paket peserta', 'package_claims' => 'Klaim paket',
            'article_views' => 'Riwayat pembaca artikel', 'sessions' => 'Sesi login', 'password_reset_tokens' => 'Token reset kata sandi',
        ];
        $labels['questions'] = 'Soal di bank soal';
        $labels['materials'] = 'Materi pembelajaran';
        $related = collect($nodes)->reject(fn (array $node): bool => $node['table'] === $type && (int) ($node['row']['id'] ?? 0) === $id);
        $groups = $related->groupBy(function (array $node) use ($labels): string {
            $table = $node['table'];
            if ($table === 'exams') {
                return ExamKind::tryFrom($node['row']['kind'] ?? '')?->label() ?? 'Sesi ujian';
            }

            return $labels[$table] ?? $table;
        });

        $previewRows = $groups->flatMap(fn ($group) => $group->take(20))->pluck('row');
        $names = [];
        foreach ([
            'material_id' => ['materials', 'title'],
            'exam_id' => ['exams', 'title'],
            'question_id' => ['questions', 'title'],
            'package_id' => ['packages', 'name'],
            'user_id' => ['users', 'name'],
        ] as $key => [$table, $column]) {
            $ids = $previewRows->pluck($key)->filter()->unique();
            $names[$key] = $ids->isEmpty() ? collect() : DB::table($table)->whereIn('id', $ids)->pluck($column, 'id');
        }

        $ownershipIds = $previewRows->pluck('user_package_id')->filter()->unique();
        $ownerships = $ownershipIds->isEmpty() ? collect() : DB::table('user_packages')
            ->leftJoin('users', 'users.id', '=', 'user_packages.user_id')
            ->whereIn('user_packages.id', $ownershipIds)
            ->get(['user_packages.id', 'user_packages.name as package_name', 'users.name as participant_name'])
            ->keyBy('id');

        return view('Dashboard.Admin.trash.show', compact('record', 'type', 'groups', 'names', 'ownerships') + [
            'title' => $record->getAttribute(TrashManager::RESOURCES[$type][2]),
            'rootLabel' => $type === 'exams' ? (ExamKind::tryFrom($record->getRawOriginal('kind') ?? '')?->label() ?? 'Sesi ujian') : $labels[$type],
            'relatedCount' => $related->count(),
            'fingerprint' => $this->trash->fingerprint($nodes),
        ]);
    }

    public function archive(Request $request, string $type, int $id): RedirectResponse
    {
        $record = $this->trash->resolve($type, $id);
        abort_if($type === 'users' && $request->user()->is($record), 403, 'Akun yang sedang digunakan tidak bisa dihapus.');
        $this->trash->archive($record);

        return redirect()->route('admin.trash.index', ['type' => $type])->with('success', 'Data dipindahkan ke Sampah dan dapat dipulihkan.');
    }

    public function restore(string $type, int $id): RedirectResponse
    {
        $this->trash->restore($this->trash->resolve($type, $id));

        return redirect()->route('admin.trash.index', ['type' => $type])->with('success', 'Data berhasil dipulihkan. Data yang sudah diarsipkan sebelumnya tetap di Sampah.');
    }

    public function destroy(Request $request, string $type, int $id): RedirectResponse
    {
        $data = $request->validate(['confirmation' => ['required', Rule::in(['HAPUS PERMANEN'])], 'fingerprint' => ['required', 'string', 'size:64']]);
        $record = $this->trash->resolve($type, $id);
        abort_if($type === 'users' && $request->user()->is($record), 403);
        $this->trash->destroy($record, $data['fingerprint']);

        return redirect()->route('admin.trash.index', ['type' => $type])->with('success', 'Data dan seluruh data terkait yang ditampilkan telah dihapus permanen.');
    }
}
