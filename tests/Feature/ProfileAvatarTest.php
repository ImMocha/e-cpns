<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProfileAvatarTest extends TestCase
{
    use RefreshDatabase;

    public function test_participant_can_remove_their_saved_avatar_while_updating_their_profile(): void
    {
        $disk = Storage::fake('public');
        $path = 'avatars/old.png';
        $disk->put($path, 'old avatar');
        $user = User::factory()->create(['avatar_path' => $path]);

        $this->actingAs($user)->from(route('profile.edit'))->patch(route('profile.update'), [
            'name' => 'Nama Baru',
            'remove_avatar' => '1',
        ])->assertRedirect(route('profile.edit'))->assertSessionHasNoErrors();

        $this->assertDatabaseHas('users', ['id' => $user->id, 'name' => 'Nama Baru', 'avatar_path' => null]);
        $disk->assertMissing($path);
    }

    public function test_replacing_the_avatar_takes_precedence_over_removal(): void
    {
        $disk = Storage::fake('public');
        $path = 'avatars/old.png';
        $disk->put($path, 'old avatar');
        $user = User::factory()->create(['avatar_path' => $path]);

        $this->actingAs($user)->from(route('profile.edit'))->patch(route('profile.update'), [
            'name' => $user->name,
            'avatar' => UploadedFile::fake()->image('replacement.png'),
            'remove_avatar' => '1',
        ])->assertRedirect(route('profile.edit'))->assertSessionHasNoErrors();

        $replacementPath = $user->fresh()->avatar_path;
        $this->assertNotNull($replacementPath);
        $disk->assertExists($replacementPath);
        $disk->assertMissing($path);
    }
}
