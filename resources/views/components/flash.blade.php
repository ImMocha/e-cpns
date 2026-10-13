{{--
    Jembatan flash message Laravel -> toast design system.

    Script inline di body dieksekusi SEBELUM module Vite, jadi kita menunggu
    DOMContentLoaded (module deferred sudah dijalankan pada titik itu).
--}}
@php
    $flashType = null;
    $flashText = null;

    foreach (['success' => 'success', 'error' => 'error', 'warning' => 'warn', 'info' => 'info'] as $key => $tone) {
        if (session()->has($key)) {
            $flashType = $tone;
            $flashText = session()->get($key);

            break;
        }
    }
@endphp

@if ($flashType)
    <script>
        (function () {
            document.addEventListener('DOMContentLoaded', function () {
                if (typeof window.toast === 'function') {
                    window.toast(@json($flashText), @json($flashType), 4200);
                }
            });
        })();
    </script>
@endif
