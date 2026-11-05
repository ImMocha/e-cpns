<?php

declare(strict_types=1);

namespace App;

use App\Models\IntegrationSetting;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Str;
use Illuminate\View\ViewException;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class ErrorDiagnostics
{
    /** @return array{enabled: bool, version: int} */
    public function current(): array
    {
        $record = IntegrationSetting::query()->find('error_diagnostics');

        return ['enabled' => (bool) ($record?->settings['enabled'] ?? false), 'version' => $record?->version ?? 0];
    }

    public function respond(Response $response, Throwable $exception, Request $request): Response
    {
        $status = $response->getStatusCode();
        if ($status < 400 || $status > 599 || $request->expectsJson() || $request->is('api/*', 'payments/midtrans/notification')) {
            return $response;
        }

        try {
            $user = $request->user();
            if (! $user?->isAdmin() || ! $user->isActive() || ! $user->hasVerifiedEmail() || ! $this->current()['enabled']) {
                return $response;
            }

            $cause = $exception;
            while ($cause instanceof ViewException && $cause->getPrevious()) {
                $cause = $cause->getPrevious();
            }
            $reference = (string) Str::uuid();
            $view = View::exists('errors.'.$status) ? 'errors.'.$status : 'errors.'.intdiv($status, 100).'xx';
            $content = view($view, ['diagnostic' => [
                'reference' => $reference,
                'type' => class_basename($cause),
                'reason' => $this->reason($cause, $status),
                'source' => str_replace(base_path().DIRECTORY_SEPARATOR, '', $cause->getFile()).':'.$cause->getLine(),
            ]])->render();
            Log::error('Admin error diagnostic', ['reference' => $reference, 'exception' => $exception]);
            $response->setContent($content);
            $response->headers->set('Cache-Control', 'no-store, private');
            $response->headers->set('Vary', 'Cookie', false);
        } catch (Throwable) {
            return $response;
        }

        return $response;
    }

    private function reason(Throwable $exception, int $status): string
    {
        if (preg_match('/Route \[([a-zA-Z0-9_.-]+)\] not defined/', $exception->getMessage(), $matches)) {
            return 'Route '.$matches[1].' belum terdaftar. Perbarui cache route setelah deployment.';
        }
        if ($exception instanceof QueryException) {
            return 'Operasi database gagal (SQLSTATE '.$exception->getCode().'). Periksa koneksi, izin, dan migrasi database. Detail query tersedia di log melalui kode referensi.';
        }

        return match ($status) {
            401 => 'Sesi login diperlukan untuk mengakses halaman ini.',
            403 => 'Akun tidak memiliki izin untuk menjalankan permintaan ini.',
            404 => 'Alamat halaman atau data yang diminta tidak ditemukan.',
            405 => 'Metode HTTP tidak didukung oleh route ini.',
            419 => 'Sesi atau token keamanan formulir sudah kedaluwarsa. Muat ulang halaman.',
            429 => 'Batas jumlah permintaan telah tercapai. Tunggu sebelum mencoba kembali.',
            503 => 'Layanan sedang dalam pemeliharaan atau sementara tidak tersedia.',
            default => 'Permintaan gagal diproses. Cocokkan kode referensi dengan log Laravel untuk pesan exception lengkap.',
        };
    }
}
