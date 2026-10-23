@props(['audience' => 'member'])
@php
    $destinations = $audience === 'admin' ? [
        'admin-start' => 'admin.dashboard',
        'admin-sections' => 'admin.sections.index',
        'admin-topics' => 'admin.topics.index',
        'admin-materials' => 'admin.materials.index',
        'admin-quizzes' => 'admin.materials.quizzes.index',
        'admin-exams' => 'admin.exams.index',
        'admin-questions' => 'admin.questions.index',
        'admin-articles' => 'admin.articles.index',
        'admin-users' => 'admin.users.index',
        'admin-packages' => 'admin.packages.index',
        'admin-transactions' => 'admin.transactions.index',
        'admin-leaderboard' => 'admin.leaderboard-simulation.index',
        'admin-vouchers' => 'admin.vouchers.index',
    ] : [
        'member-dashboard' => 'dashboard',
        'member-materials' => 'materials.index',
        'member-question-bank' => 'question-bank.index',
        'member-quizzes' => 'quizzes.index',
        'member-tryouts' => 'tryouts.index',
        'member-history' => 'history.index',
        'member-statistics' => 'statistics.index',
        'member-leaderboard' => 'leaderboard.index',
        'member-packages' => 'packages.index',
        'member-transactions' => 'transactions.index',
        'member-certificates' => 'certificates.index',
        'member-profile' => 'profile.edit',
        'member-articles' => 'articles.index',
    ];
@endphp
<div class="feature-help no-print" data-feature-help data-help-audience="{{ $audience }}" data-help-route="{{ request()->route()?->getName() }}">
    <button class="appbar__icon help-launcher" type="button" data-help-open aria-label="Bantuan &amp; Panduan" title="Bantuan &amp; Panduan" aria-haspopup="dialog" aria-controls="feature-help-center">
        <svg data-icon="book" aria-hidden="true"></svg>
    </button>

    <section class="help-banner" data-help-banner aria-label="Panduan halaman ini" hidden>
        <div><span class="help-eyebrow">Panduan halaman ini</span><p data-help-summary></p></div>
        <div class="btn-row">
            <button class="btn btn--ghost btn--sm" type="button" data-help-current>Baca penjelasan</button>
            <button class="btn btn--primary btn--sm" type="button" data-help-tour>Mulai tur halaman</button>
        </div>
    </section>

    <dialog class="help-center" id="feature-help-center" role="dialog" aria-modal="true" aria-labelledby="feature-help-title" aria-describedby="feature-help-description" data-help-dialog>
        <div class="help-center__header">
            <div><span class="help-eyebrow">{{ $audience === 'admin' ? 'Pegangan administrator' : 'Teman belajar kamu' }}</span><h2 id="feature-help-title">Pusat Bantuan &amp; Panduan</h2><p id="feature-help-description">Penjelasan fitur, langkah penggunaan, arti pengaturan, dan solusi kendala.</p></div>
            <button class="btn btn--ghost btn--square btn--sm" type="button" data-help-close aria-label="Tutup pusat bantuan">×</button>
        </div>
        <div class="help-center__search">
            <label class="label" for="feature-help-search">Cari di seluruh panduan {{ $audience === 'admin' ? 'admin' : 'peserta' }}</label>
            <input class="input" id="feature-help-search" type="search" placeholder="Contoh: passing grade, kuota, soal berbobot, materi terkunci…" autocomplete="off" data-help-search aria-controls="feature-help-topics">
            <p class="text-xs text-soft" data-help-count role="status" aria-live="polite"></p>
        </div>
        <div class="help-center__layout">
            <nav class="help-center__topics" id="feature-help-topics" aria-label="Topik bantuan" data-help-topics></nav>
            <article class="help-center__article" tabindex="0" aria-label="Isi panduan" data-help-article></article>
        </div>
        <div class="help-center__footer">Tur hanya menjelaskan dan menyorot bagian halaman. Tutup dengan tombol × atau Esc. Pada sesi ujian, timer tetap berjalan saat bantuan terbuka.</div>
    </dialog>

    <div data-help-destinations hidden>
        @foreach ($destinations as $guideId => $routeName)
            <a data-help-destination="{{ $guideId }}" href="{{ route($routeName) }}">{{ $guideId }}</a>
        @endforeach
    </div>
    <noscript><p class="help-noscript">Aktifkan JavaScript untuk membuka pusat bantuan dan tur interaktif.</p></noscript>
</div>
