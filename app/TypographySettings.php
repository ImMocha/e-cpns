<?php

declare(strict_types=1);

namespace App;

use App\Models\IntegrationSetting;
use App\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class TypographySettings
{
    /** @return array<string, array{label: string, category: string}> */
    public function fonts(): array
    {
        $groups = [
            'Sans serif' => ['Outfit', 'Plus Jakarta Sans', 'Inter', 'Poppins', 'DM Sans', 'Manrope', 'Nunito', 'Nunito Sans', 'Lato', 'Open Sans', 'Roboto', 'Montserrat', 'Raleway', 'Rubik', 'Work Sans', 'Source Sans 3', 'Figtree', 'Lexend', 'Ubuntu', 'Quicksand'],
            'Serif' => ['Lora', 'Merriweather', 'Playfair Display', 'Libre Baskerville'],
        ];
        $fonts = [];
        foreach ($groups as $category => $families) {
            foreach ($families as $family) {
                $fonts[strtolower(str_replace(' ', '-', $family))] = ['label' => $family, 'category' => $category];
            }
        }

        return $fonts;
    }

    /** @return array{heading_font: string, body_font: string, landing: bool, member: bool, admin: bool} */
    public function defaults(): array
    {
        return ['heading_font' => 'outfit', 'body_font' => 'plus-jakarta-sans', 'landing' => true, 'member' => false, 'admin' => false];
    }

    /** @return array{settings: array{heading_font: string, body_font: string, landing: bool, member: bool, admin: bool}, version: int} */
    public function current(): array
    {
        $record = IntegrationSetting::query()->find('typography');

        return ['settings' => array_replace($this->defaults(), $record?->settings ?? []), 'version' => $record?->version ?? 0];
    }

    public function stack(string $font): string
    {
        $definition = $this->fonts()[$font] ?? $this->fonts()['plus-jakarta-sans'];

        return '"'.$definition['label'].'", '.($definition['category'] === 'Serif' ? 'Georgia, serif' : 'ui-sans-serif, system-ui, sans-serif');
    }

    /** @param list<string> $fonts */
    public function stylesheet(array $fonts): ?string
    {
        $families = [];
        foreach (array_unique($fonts) as $font) {
            if (isset($this->fonts()[$font]) && ! in_array($font, ['outfit', 'plus-jakarta-sans'], true)) {
                $families[] = $font.':400,700';
            }
        }

        return $families === [] ? null : 'https://fonts.bunny.net/css?family='.implode('|', $families).'&display=swap';
    }

    /** @param array<string, mixed> $data */
    public function update(array $data, User $actor, ?string $ipAddress): void
    {
        DB::transaction(function () use ($data, $actor, $ipAddress): void {
            IntegrationSetting::query()->firstOrCreate(['key' => 'typography']);
            $record = IntegrationSetting::query()->lockForUpdate()->findOrFail('typography');
            if ($record->version !== (int) $data['version']) {
                throw ValidationException::withMessages(['version' => 'Pengaturan sudah diubah admin lain. Muat ulang halaman sebelum menyimpan.']);
            }
            $before = array_replace($this->defaults(), $record->settings ?? []);
            $after = Arr::only($data, array_keys($this->defaults()));
            foreach (['landing', 'member', 'admin'] as $scope) {
                $after[$scope] = (bool) $after[$scope];
            }
            $changed = array_values(array_filter(array_keys($before), fn (string $field): bool => $before[$field] !== $after[$field]));
            $record->update(['settings' => $after, 'version' => $record->version + 1, 'updated_by' => $actor->id]);
            app(IntegrationSettings::class)->audit('typography', 'updated', $changed, $actor, $ipAddress);
        });
    }
}
