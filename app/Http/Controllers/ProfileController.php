<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\Province;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Throwable;

class ProfileController extends SliceController
{
    public function edit(Request $request): View
    {
        /** @var User $user */
        $user = $request->user();

        return $this->slice('Dashboard.User.profile.edit', [
            'user' => $user,
            'avatarUrl' => $user->avatar_path !== null
                ? Storage::disk('public')->url($user->avatar_path)
                : null,
            'notificationPreferences' => array_merge(
                User::DEFAULT_NOTIFICATION_PREFERENCES,
                $user->notification_preferences ?? [],
            ),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'min:3', 'max:120'],
            'phone' => ['nullable', 'string', 'max:30', 'regex:/^[0-9+\-\s()]+$/'],
            'address' => ['nullable', 'string', 'max:180'],
            'province' => ['sometimes', 'required', Rule::enum(Province::class)],
            'institution' => ['nullable', 'string', 'max:120'],
            'bio' => ['nullable', 'string', 'max:500'],
            'avatar' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'remove_avatar' => ['sometimes', 'boolean'],
        ]);

        /** @var User $user */
        $user = $request->user();
        $newAvatarPath = $request->file('avatar')?->store('avatars', 'public');

        if ($request->hasFile('avatar') && $newAvatarPath === false) {
            return back()
                ->withErrors(['avatar' => 'Foto profil gagal diunggah. Coba lagi.'])
                ->withInput();
        }

        try {
            $removedAvatarPath = DB::transaction(function () use ($user, $validated, $newAvatarPath): ?string {
                $profile = User::query()->whereKey($user->id)->lockForUpdate()->firstOrFail();
                $oldAvatarPath = $profile->avatar_path;
                $profile->fill(Arr::only($validated, ['name', 'phone', 'address', 'province', 'institution', 'bio']));

                if (is_string($newAvatarPath)) {
                    $profile->avatar_path = $newAvatarPath;
                } elseif ($validated['remove_avatar'] ?? false) {
                    $profile->avatar_path = null;
                }

                $profile->save();

                return $oldAvatarPath !== $profile->avatar_path ? $oldAvatarPath : null;
            });
        } catch (Throwable $exception) {
            if (is_string($newAvatarPath)) {
                Storage::disk('public')->delete($newAvatarPath);
            }
            throw $exception;
        }

        if ($removedAvatarPath) {
            Storage::disk('public')->delete($removedAvatarPath);
        }

        $user->refresh();

        return back()->with('success', 'Informasi profil berhasil disimpan.');
    }

    public function updatePassword(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        /** @var User $user */
        $user = $request->user();
        $user->update(['password' => $validated['password']]);

        return back()->with('success', 'Kata sandi berhasil diperbarui.');
    }

    public function updateNotifications(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'notifications.new_tryout' => ['sometimes', 'boolean'],
            'notifications.updated_materials' => ['sometimes', 'boolean'],
            'notifications.payment_updates' => ['sometimes', 'boolean'],
            'notifications.package_recommendations' => ['sometimes', 'boolean'],
            'notifications.platform_announcements' => ['sometimes', 'boolean'],
        ]);

        /** @var User $user */
        $user = $request->user();
        $submittedPreferences = $validated['notifications'] ?? [];
        $preferences = [];

        foreach (array_keys(User::DEFAULT_NOTIFICATION_PREFERENCES) as $key) {
            $preferences[$key] = (bool) ($submittedPreferences[$key] ?? false);
        }

        $user->update(['notification_preferences' => $preferences]);

        return back()->with('success', 'Preferensi notifikasi berhasil disimpan.');
    }

    public function destroy(Request $request): RedirectResponse
    {
        $request->validate([
            'delete_password' => ['required', 'current_password'],
            'confirm_delete' => ['accepted'],
        ]);

        /** @var User $user */
        $user = $request->user();

        Auth::logout();
        $user->delete();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home')->with('success', 'Akun kamu sudah dinonaktifkan.');
    }
}
