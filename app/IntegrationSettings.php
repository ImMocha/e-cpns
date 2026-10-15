<?php

declare(strict_types=1);

namespace App;

use App\Enums\TransactionStatus;
use App\Models\IntegrationSetting;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class IntegrationSettings
{
    public const SECRET_FIELDS = ['sandbox_server_key', 'production_server_key', 'smtp_password'];

    /** @return array{enabled: bool, is_production: bool, sandbox_server_key: string, production_server_key: string} */
    public function midtrans(): array
    {
        return array_replace($this->midtransDefaults(), IntegrationSetting::query()->findOrFail('midtrans')->settings ?? []);
    }

    /** @return array<string, mixed>|null */
    public function mail(): ?array
    {
        return IntegrationSetting::query()->findOrFail('mail')->settings;
    }

    /** @return array<string, mixed> */
    public function mailDefaults(): array
    {
        return [
            'enabled' => true, 'smtp_host' => config('mail.mailers.smtp.host'),
            'smtp_port' => (int) config('mail.mailers.smtp.port'),
            'smtp_username' => (string) config('mail.mailers.smtp.username'),
            'smtp_password' => (string) config('mail.mailers.smtp.password'),
            'from_address' => config('mail.from.address'), 'from_name' => config('mail.from.name'),
        ];
    }

    /** @return list<string> */
    public function smtpHosts(): array
    {
        return config('services.integrations.smtp_hosts');
    }

    /** @param array<string, mixed> $data */
    public function update(string $key, array $data, User $actor, ?string $ipAddress): void
    {
        DB::transaction(function () use ($key, $data, $actor, $ipAddress): void {
            $record = IntegrationSetting::query()->lockForUpdate()->findOrFail($key);
            if ($record->version !== (int) $data['version']) {
                throw ValidationException::withMessages(['version' => 'Pengaturan sudah diubah admin lain. Muat ulang halaman sebelum menyimpan.']);
            }

            $before = $record->settings ?? ($key === 'midtrans' ? $this->midtransDefaults() : $this->mailDefaults());
            $fields = $key === 'midtrans'
                ? ['enabled', 'is_production', 'sandbox_server_key', 'production_server_key']
                : ['enabled', 'smtp_host', 'smtp_port', 'smtp_username', 'smtp_password', 'from_address', 'from_name'];
            $after = array_replace($before, Arr::only($data, $fields));
            foreach (self::SECRET_FIELDS as $field) {
                if (! in_array($field, $fields, true)) {
                    continue;
                }
                $after[$field] = ! empty($data['remove_'.$field]) ? '' : ($data[$field] ?? $before[$field]);
            }
            $after['enabled'] = (bool) $after['enabled'];
            if ($key === 'midtrans') {
                $after['is_production'] = (bool) $after['is_production'];
                foreach (['sandbox_server_key' => false, 'production_server_key' => true] as $field => $isProduction) {
                    if ($before[$field] !== '' && $after[$field] !== $before[$field]
                        && Transaction::query()->where('provider', 'midtrans')->where('is_production', $isProduction)
                            ->where('status', TransactionStatus::Pending)->exists()) {
                        throw ValidationException::withMessages([$field => 'Key belum dapat diganti atau dihapus karena masih ada transaksi pending pada mode ini. Sinkronkan dan selesaikan transaksi terlebih dahulu.']);
                    }
                }
                $activeField = $after['is_production'] ? 'production_server_key' : 'sandbox_server_key';
                if ($after['enabled'] && $after[$activeField] === '') {
                    throw ValidationException::withMessages([$activeField => 'Isi server key untuk mode yang akan diaktifkan.']);
                }
            } else {
                $after['smtp_port'] = (int) $after['smtp_port'];
                if ($after['enabled']) {
                    foreach (['smtp_host', 'smtp_username', 'smtp_password', 'from_address', 'from_name'] as $field) {
                        if ($after[$field] === null || $after[$field] === '') {
                            throw ValidationException::withMessages([$field => 'Lengkapi konfigurasi SMTP sebelum mengaktifkan email.']);
                        }
                    }
                }
            }

            $changedFields = array_values(array_filter($fields, fn (string $field): bool => $before[$field] !== $after[$field]));
            $record->update(['settings' => $after, 'version' => $record->version + 1, 'updated_by' => $actor->id]);
            $this->audit($key, 'updated', $changedFields, $actor, $ipAddress);
        });
    }

    /** @param list<string> $changedFields */
    public function audit(string $key, string $action, array $changedFields, User $actor, ?string $ipAddress): void
    {
        DB::table('integration_setting_audits')->insert([
            'key' => $key, 'action' => $action, 'changed_fields' => json_encode($changedFields, JSON_THROW_ON_ERROR),
            'actor_id' => $actor->id, 'ip_address' => $ipAddress, 'created_at' => now(),
        ]);
    }

    /** @return array{enabled: bool, is_production: bool, sandbox_server_key: string, production_server_key: string} */
    private function midtransDefaults(): array
    {
        $production = (bool) config('services.midtrans.is_production');

        return [
            'enabled' => true, 'is_production' => $production,
            'sandbox_server_key' => $production ? '' : (string) config('services.midtrans.server_key'),
            'production_server_key' => $production ? (string) config('services.midtrans.server_key') : '',
        ];
    }
}
