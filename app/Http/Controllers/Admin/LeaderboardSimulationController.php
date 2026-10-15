<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\LeaderboardSimulation;
use App\Models\LeaderboardBot;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class LeaderboardSimulationController extends Controller
{
    public function index(LeaderboardSimulation $simulation): View
    {
        return view('Dashboard.Admin.leaderboard.index', [
            'simulationEnabled' => $simulation->enabled(),
            'populationRange' => $simulation->populationRange(),
            'botCount' => LeaderboardBot::query()->where('is_visible', true)->count(),
            'provinceCounts' => LeaderboardBot::query()->where('is_visible', true)->select('province')
                ->selectRaw('COUNT(*) AS total')->groupBy('province')->orderBy('province')->get(),
            'sampleBots' => LeaderboardBot::query()->where('is_visible', true)->orderByDesc('score')->orderBy('id')->limit(5)->get(),
        ]);
    }

    public function update(Request $request, LeaderboardSimulation $simulation): RedirectResponse
    {
        $data = $request->validate([
            'enabled' => ['required', 'boolean'],
            'minimum_per_province' => ['sometimes', 'required', 'integer', 'between:1,100'],
            'maximum_per_province' => ['sometimes', 'required', 'integer', 'between:1,100'],
        ], ['minimum_per_province.between' => 'Jumlah minimum harus antara 1 dan 100.',
            'maximum_per_province.between' => 'Jumlah maksimum harus antara 1 dan 100.']);

        $range = $simulation->populationRange();
        $minimum = (int) ($data['minimum_per_province'] ?? $range['minimum']);
        $maximum = (int) ($data['maximum_per_province'] ?? $range['maximum']);
        if ($minimum > $maximum) {
            throw ValidationException::withMessages(['maximum_per_province' => 'Jumlah maksimum harus sama atau lebih besar dari minimum.']);
        }

        $enabled = $request->boolean('enabled');
        $simulation->setEnabled($enabled, $minimum, $maximum);

        return redirect()->route('admin.leaderboard-simulation.index')
            ->with('success', $enabled ? 'Pengaturan peserta virtual disimpan. Fitur aktif di leaderboard.' : 'Pengaturan disimpan. Peserta virtual disembunyikan dari leaderboard.');
    }
}
