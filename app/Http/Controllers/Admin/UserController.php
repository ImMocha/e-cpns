<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreUserRequest;
use App\Http\Requests\Admin\UpdateUserRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class UserController extends Controller
{
    private const PER_PAGE = 12;

    public function index(Request $request): View
    {
        $filters = [
            'q' => trim((string) $request->query('q', '')),
            'role' => $request->query('role'),
            'status' => $request->query('status'),
        ];

        $users = User::query()
            ->filter($filters)
            ->orderByDesc('id')
            ->paginate(self::PER_PAGE)
            ->withQueryString();

        return view('Dashboard.Admin.users.index', [
            'users' => $users,
            'filters' => $filters,
            'tabs' => $this->tabs($filters),
            'stats' => [
                'total' => User::withTrashed()->count(),
                'active' => User::active()->count(),
                'admins' => User::role(UserRole::Admin)->count(),
                'inactive' => User::query()->where('status', UserStatus::Inactive->value)->count(),
            ],
            'roles' => UserRole::cases(),
            'statuses' => UserStatus::cases(),
        ]);
    }

    /**
     * Grup filter eksklusif, mengikuti baris pill di desain.
     *
     * @param  array{q: string, role: mixed, status: mixed}  $filters
     * @return array<string, array{label: string, query: array<string, string>, is_active: bool}>
     */
    private function tabs(array $filters): array
    {
        $definitions = [
            'all' => ['label' => 'Semua', 'query' => []],
            'user' => ['label' => 'Peserta', 'query' => ['role' => UserRole::User->value]],
            'admin' => ['label' => 'Admin', 'query' => ['role' => UserRole::Admin->value]],
            'active' => ['label' => 'Aktif', 'query' => ['status' => UserStatus::Active->value]],
            'inactive' => ['label' => 'Nonaktif', 'query' => ['status' => UserStatus::Inactive->value]],
        ];

        $activeTab = 'all';

        foreach ($definitions as $key => $definition) {
            foreach ($definition['query'] as $field => $value) {
                if (($filters[$field] ?? null) === $value) {
                    $activeTab = $key;
                }
            }
        }

        $tabs = [];

        foreach ($definitions as $key => $definition) {
            $query = array_filter(
                array_merge(['q' => $filters['q']], $definition['query']),
                fn ($value) => $value !== null && $value !== '',
            );

            $tabs[$key] = [
                'label' => $definition['label'],
                'url' => route('admin.users.index', $query),
                'is_active' => $key === $activeTab,
            ];
        }

        return $tabs;
    }

    public function create(): View
    {
        return view('Dashboard.Admin.users.create', [
            'user' => new User,
            'roles' => UserRole::cases(),
            'statuses' => UserStatus::cases(),
        ]);
    }

    public function store(StoreUserRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['email_verified_at'] = $request->boolean('email_verified') ? now() : null;

        $user = User::create($data);

        return redirect()
            ->route('admin.users.edit', $user)
            ->with('success', "Akun {$user->name} berhasil dibuat.");
    }

    public function show(User $user): RedirectResponse
    {
        return redirect()->route('admin.users.edit', $user);
    }

    public function edit(User $user): View
    {
        return view('Dashboard.Admin.users.edit', [
            'user' => $user,
            'roles' => UserRole::cases(),
            'statuses' => UserStatus::cases(),
        ]);
    }

    public function update(UpdateUserRequest $request, User $user): RedirectResponse
    {
        $data = $request->validated();

        // Password kosong = pertahankan password lama.
        if (blank($data['password'] ?? null)) {
            unset($data['password']);
        }

        $data['email_verified_at'] = $request->boolean('email_verified') ? now() : null;

        $user->update($data);

        return redirect()
            ->route('admin.users.index')
            ->with('success', "Perubahan pada akun {$user->name} disimpan.");
    }

    public function destroy(Request $request, User $user): RedirectResponse
    {
        if ($request->user()?->is($user)) {
            return back()->with('error', 'Akun yang sedang digunakan tidak bisa dihapus.');
        }

        $user->delete();

        return redirect()
            ->route('admin.users.index')
            ->with('success', "Akun {$user->name} dipindahkan ke sampah.");
    }

    /** Ubah status aktif / nonaktif tanpa membuka form edit. */
    public function toggleStatus(Request $request, User $user): RedirectResponse
    {
        if ($request->user()?->is($user) && $user->isActive()) {
            return back()->with('error', 'Akun yang sedang digunakan tidak bisa dinonaktifkan.');
        }

        $user->update([
            'status' => $user->isActive() ? UserStatus::Inactive : UserStatus::Active,
        ]);

        $verb = $user->isActive() ? 'diaktifkan' : 'dinonaktifkan';

        return back()->with('success', "Akun {$user->name} {$verb}.");
    }
}
