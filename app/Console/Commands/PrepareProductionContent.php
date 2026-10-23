<?php

namespace App\Console\Commands;

use App\Enums\MaterialStatus;
use App\Enums\MaterialType;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\Material;
use App\Models\User;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

#[Signature('app:prepare-production-content {--apply : Terapkan perubahan setelah menyimpan cadangan status}')]
#[Description('Kembalikan materi uji ke draft dan nonaktifkan akun seed yang masih memakai password bawaan')]
class PrepareProductionContent extends Command
{
    public function handle(): int
    {
        $materials = Material::query()->published()->get()->filter(fn (Material $material): bool => $this->isSampleMaterial($material));
        $users = User::query()->active()->where(function ($query): void {
            $query->whereIn('email', ['admin@lontar.asia', 'editor@lontar.asia', 'peserta@lontar.asia'])
                ->orWhere('email', 'like', '%@example.com')->orWhere('email', 'like', '%@example.net')->orWhere('email', 'like', '%@example.org');
        })->get()->filter(fn (User $user): bool => Hash::check('password', $user->password));
        $adminContacts = User::query()->role(UserRole::Admin)
            ->whereIn('email', ['admin@lontar.asia', 'editor@lontar.asia'])
            ->whereIn('phone', ['0812 3456 7890', '0813 2211 9087'])->get();

        $this->info($materials->count().' materi uji akan dikembalikan ke draft.');
        $this->info($users->count().' akun seed dengan password bawaan akan dinonaktifkan.');
        $this->info($adminContacts->count().' nomor admin bawaan akan diperbarui.');
        if (! $this->option('apply') || ($materials->isEmpty() && $users->isEmpty() && $adminContacts->isEmpty())) {
            return self::SUCCESS;
        }

        if ($users->contains(fn (User $user): bool => $user->isAdmin()) && ! User::query()->active()->role(UserRole::Admin)->whereNotIn('id', $users->modelKeys())->exists()) {
            $this->warn('Administrator seed terakhir dipertahankan. Ganti password admin melalui Profil sebelum menggunakan layanan untuk produksi.');
            $users = $users->reject(fn (User $user): bool => $user->isAdmin());
        }
        if ($materials->isEmpty() && $users->isEmpty() && $adminContacts->isEmpty()) {
            return self::SUCCESS;
        }

        $backupPath = 'production-preparation-'.now()->format('Ymd-His').'-'.Str::uuid().'.json';
        $backup = ['materials' => $materials->map->only(['id', 'status'])->values()->all(),
            'users' => $users->map->only(['id', 'status', 'remember_token'])->values()->all(),
            'admin_contacts' => $adminContacts->map->only(['id', 'phone'])->values()->all()];
        if (! Storage::disk('local')->put($backupPath, json_encode($backup, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR))) {
            $this->error('Cadangan tidak dapat disimpan; tidak ada perubahan yang diterapkan.');

            return self::FAILURE;
        }

        DB::transaction(function () use ($materials, $users, $adminContacts): void {
            if ($materials->isNotEmpty()) {
                $materials->toQuery()->update(['status' => MaterialStatus::Draft]);
            }
            if ($users->isNotEmpty()) {
                $users->toQuery()->update(['status' => UserStatus::Inactive, 'remember_token' => null]);
            }
            if ($adminContacts->isNotEmpty()) {
                $adminContacts->toQuery()->update(['phone' => config('app.contact_phone')]);
            }
        });
        $this->info('Perubahan diterapkan. Cadangan: storage/app/private/'.$backupPath);

        return self::SUCCESS;
    }

    private function isSampleMaterial(Material $material): bool
    {
        if ($material->type === MaterialType::Video) {
            $url = (string) $material->video_url;

            return $url === '' || parse_url($url, PHP_URL_HOST) === 'cdn.example.com'
                || $url === 'https://developer.mozilla.org/shared-assets/videos/flower.mp4';
        }

        if (! $material->file_path || ! Storage::disk(Material::PDF_DISK)->exists($material->file_path)) {
            return true;
        }
        $stream = Storage::disk(Material::PDF_DISK)->readStream($material->file_path);
        if (! is_resource($stream)) {
            return false;
        }
        try {
            $header = fread($stream, 65536);

            return $header !== false && str_contains($header, 'PLACEHOLDER-LONTAR-v1');
        } finally {
            fclose($stream);
        }
    }
}
