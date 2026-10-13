<?php

namespace App\Http\Controllers;

use App\Models\Material;
use App\Models\MaterialProgress;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class MaterialProgressController extends Controller
{
    public function store(Request $request, Material $material): RedirectResponse
    {
        $user = $request->user();
        $this->ensureTrackable($material, $user);

        $progress = MaterialProgress::query()->firstOrCreate(
            ['user_id' => $user->id, 'material_id' => $material->id],
            ['last_viewed_at' => now(), 'completed_at' => now()],
        );

        if ($progress->completed_at === null) {
            $progress->update(['completed_at' => now()]);
        }

        return redirect()->route('materials.show', $material)->with('success', 'Materi ditandai selesai.');
    }

    public function destroy(Request $request, Material $material): RedirectResponse
    {
        $user = $request->user();
        $this->ensureTrackable($material, $user);

        MaterialProgress::query()->whereBelongsTo($user)
            ->where('material_id', $material->id)
            ->update(['completed_at' => null]);

        return redirect()->route('materials.show', $material)->with('success', 'Materi kembali ditandai belum selesai.');
    }

    private function ensureTrackable(Material $material, User $user): void
    {
        abort_if($user->isAdmin(), 403, 'Progres belajar hanya untuk peserta.');
        abort_unless($material->isVisibleToMember(), 404);
        abort_unless($material->isAccessibleBy($user), 403);
        abort_unless($material->hasAvailableContent(), 422, 'Konten materi belum tersedia.');
    }
}
