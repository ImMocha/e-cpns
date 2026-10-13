<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\MaterialAccess;
use App\Enums\MaterialStatus;
use App\Enums\MaterialType;
use App\Models\Material;
use App\Models\Section;
use App\Models\Topic;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Material>
 */
class MaterialFactory extends Factory
{
    public function configure(): static
    {
        return $this->afterCreating(function (Material $material): void {
            if ($material->topic_id === null) {
                $topic = Topic::firstOrCreate(['section_id' => $material->section_id, 'name' => 'Umum']);
                $material->update(['topic_id' => $topic->id]);
            }
        });
    }

    public function definition(): array
    {
        $title = Str::title(fake()->unique()->words(4, true));

        // Default sengaja deterministik — PDF, gratis, terbit, dan punya
        // berkasnya. Acak membuat test bergantung pada undian: satu pengujian
        // bisa hijau di lokal dan merah di CI. Yang tidak seperti default
        // dipanggil eksplisit lewat state.
        return [
            'section_id' => Section::factory(),
            'topic_id' => null,
            'title' => $title,
            'slug' => Str::slug($title).'-'.fake()->unique()->numberBetween(1, 999999),
            'summary' => fake()->optional()->sentence(14),
            'type' => MaterialType::Pdf->value,
            'access_level' => MaterialAccess::Free->value,
            'status' => MaterialStatus::Published->value,
            'file_path' => 'materials/pdf/'.fake()->uuid.'.pdf',
            'file_size' => fake()->numberBetween(180_000, 8_000_000),
            'page_count' => fake()->numberBetween(8, 120),
            'video_url' => null,
            'duration_minutes' => null,
            'position' => fake()->numberBetween(0, 20),
            'published_at' => fake()->dateTimeBetween('-3 months', 'now'),
        ];
    }

    public function draft(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => MaterialStatus::Draft->value,
            'published_at' => null,
        ]);
    }

    public function published(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => MaterialStatus::Published->value,
            'published_at' => fake()->dateTimeBetween('-3 months', 'now'),
        ]);
    }

    public function premium(): static
    {
        return $this->state(fn (array $attributes) => ['access_level' => MaterialAccess::Premium->value]);
    }

    public function free(): static
    {
        return $this->state(fn (array $attributes) => ['access_level' => MaterialAccess::Free->value]);
    }

    public function pdf(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => MaterialType::Pdf->value,
            'video_url' => null,
            'duration_minutes' => null,
        ]);
    }

    public function video(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => MaterialType::Video->value,
            'file_path' => null,
            'file_size' => null,
            'page_count' => null,
            'video_url' => 'https://cdn.example.com/'.fake()->uuid.'.mp4',
            'duration_minutes' => fake()->numberBetween(5, 90),
        ]);
    }

    /** Tanpa berkas — untuk menguji aturan "tidak bisa terbit tanpa sumber". */
    public function withoutSource(): static
    {
        return $this->state(fn (array $attributes) => [
            'file_path' => null,
            'file_size' => null,
            'page_count' => null,
            'video_url' => null,
            'duration_minutes' => null,
        ]);
    }

    /** Alias tanpa sumber, untuk `make()` tanpa menyentuh database. */
    public function plain(): static
    {
        return $this->withoutSource();
    }
}
