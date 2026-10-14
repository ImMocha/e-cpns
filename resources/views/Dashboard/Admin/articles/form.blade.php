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
          enctype="multipart/form-data" data-article-editor class="article-workspace" novalidate>
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

        <section class="card article-details">
            <div class="card__head">
                <div><div class="card__title">Informasi artikel</div><div class="card__sub">Judul, bidang, dan cover yang akan dilihat peserta</div></div>
                <span class="badge {{ $article->status->badgeClass() }}">{{ $article->status->label() }}</span>
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
                        <input class="input" id="article-slug" name="slug" maxlength="180" value="{{ old('slug', $article->slug) }}" placeholder="Otomatis dari judul">
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
        </section>

        <div class="article-editor-grid">
            <section class="card article-writing-panel" aria-label="Editor artikel">
                <div class="card__head article-panel-head">
                    <div><div class="card__title">Isi artikel</div><div class="card__sub">Blok teks untuk menerapkan format</div></div>
                    <span class="badge badge--ink badge--flat"><svg data-icon="pen-line"></svg> Editor</span>
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
                    <div class="article-toolbar-group">
                        <button type="button" data-editor-link title="Sisipkan link" aria-label="Sisipkan link"><svg data-icon="link"></svg></button>
                        <button type="button" data-command="unlink" title="Hapus link" aria-label="Hapus link">⛓</button>
                        <button type="button" data-command="insertHorizontalRule" title="Garis pemisah" aria-label="Garis pemisah">―</button>
                        <button type="button" data-editor-table title="Tabel 2 × 3" aria-label="Sisipkan tabel"><svg data-icon="grid"></svg></button>
                        <button type="button" data-command="removeFormat" title="Hapus format teks" aria-label="Hapus format teks">Tx</button>
                        <button type="button" data-command="undo" title="Undo (Ctrl+Z)" aria-label="Undo">↶</button>
                        <button type="button" data-command="redo" title="Redo (Ctrl+Y)" aria-label="Redo">↷</button>
                    </div>
                </div>
                <label class="sr-only" for="article-body-source">Isi artikel dalam HTML</label>
                <textarea class="textarea" name="body" id="article-body-source" rows="16" data-editor-source>{{ old('body', $article->safeBody()) }}</textarea>
                <div class="article-prose article-editable" contenteditable="true" role="textbox" aria-label="Isi artikel" aria-multiline="true" aria-describedby="article-editor-help" spellcheck="true" data-editor-content hidden></div>
                <div class="article-editor-footer"><span data-word-count>0 kata</span><span id="article-editor-help">Ctrl+B bold · Ctrl+I italic · Ctrl+Z undo</span></div>
                <p class="article-editor-message" data-editor-message role="status" hidden></p>
                <noscript><p class="hint">Aktifkan JavaScript untuk menggunakan toolbar dan preview langsung. Form HTML tetap bisa disimpan.</p></noscript>
            </section>

            <section class="card article-preview-panel" aria-label="Preview artikel">
                <div class="card__head article-panel-head">
                    <div><div class="card__title">Preview langsung</div><div class="card__sub">Tampilan artikel untuk peserta</div></div>
                    <span class="article-live"><span></span> Live</span>
                </div>
                <div class="article-preview-scroll">
                    <article class="article-paper">
                        <span class="badge badge--sky badge--flat" data-preview-section>Bidang artikel</span>
                        <h2 class="article-paper-title" data-preview-title>Judul artikel Anda</h2>
                        <p class="article-paper-excerpt" data-preview-excerpt></p>
                        <div class="article-paper-meta"><span>{{ $article->author?->name ?? auth()->user()->name }}</span><span>·</span><span data-reading-time>1 menit baca</span></div>
                        <img class="article-paper-cover" data-preview-cover data-existing-cover="{{ $article->coverUrl() }}" alt="Cover artikel" hidden>
                        <div class="article-prose" data-preview-body><p class="article-placeholder">Mulai menulis untuk melihat preview artikel di sini…</p></div>
                    </article>
                </div>
            </section>
        </div>
        <div class="article-save-note" data-save-note role="status">{{ $article->exists ? 'Perubahan terakhir disimpan '.$article->updated_at->format('d M Y, H:i') : 'Artikel baru · belum disimpan' }}</div>
    </form>

    <dialog class="article-link-dialog" data-link-dialog aria-labelledby="article-link-title">
        <form method="dialog" data-link-form>
            <h2 id="article-link-title">Sisipkan link</h2>
            <p>Pilih teks yang ingin diberi link, lalu masukkan alamatnya.</p>
            <label class="label" for="article-link-url">Alamat link</label>
            <input class="input" id="article-link-url" type="url" placeholder="https://contoh.com" required>
            <p class="hint" data-link-error role="alert"></p>
            <div class="btn-row"><button class="btn btn--ghost" type="button" data-link-cancel>Batal</button><button class="btn btn--primary" type="submit">Terapkan link</button></div>
        </form>
    </dialog>
@endsection
