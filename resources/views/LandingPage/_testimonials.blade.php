<section class="section testimonial-page" id="testimoni">
    <div class="container container--wide testimonial-page__layout">
        <header class="section-head testimonial-page__heading">
            <div><span class="eyebrow">Cerita Peserta</span><h2>Testimoni Peserta</h2><p class="text-soft">Pengalaman belajar dan persiapan CPNS bersama Ganesha Nusa.</p></div>
            <a class="btn btn--primary" href="#kirim-testimoni">Tulis Testimoni <svg data-icon="pen-line"></svg></a>
        </header>
        @if (session('testimonial_success'))<div class="card mb-6" role="status">{{ session('testimonial_success') }}</div>@endif
        @if ($testimonials->isNotEmpty())
        <section class="testimonial-carousel" data-testimonial-carousel aria-label="Testimoni peserta" aria-roledescription="carousel">
            <div class="testimonial-carousel__track" data-testimonial-track tabindex="0" aria-label="Geser testimoni atau gunakan tombol panah keyboard">
            @foreach ($testimonials as $testimonial)
                <x-testimonial-card :testimonial="$testimonial" />
            @endforeach
            </div>
            <div class="testimonial-carousel__controls" data-testimonial-controls hidden>
                <p class="text-sm text-soft">Tarik atau geser untuk melihat cerita lainnya</p>
                <div class="testimonial-carousel__navigation">
                    <button class="btn btn--outline btn--sm" type="button" data-testimonial-previous aria-label="Testimoni sebelumnya"><svg data-icon="chevron-left"></svg></button>
                    <span class="text-sm" data-testimonial-position aria-live="polite" aria-atomic="true"></span>
                    <button class="btn btn--outline btn--sm" type="button" data-testimonial-next aria-label="Testimoni berikutnya"><svg data-icon="chevron-right"></svg></button>
                </div>
            </div>
        </section>
        @else
            <div class="card testimonial-page__empty"><h2>Belum ada testimoni</h2><p class="text-soft">Jadilah yang pertama berbagi pengalaman belajar. Testimoni akan muncul setelah ditinjau admin.</p></div>
        @endif
        @if ($canWriteTestimonial)
        <section class="card testimonial-page__form" id="kirim-testimoni" aria-labelledby="testimonial-form-title">
            <h2 id="testimonial-form-title">Bagikan Pengalamanmu</h2>
            <p class="text-soft">Nama, instansi, dan pengalamanmu dapat ditampilkan kepada publik setelah ditinjau admin. Hindari mencantumkan informasi pribadi lainnya.</p>
            <form method="POST" action="{{ route('testimonials.store') }}" class="testimonial-page__fields">
                @csrf
                <x-testimonial-fields :testimonial="auth()->user()" :readonly-name="true" />
                <button class="btn btn--primary self-start" type="submit">Kirim Testimoni <svg data-icon="arrow-right"></svg></button>
            </form>
        </section>
        @else
        <div class="card testimonial-page__form" id="kirim-testimoni">
            <h3>Bagikan pengalaman setelah membeli paket</h3>
            <p class="text-soft">Testimoni hanya dapat ditulis oleh peserta yang sudah login, memverifikasi email, dan pernah membeli paket berbayar.</p>
            <div class="btn-row" style="margin-top:1.5rem">
                @guest
                    <a class="btn btn--primary" href="{{ route('login') }}">Masuk untuk Menulis</a>
                @else
                    @if (! auth()->user()->hasVerifiedEmail())
                        <a class="btn btn--primary" href="{{ route('verification.notice') }}">Verifikasi Email</a>
                    @endif
                @endguest
                <a class="btn btn--outline" href="{{ route('home') }}#paket">Lihat Paket</a>
            </div>
        </div>
        @endif
    </div>
</section>
