<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateTypographyRequest;
use App\TypographySettings;
use Illuminate\Http\RedirectResponse;

class TypographySettingController extends Controller
{
    public function update(UpdateTypographyRequest $request, TypographySettings $typography): RedirectResponse
    {
        $typography->update($request->validated(), $request->user(), $request->ip());

        return redirect()->route('admin.settings.index')->with('success', 'Pengaturan font disimpan. Font diterapkan pada area yang dipilih.');
    }
}
