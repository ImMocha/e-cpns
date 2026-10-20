<aside {{ $attributes->merge(['class' => 'alert alert--info']) }} aria-label="Passing grade tryout">
    <span class="alert__icon"><svg data-icon="info"></svg></span>
    <div>
        <strong>Passing Grade per Bidang</strong>
        <div class="flex flex-wrap gap-2" style="margin-block:.65rem">
            @foreach (\App\ExamScoring::PASSING_GRADES as $section => $minimum)
                <span class="badge badge--primary badge--flat">{{ $section }} ≥ {{ $minimum }} poin</span>
            @endforeach
        </div>
        <p class="text-sm">Wajib PASS di semua bidang untuk lulus tryout dan mendapatkan sertifikat. Nilai total tinggi tidak menggantikan passing grade bidang yang belum terpenuhi.</p>
    </div>
</aside>
