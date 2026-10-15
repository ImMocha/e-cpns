@php
    $isMaterialPicker = $pickerType === 'materials';
    $currentSelection = old($isMaterialPicker ? 'material_ids' : 'exam_ids', $selectedIds);
    $currentSelection = is_array($currentSelection) ? $currentSelection : [];
@endphp
<section class="card question-form-stack" data-package-picker>
    <div class="exam-pool-summary">
        <div><h2 class="card__title">{{ $isMaterialPicker ? 'Materi & video' : 'Latihan & tryout' }}</h2><p class="card__sub">{{ $isMaterialPicker ? 'Pilih materi yang menjadi isi paket.' : 'Pilih sesi latihan dan tryout yang menjadi isi paket.' }}</p></div>
        <span class="badge badge--primary badge--flat" data-package-count role="status" aria-live="polite">{{ count($currentSelection) }} dipilih</span>
    </div>
    <div class="grid" style="--gap:.75rem" data-package-tools hidden>
        <div class="grid {{ $isMaterialPicker ? 'sm:grid-cols-2' : '' }}" style="--gap:.75rem">
            <div class="field">
                <label class="label" for="{{ $pickerType }}-search">Cari {{ $isMaterialPicker ? 'materi' : 'sesi' }}</label>
                <input class="input" type="search" id="{{ $pickerType }}-search" placeholder="Cari judul atau bidang…" autocomplete="off" data-package-search>
            </div>
            @if ($isMaterialPicker)
                <div class="field">
                    <label class="label" for="materials-topic">Topik materi</label>
                    <select class="select" id="materials-topic" data-package-topic>
                        <option value="">Semua topik</option>
                        @foreach ($materialTopics as $topic)<option value="{{ $topic->id }}">{{ $topic->section?->name ?? 'Tanpa bidang' }} — {{ $topic->name }}</option>@endforeach
                    </select>
                </div>
            @endif
        </div>
        <div class="btn-row flex-wrap">
            <button class="btn btn--ghost btn--sm" type="button" data-package-action="all">{{ $isMaterialPicker ? 'Semua materi' : 'Semua sesi' }}</button>
            @unless ($isMaterialPicker)
                <button class="btn btn--ghost btn--sm" type="button" data-package-action="quiz">Semua latihan</button>
                <button class="btn btn--ghost btn--sm" type="button" data-package-action="tryout">Semua tryout</button>
            @endunless
            <button class="btn btn--ghost btn--sm" type="button" data-package-action="premium">Semua premium</button>
            <button class="btn btn--primary btn--sm" type="button" data-package-action="filtered">Pilih hasil filter</button>
            <button class="btn btn--ghost btn--sm" type="button" data-package-action="clear">Kosongkan</button>
        </div>
        <p class="field__hint">{{ $isMaterialPicker ? 'Pilih satu topik lalu tekan Pilih hasil filter untuk menambahkan seluruh materinya.' : 'Gunakan pencarian lalu Pilih hasil filter untuk menambahkan beberapa sesi sekaligus.' }} Pilihan massal hanya menambahkan konten terbit yang tersedia.</p>
    </div>
    <div class="exam-question-pool">
        @foreach ($items as $item)
            @php
                $topicName = $isMaterialPicker ? $item->topic?->name : null;
                $sectionName = $isMaterialPicker ? $item->section?->name : null;
                $kind = $isMaterialPicker ? $item->type->value : $item->kind;
                $kindLabel = $isMaterialPicker ? $item->type->label() : ($item->kind === 'quiz' ? 'Latihan' : 'Tryout');
                $metadata = implode(' · ', array_filter([$sectionName, $topicName, $kindLabel, $item->access_level->label(), $item->status->label()]));
                $isAvailable = isset($availableIds[$item->id]);
            @endphp
            <label class="exam-pool-item" data-package-item data-kind="{{ $kind }}" data-access="{{ $item->access_level->value }}" data-topic="{{ $isMaterialPicker ? $item->topic_id : '' }}" data-available="{{ $isAvailable ? '1' : '0' }}" data-search="{{ $item->title.' '.$metadata }}">
                <input type="checkbox" name="{{ $isMaterialPicker ? 'material_ids' : 'exam_ids' }}[]" value="{{ $item->id }}" @checked(in_array($item->id, $currentSelection))>
                <span><strong>{{ $item->title }}</strong><span class="text-xs text-soft">{{ $metadata }}</span>@unless ($isAvailable)<span class="text-xs text-soft">Belum siap untuk paket aktif</span>@endunless</span>
            </label>
        @endforeach
        <p class="text-sm text-soft" data-package-empty @if ($items->isNotEmpty()) hidden @endif>Tidak ada konten yang sesuai dengan pencarian atau topik ini.</p>
    </div>
    <p class="field__hint">Pilihan baru diterapkan setelah menekan Simpan paket. {{ $isMaterialPicker ? 'Materi baru pada topik tersebut dapat ditambahkan lewat pengelolaan paket.' : 'Sesi gratis tetap bebas diakses dan tidak memotong kuota tryout.' }}</p>
    <noscript><p class="field__hint">Aktifkan JavaScript untuk memilih banyak konten sekaligus. Pilihan satu per satu tetap dapat digunakan.</p></noscript>
</section>
