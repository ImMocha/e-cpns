@props(['placeholder' => 'Cari materi, soal, tryout…'])

<div class="appbar__search" data-navbar-search>
    <div class="appbar__search-field" data-navbar-search-field aria-hidden="true">
        <input class="input" id="navbar-search-input" type="search" placeholder="{{ $placeholder }}" aria-label="Pencarian" data-navbar-search-input disabled>
    </div>
    <button class="appbar__icon appbar__search-toggle" type="button" data-navbar-search-toggle
            aria-expanded="false" aria-controls="navbar-search-input" aria-label="Buka pencarian">
        <svg class="appbar__search-open" data-icon="search"></svg>
        <svg class="appbar__search-close" data-icon="close"></svg>
    </button>
</div>
