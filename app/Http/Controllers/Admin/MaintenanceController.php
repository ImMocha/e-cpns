<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateMaintenanceRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Artisan;

class MaintenanceController extends Controller
{
    public function update(UpdateMaintenanceRequest $request): RedirectResponse
    {
        $enabled = $request->boolean('enabled');
        $exitCode = $enabled
            ? Artisan::call('down', ['--render' => 'errors::503', '--retry' => 60, '--no-interaction' => true])
            : Artisan::call('up', ['--no-interaction' => true]);

        if ($exitCode !== 0) {
            return back()->with('error', 'Status maintenance belum dapat diubah. Periksa log server lalu coba kembali.');
        }

        return redirect()->route('admin.settings.index')->with('success', $enabled
            ? 'Mode maintenance aktif. Pengunjung sementara melihat halaman pemeliharaan.'
            : 'Mode maintenance dinonaktifkan. Website kembali dapat diakses.');
    }
}
