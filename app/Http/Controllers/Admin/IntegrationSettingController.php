<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateIntegrationSettingRequest;
use App\IntegrationSettings;
use App\MidtransGateway;
use App\Models\IntegrationSetting;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Mail\Message;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;
use Throwable;

class IntegrationSettingController extends Controller
{
    public function index(IntegrationSettings $settings): View
    {
        $midtrans = $settings->midtrans();
        $mail = $settings->mail() ?? $settings->mailDefaults();

        return view('Dashboard.Admin.settings.index', [
            'midtrans' => Arr::except($midtrans, IntegrationSettings::SECRET_FIELDS),
            'mail' => Arr::except($mail, IntegrationSettings::SECRET_FIELDS),
            'sandboxConfigured' => $midtrans['sandbox_server_key'] !== '',
            'productionConfigured' => $midtrans['production_server_key'] !== '',
            'smtpConfigured' => $mail['smtp_password'] !== '',
            'mailManaged' => $settings->mail() !== null,
            'versions' => IntegrationSetting::query()->pluck('version', 'key'),
            'smtpHosts' => $settings->smtpHosts(),
            'audits' => DB::table('integration_setting_audits')->leftJoin('users', 'users.id', '=', 'actor_id')
                ->select('integration_setting_audits.*', 'users.name as actor_name')
                ->orderByDesc('integration_setting_audits.id')->paginate(15),
        ]);
    }

    public function update(UpdateIntegrationSettingRequest $request, string $integration, IntegrationSettings $settings): RedirectResponse
    {
        $settings->update($integration, $request->validated(), $request->user(), $request->ip());

        return redirect()->route('admin.settings.index')->with('success', 'Pengaturan integrasi disimpan.');
    }

    public function test(UpdateIntegrationSettingRequest $request, string $integration, IntegrationSettings $settings, MidtransGateway $gateway): RedirectResponse
    {
        $successful = false;
        try {
            if ($integration === 'midtrans') {
                $successful = $gateway->testConnection($request->boolean('is_production'));
            } else {
                $mail = $settings->mail();
                if ($mail !== null && $mail['enabled']) {
                    Mail::send(['html' => 'emails.notification', 'text' => 'emails.notification-text'], [
                        'headline' => 'Email uji berhasil dikirim',
                        'intro' => 'Koneksi email '.config('app.name').' siap digunakan. Email ini dikirim untuk menguji integrasi SMTP yang kamu simpan.',
                        'actionText' => 'Buka pengaturan email',
                        'actionUrl' => route('admin.settings.index'),
                        'details' => [],
                        'outro' => 'Ini adalah email uji yang diminta melalui halaman pengaturan integrasi admin.',
                        'preferencesUrl' => null,
                    ], function (Message $message) use ($request): void {
                        $message->to($request->user()->email)->subject('Uji integrasi email — '.config('app.name'));
                    });
                    $successful = true;
                }
            }
        } catch (Throwable) {
            $successful = false;
        }

        $settings->audit($integration, $successful ? 'test_succeeded' : 'test_failed', [], $request->user(), $request->ip());
        if (! $successful) {
            throw ValidationException::withMessages(['connection' => 'Uji koneksi gagal. Pastikan integrasi aktif, kredensial tersimpan benar, dan layanan dapat dihubungi.']);
        }

        return redirect()->route('admin.settings.index')->with('success', $integration === 'mail'
            ? 'Server SMTP menerima email uji untuk alamat email akun admin kamu. Periksa kotak masuk dan spam.'
            : 'Kredensial Midtrans diterima. Uji ini tidak membuat transaksi pembayaran.');
    }
}
