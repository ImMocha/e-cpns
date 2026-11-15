@props(['name', 'label', 'value' => '', 'imageUrls' => []])
@php($value = is_string($value) ? $value : '')
<div class="question-rich-text" data-question-rich-text data-name="{{ $name }}" @if($name === 'explanation') data-inline-image-urls="{{ json_encode((object) $imageUrls, JSON_UNESCAPED_SLASHES) }}" data-max-inline-images="{{ \App\QuestionExplanation::MAX_IMAGES }}" @endif>
    <label class="label" for="{{ $name }}" id="{{ $name }}-label">{{ $label }}</label>
    <div class="article-toolbar" role="toolbar" aria-label="Format {{ strtolower($label) }}" hidden data-rich-toolbar>
        <button type="button" data-rich-command="bold" title="Bold (Ctrl+B)" aria-label="Bold {{ strtolower($label) }}"><strong>B</strong></button>
        <button type="button" data-rich-command="italic" title="Italic (Ctrl+I)" aria-label="Italic {{ strtolower($label) }}"><em>I</em></button>
        <button type="button" data-rich-command="underline" title="Underline (Ctrl+U)" aria-label="Underline {{ strtolower($label) }}"><u>U</u></button>
        <button type="button" data-rich-command="insertUnorderedList" aria-label="Daftar poin {{ strtolower($label) }}">• ≡</button>
        <button type="button" data-rich-command="insertOrderedList" aria-label="Daftar angka {{ strtolower($label) }}">1.</button>
        @if($name === 'explanation')
            <button type="button" data-rich-align="left" title="Rata kiri" aria-label="Rata kiri"><svg data-icon="align-left" class="size-4" aria-hidden="true"></svg></button>
            <button type="button" data-rich-align="center" title="Rata tengah" aria-label="Rata tengah"><svg data-icon="align-center" class="size-4" aria-hidden="true"></svg></button>
            <button type="button" data-rich-align="right" title="Rata kanan" aria-label="Rata kanan"><svg data-icon="align-right" class="size-4" aria-hidden="true"></svg></button>
            <button type="button" data-rich-choose-images class="w-auto! px-2" title="Sisipkan gambar dari file">+ Gambar</button>
        @endif
        <button type="button" data-rich-command="undo" aria-label="Undo {{ strtolower($label) }}">↶</button>
        <button type="button" data-rich-command="redo" aria-label="Redo {{ strtolower($label) }}">↷</button>
        <button type="button" data-rich-insert-fraction title="Sisipkan contoh pecahan LaTeX">½ <span class="sr-only">Sisipkan pecahan pada {{ strtolower($label) }}</span></button>
    </div>
    <textarea class="textarea" id="{{ $name }}" name="{{ $name }}" rows="6" maxlength="50000" aria-describedby="{{ $name }}-math-hint" data-rich-source>{{ $value }}</textarea>
    <div class="article-prose question-editable" contenteditable="true" role="textbox" aria-multiline="true" aria-labelledby="{{ $name }}-label" aria-describedby="{{ $name }}-math-hint{{ $name === 'explanation' ? ' explanation-image-hint' : '' }}" hidden data-rich-content></div>
    @if($name === 'explanation')
        <input type="file" accept="image/jpeg,image/png,image/webp" multiple hidden data-rich-image-picker>
        <p class="field__hint" id="explanation-image-hint">Klik di posisi yang diinginkan, lalu tempel gambar dengan Ctrl+V atau ⌘+V. Klik gambar untuk memilihnya, lalu atur perataan, hapus, atau pindahkan dengan potong dan tempel. Maks. {{ \App\QuestionExplanation::MAX_IMAGES }} gambar JPG, PNG, atau WebP, masing-masing 2 MB.</p>
        <p class="field__hint">Untuk gambar tanpa latar, gunakan PNG atau WebP transparan. Kalau hasil tempel masih berlatar, sisipkan file aslinya dengan tombol + Gambar.</p>
        <p class="question-error" role="alert" hidden data-rich-image-error></p>
    @endif
    <p class="field__hint" id="{{ $name }}-math-hint">Tulis LaTeX seperti <code>\(\frac{1}{2}\)</code> di dalam kalimat atau <code>\[...\]</code> di baris tersendiri. Cek hasilnya di pratinjau.</p>
</div>

