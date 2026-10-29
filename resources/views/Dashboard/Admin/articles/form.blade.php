@extends('layouts.admin.main')

@section('title', ($article->exists ? 'Edit Artikel' : 'Tulis Artikel').' — Ganesha Nusa')

@section('content')
    <nav class="crumbs" aria-label="Breadcrumb">
        <a href="{{ route('admin.dashboard') }}">Admin Dashboard</a>
        <span class="crumbs__sep"><svg data-icon="chevron-right"></svg></span>
        <a href="{{ route('admin.articles.index') }}">Artikel</a>
        <span class="crumbs__sep"><svg data-icon="chevron-right"></svg></span>
        <span class="crumbs__current">{{ $article->exists ? 'Edit Artikel' : 'Tulis Artikel' }}</span>
    </nav>

    <form method="POST" action="{{ $article->exists ? route('admin.articles.update', $article) : route('admin.articles.store') }}"
          enctype="multipart/form-data" data-article-editor data-draft-key="article-draft:{{ auth()->id() }}:{{ $article->id ?? 'new' }}" data-saved="{{ session('success') ? 'true' : 'false' }}" data-version="{{ $article->updated_at?->toIso8601String() }}" data-image-upload-url="{{ route('admin.articles.images.store') }}" class="article-workspace" novalidate>
        @csrf
        @if ($article->exists) @method('PUT') @endif
        <input type="hidden" name="status" value="{{ old('status', $article->status->value) }}">

        <header class="page-head page-head__row">
            <div>
                <span class="eyebrow">Ruang menulis</span>
                <h1 style="margin-top:.5rem">{{ $article->exists ? 'Edit Artikel' : 'Tulis Artikel Baru' }}</h1>
                <p>Tulis di kiri, lihat hasilnya langsung di kanan.</p>
            </div>
            <div class="btn-row">
                <a class="btn btn--ghost" href="{{ route('admin.articles.index') }}">Kembali</a>
                <button class="btn btn--outline" type="submit" name="status" value="draft"><svg data-icon="pencil"></svg> Simpan draft</button>
                <button class="btn btn--primary" type="submit" name="status" value="published"><svg data-icon="send"></svg> {{ $article->status === \App\Enums\ArticleStatus::Published ? 'Simpan & terbitkan' : 'Terbitkan artikel' }}</button>
            </div>
        </header>

        @if ($errors->any())
            <div class="article-errors" role="alert">
                <strong>Artikel belum tersimpan. Periksa kembali:</strong>
                <ul>@foreach ($errors->all() as $message)<li>{{ $message }}</li>@endforeach</ul>
            </div>
        @endif

        <div class="article-recovery" data-draft-recovery hidden role="status">
            <p data-draft-description></p>
            <div class="btn-row"><button type="button" class="btn btn--outline btn--sm" data-draft-restore>Pulihkan draft lokal</button><button type="button" class="btn btn--ghost btn--sm" data-draft-discard>Abaikan draft lokal</button></div>
        </div>

        <section class="card article-details">
            <div class="card__head">
                <div><div class="card__title">Informasi artikel</div><div class="card__sub">Judul, bidang, dan cover yang akan dilihat peserta</div></div>
                <span class="badge {{ $article->status->badgeClass() }}">{{ $article->published_at?->isFuture() ? 'Terjadwal' : $article->status->label() }}</span>
            </div>
            <div class="grid grid-cols-1 gap-5 md:grid-cols-2">
                <div class="field">
                    <label class="label" for="article-title">Judul artikel <span class="text-primary">*</span></label>
                    <input class="input" id="article-title" name="title" maxlength="180" value="{{ old('title', $article->title) }}" placeholder="Tulis judul yang menarik" required>
                </div>
                <div class="field">
                    <label class="label" for="article-section">Bidang <span class="text-primary">*</span></label>
                    <select class="select" id="article-section" name="section_id" required>
                        <option value="">Pilih bidang artikel</option>
                        @foreach ($sections as $section)
                            <option value="{{ $section->id }}" @selected((string) old('section_id', $article->section_id) === (string) $section->id)>{{ $section->name }}{{ $section->is_active ? '' : ' (nonaktif)' }}</option>
                        @endforeach
                    </select>
                    @if ($sections->isEmpty())<p class="hint">Tambahkan bidang melalui menu Bidang terlebih dahulu.</p>@endif
                </div>
                <div class="field">
                    <label class="label" for="article-excerpt">Ringkasan</label>
                    <textarea class="textarea" id="article-excerpt" name="excerpt" rows="2" maxlength="500" placeholder="Pengantar singkat sebelum peserta mulai membaca">{{ old('excerpt', $article->excerpt) }}</textarea>
                </div>
                <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                    <div class="field">
                        <label class="label" for="article-slug">Slug</label>
                        <input class="input" id="article-slug" name="slug" data-auto-slug="articles" data-slug-url="{{ route('admin.slugs.preview') }}" data-slug-id="{{ $article->id }}" maxlength="180" value="{{ old('slug', $article->slug) }}" placeholder="Otomatis dari judul">
                    </div>
                    <div class="field">
                        <label class="label" for="article-cover">Gambar cover</label>
                        <input class="input article-cover-input" id="article-cover" type="file" name="cover" accept="image/jpeg,image/png,image/webp">
                        <p class="hint">JPG, PNG, WebP · maksimal 5 MB</p>
                        @if ($article->cover_path)
                            <label class="check"><input type="checkbox" name="remove_cover" value="1" data-remove-cover @checked(old('remove_cover'))> Hapus cover lama</label>
                        @endif
                    </div>
                </div>
            </div>
            <div class="grid grid-cols-1 gap-5 md:grid-cols-2 mt-5">
                <div class="field">
                    <label class="label" for="article-cover-source">Sumber gambar cover</label>
                    <input class="input" id="article-cover-source" name="cover_source" maxlength="255" value="{{ old('cover_source', $article->cover_source) }}" placeholder="Nama fotografer, pemilik gambar, atau Dokumentasi pribadi">
                    <p class="hint">Kredit ditampilkan di bawah cover. Cantumkan pemilik dan lisensi bila diperlukan.</p>
                </div>
                <div class="field">
                    <label class="label" for="article-cover-source-url">Tautan sumber cover (opsional)</label>
                    <input class="input" id="article-cover-source-url" type="url" name="cover_source_url" maxlength="2048" value="{{ old('cover_source_url', $article->cover_source_url) }}" placeholder="https://situs-sumber.com/foto">
                </div>
            </div>
            <div class="field mt-5">
                <label class="label" for="article-publish-at">Jadwal terbit (opsional · WITA / UTC+8)</label>
                <input class="input" id="article-publish-at" type="datetime-local" name="publish_at" value="{{ old('publish_at', $article->published_at?->isFuture() ? $article->published_at->copy()->timezone('Asia/Makassar')->format('Y-m-d\TH:i') : '') }}">
                <p class="hint">Kosongkan untuk terbit sekarang. Jika memilih waktu mendatang, artikel otomatis tersedia pada waktunya setelah menekan Terbitkan artikel. Simpan draft tidak menerbitkan artikel.</p>
                <p class="hint" data-publish-note role="status"></p>
            </div>
        </section>

        <div class="article-editor-grid">
            <section class="card article-writing-panel" aria-label="Editor artikel">
                <div class="card__head article-panel-head">
                    <div><div class="card__title">Isi artikel</div><div class="card__sub">Blok teks untuk menerapkan format</div></div>
                    <div class="btn-row" data-editor-modes hidden><button type="button" class="btn btn--ghost btn--sm" data-editor-mode="visual" aria-pressed="true">Visual</button><button type="button" class="btn btn--ghost btn--sm" data-editor-mode="html" aria-pressed="false">HTML</button></div>
                </div>
                <div class="article-toolbar" role="toolbar" aria-label="Format artikel" hidden data-editor-toolbar>
                    <div class="article-toolbar-group">
                        <select aria-label="Gaya paragraf" data-command-select="formatBlock">
                            <option value="p">Paragraf</option><option value="h2">Heading 2</option><option value="h3">Heading 3</option><option value="h4">Heading 4</option><option value="blockquote">Kutipan</option>
                        </select>
                        <select aria-label="Ukuran teks" data-command-select="fontSize">
                            <option value="1">12 px</option><option value="2">14 px</option><option value="3" selected>16 px</option><option value="4">18 px</option><option value="5">24 px</option><option value="6">32 px</option><option value="7">40 px</option>
                        </select>
                    </div>
                    <div class="article-toolbar-group">
                        <button type="button" data-command="bold" title="Bold (Ctrl+B)" aria-label="Bold" aria-pressed="false"><strong>B</strong></button>
                        <button type="button" data-command="italic" title="Italic (Ctrl+I)" aria-label="Italic" aria-pressed="false"><em>I</em></button>
                        <button type="button" data-command="underline" title="Underline (Ctrl+U)" aria-label="Underline" aria-pressed="false"><u>U</u></button>
                        <button type="button" data-command="strikeThrough" title="Coret" aria-label="Coret" aria-pressed="false"><s>S</s></button>
                        <button type="button" data-command="subscript" title="Subskrip" aria-label="Subskrip" aria-pressed="false">x₂</button>
                        <button type="button" data-command="superscript" title="Superskrip" aria-label="Superskrip" aria-pressed="false">x²</button>
                        <label class="article-color" title="Warna teks">A<input type="color" value="#162b3a" aria-label="Warna teks" data-command-color="foreColor"></label>
                        <label class="article-color article-color--highlight" title="Highlight teks">H<input type="color" value="#fff3bf" aria-label="Highlight teks" data-command-color="hiliteColor"></label>
                    </div>
                    <div class="article-toolbar-group">
                        @foreach (['justifyLeft' => ['align-left', 'Rata kiri'], 'justifyCenter' => ['align-center', 'Rata tengah'], 'justifyRight' => ['align-right', 'Rata kanan'], 'justifyFull' => ['align-justify', 'Rata kanan kiri']] as $command => [$icon, $label])
                            <button type="button" data-command="{{ $command }}" title="{{ $label }}" aria-label="{{ $label }}" aria-pressed="false"><svg data-icon="{{ $icon }}"></svg></button>
                        @endforeach
                        <button type="button" data-command="insertUnorderedList" title="Bullet list" aria-label="Bullet list" aria-pressed="false">•≡</button>
                        <button type="button" data-command="insertOrderedList" title="Numbered list" aria-label="Numbered list" aria-pressed="false">1.</button>
                    </div>
                    <div class="article-toolbar-group article-list-toolbar">
                        <select aria-label="Format numbering" data-list-format>
                            <option value="">Format numbering…</option>
                            @foreach (['1' => ['1., 2., 3.', '1), 2), 3)'], 'a' => ['a., b., c.', 'a), b), c)'], 'A' => ['A., B., C.', 'A), B), C)'], 'i' => ['i., ii., iii.', 'i), ii), iii)'], 'I' => ['I., II., III.', 'I), II), III)']] as $type => [$period, $paren])
                                <option value="{{ $type }}:period">{{ $period }}</option><option value="{{ $type }}:paren">{{ $paren }}</option>
                            @endforeach
                        </select>
                        <button type="button" data-list-level="indent" title="Tambah tingkat (Tab)" aria-label="Tambah tingkat daftar">→</button>
                        <button type="button" data-list-level="outdent" title="Kurangi tingkat (Shift+Tab)" aria-label="Kurangi tingkat daftar">←</button>
                        <label class="article-list-start">Mulai <input type="number" min="1" max="9999" value="1" aria-label="Nomor awal daftar" data-list-start disabled></label>
                        <button type="button" data-list-restart title="Mulai ulang dari 1" aria-label="Mulai ulang numbering dari 1" disabled>↺</button>
                        <button type="button" data-list-example title="Sisipkan contoh numbering bertingkat" aria-label="Sisipkan contoh numbering bertingkat">1↳a</button>
                    </div>
                    <div class="article-toolbar-group">
                        <button type="button" data-editor-link title="Sisipkan link" aria-label="Sisipkan link"><svg data-icon="link"></svg></button>
                        <button type="button" data-editor-image title="Sisipkan gambar dan sumber" aria-label="Sisipkan gambar dan sumber"><svg data-icon="image"></svg></button>
                        <button type="button" data-command="unlink" title="Hapus link" aria-label="Hapus link">⛓</button>
                        <button type="button" data-command="insertHorizontalRule" title="Garis pemisah" aria-label="Garis pemisah">―</button>
                        <button type="button" data-editor-table title="Sisipkan atau ubah tabel" aria-label="Sisipkan atau ubah tabel"><svg data-icon="grid"></svg></button>
                        <button type="button" data-command="removeFormat" title="Hapus format teks" aria-label="Hapus format teks">Tx</button>
                        <button type="button" data-command="undo" title="Undo (Ctrl+Z)" aria-label="Undo">↶</button>
                        <button type="button" data-command="redo" title="Redo (Ctrl+Y)" aria-label="Redo">↷</button>
                    </div>
                    <div class="article-toolbar-group article-toolbar-blocks">
                        <select aria-label="Sisipkan blok" data-insert-block><option value="">Sisipkan blok…</option><option value="info">Informasi</option><option value="tip">Tips belajar</option><option value="warning">Perhatian</option><option value="code">Blok kode</option><option value="faq">Tanya jawab</option></select>
                        <button type="button" data-editor-fullscreen title="Fokus menulis" aria-label="Fokus menulis" aria-pressed="false">⛶</button>
                    </div>
                </div>
                <label class="sr-only" for="article-body-source">Isi artikel dalam HTML</label>
                <textarea class="textarea" name="body" id="article-body-source" rows="16" data-editor-source>{{ old('body', $article->safeBody()) }}</textarea>
                <div class="article-prose article-editable" contenteditable="true" role="textbox" aria-label="Isi artikel" aria-multiline="true" aria-describedby="article-editor-help" spellcheck="true" data-editor-content hidden></div>
                <div class="article-editor-footer"><span data-word-count>0 kata</span><span data-character-count>0 karakter</span><span id="article-editor-help">Tab / Shift+Tab: tingkat daftar · Pilih format numbering per tingkat · Ctrl+S simpan draft · Esc keluar fokus</span></div>
                <p class="article-editor-message" data-editor-message role="status" hidden></p>
                <noscript><p class="hint">Aktifkan JavaScript untuk menggunakan toolbar dan preview langsung. Form HTML tetap bisa disimpan.</p></noscript>
            </section>

            <section class="card article-preview-panel" aria-label="Preview artikel">
                <div class="card__head article-panel-head">
                    <div><div class="card__title">Preview langsung</div><div class="card__sub">Tampilan artikel untuk peserta</div></div>
                    <div class="btn-row" data-preview-controls hidden><button type="button" class="btn btn--ghost btn--sm" data-preview-size="desktop" aria-pressed="true">Desktop</button><button type="button" class="btn btn--ghost btn--sm" data-preview-size="mobile" aria-pressed="false">Ponsel</button></div>
                </div>
                <div class="article-preview-scroll">
                    <article class="article-paper">
                        <span class="badge badge--sky badge--flat" data-preview-section>Bidang artikel</span>
                        <h2 class="article-paper-title" data-preview-title>Judul artikel Anda</h2>
                        <p class="article-paper-excerpt" data-preview-excerpt></p>
                        <div class="article-paper-meta"><span>{{ $article->author?->name ?? auth()->user()->name }}</span><span>·</span><span data-reading-time>1 menit baca</span></div>
                        <figure class="article-cover-figure" data-preview-cover-figure hidden>
                            <img class="article-paper-cover" data-preview-cover data-existing-cover="{{ $article->coverUrl() }}" alt="Cover artikel" hidden>
                            <figcaption class="article-image-credit" data-preview-cover-credit hidden></figcaption>
                        </figure>
                        <nav class="article-toc" aria-label="Daftar isi preview" data-preview-toc hidden></nav>
                        <div class="article-prose" data-preview-body><p class="article-placeholder">Mulai menulis untuk melihat preview artikel di sini…</p></div>
                    </article>
                </div>
            </section>
        </div>
        <div class="article-save-note" data-save-note role="status">@if ($article->exists)Perubahan terakhir disimpan <x-local-time :value="$article->updated_at" format="d M Y, H:i" />@else Artikel baru · belum disimpan @endif</div>
    </form>

    <dialog class="article-link-dialog" data-link-dialog aria-labelledby="article-link-title">
        <form method="dialog" data-link-form>
            <h2 id="article-link-title">Sisipkan link</h2>
            <p>Pilih teks yang ingin diberi link, lalu masukkan alamatnya.</p>
            <label class="label" for="article-link-url">Alamat link</label>
            <input class="input" id="article-link-url" type="text" inputmode="url" placeholder="https://contoh.com atau mailto:nama@contoh.com" required>
            <label class="check mt-3"><input type="checkbox" data-link-new-tab> Buka di tab baru</label>
            <p class="hint" data-link-error role="alert"></p>
            <div class="btn-row"><button class="btn btn--ghost" type="button" data-link-cancel>Batal</button><button class="btn btn--primary" type="submit">Terapkan link</button></div>
        </form>
    </dialog>

    <dialog class="article-link-dialog article-image-dialog" data-image-dialog aria-labelledby="article-image-title">
        <form data-image-form>
            <h2 id="article-image-title">Sisipkan gambar</h2>
            <p>Unggah gambar, lalu cantumkan sumbernya. Klik gambar di editor untuk mengubahnya kembali.</p>
            <div class="field">
                <label class="label" for="article-inline-image">File gambar</label>
                <input class="input article-cover-input" id="article-inline-image" type="file" accept="image/jpeg,image/png,image/webp">
                <p class="hint">JPG, PNG, WebP · maksimal 5 MB. Saat mengedit, kosongkan untuk memakai gambar yang sama.</p>
            </div>
            <div class="field">
                <label class="label" for="article-image-alt">Deskripsi gambar (teks alternatif)</label>
                <input class="input" id="article-image-alt" maxlength="255" placeholder="Jelaskan isi gambar untuk pembaca layar">
            </div>
            <div class="field">
                <label class="label" for="article-image-source">Sumber gambar</label>
                <input class="input" id="article-image-source" maxlength="255" placeholder="Nama fotografer, pemilik gambar, atau Dokumentasi pribadi">
            </div>
            <div class="field">
                <label class="label" for="article-image-caption">Keterangan gambar (opsional)</label>
                <input class="input" id="article-image-caption" maxlength="500" placeholder="Keterangan yang ditampilkan di bawah gambar">
            </div>
            <div class="grid grid-cols-2 gap-3">
                <div class="field"><label class="label" for="article-image-width">Lebar gambar</label><select class="select" id="article-image-width"><option value="100">100%</option><option value="75">75%</option><option value="50">50%</option><option value="25">25%</option></select></div>
                <div class="field"><label class="label" for="article-image-align">Posisi gambar</label><select class="select" id="article-image-align"><option value="center">Tengah</option><option value="left">Kiri</option><option value="right">Kanan</option></select></div>
            </div>
            <div class="field">
                <label class="label" for="article-image-source-url">Tautan sumber (opsional)</label>
                <input class="input" id="article-image-source-url" type="url" maxlength="2048" placeholder="https://situs-sumber.com/foto">
            </div>
            <p class="hint">Gunakan gambar sesuai izin atau lisensinya dan tuliskan kredit yang diminta pemilik.</p>
            <p class="article-image-error" data-image-error role="alert"></p>
            <div class="btn-row">
                <button class="btn btn--ghost text-danger" type="button" data-image-remove hidden>Hapus gambar</button>
                <button class="btn btn--ghost" type="button" data-image-cancel>Batal</button>
                <button class="btn btn--primary" type="submit" data-image-submit>Sisipkan gambar</button>
            </div>
        </form>
    </dialog>

    <dialog class="article-link-dialog" data-table-dialog aria-labelledby="article-table-title">
        <form data-table-form>
            <h2 id="article-table-title">Tabel artikel</h2>
            <div data-table-create>
                <div class="grid grid-cols-2 gap-3">
                    <div class="field"><label class="label" for="article-table-rows">Baris (termasuk judul)</label><input class="input" type="number" id="article-table-rows" min="1" max="30" value="3" required></div>
                    <div class="field"><label class="label" for="article-table-cols">Kolom</label><input class="input" type="number" id="article-table-cols" min="1" max="10" value="2" required></div>
                </div>
                <label class="check mt-3"><input type="checkbox" data-table-header checked> Baris pertama sebagai judul</label>
                <label class="label mt-3" for="article-table-caption">Judul tabel (opsional)</label><input class="input" id="article-table-caption" maxlength="255">
            </div>
            <div data-table-actions hidden class="article-table-actions">
                <p>Pilih sel pada tabel untuk menentukan letak baris atau kolom.</p>
                <button type="button" class="btn btn--outline btn--sm" data-table-action="row">Tambah baris di bawah</button>
                <button type="button" class="btn btn--outline btn--sm" data-table-action="column">Tambah kolom di kanan</button>
                <button type="button" class="btn btn--ghost btn--sm" data-table-action="remove-row">Hapus baris</button>
                <button type="button" class="btn btn--ghost btn--sm" data-table-action="remove-column">Hapus kolom</button>
                <button type="button" class="btn btn--ghost btn--sm text-danger" data-table-action="remove-table">Hapus tabel</button>
            </div>
            <p class="hint" data-table-error role="alert"></p>
            <div class="btn-row"><button type="button" class="btn btn--ghost" data-table-cancel>Tutup</button><button type="submit" class="btn btn--primary" data-table-submit>Sisipkan tabel</button></div>
        </form>
    </dialog>
@endsection
