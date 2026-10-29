@extends('layouts.admin.main')
@section('title', ($testimonial->exists ? 'Edit Testimoni' : 'Tambah Testimoni') . ' — Ganesha Nusa')
@section('content')
    <nav class="crumbs"><a href="{{ route('admin.testimonials.index') }}">Testimoni</a><span class="crumbs__sep"><svg data-icon="chevron-right"></svg></span><span>{{ $testimonial->exists ? 'Edit' : 'Tambah' }}</span></nav>
    <header class="page-head"><h1>{{ $testimonial->exists ? 'Edit Testimoni' : 'Tambah Testimoni' }}</h1><p>Kelola pengalaman peserta dan tentukan apakah testimoni ditampilkan kepada publik.</p></header>
    <form class="card card--pop max-w-3xl stack" method="POST" action="{{ $testimonial->exists ? route('admin.testimonials.update', $testimonial) : route('admin.testimonials.store') }}">
        @csrf
        @if ($testimonial->exists) @method('PUT') @endif
        <x-testimonial-fields :testimonial="$testimonial" />
        <div class="field{{ $errors->has('is_visible') ? ' is-invalid' : '' }}">
            <label class="label" for="testimonial-visibility">Status Visibilitas</label>
            <select class="select" id="testimonial-visibility" name="is_visible" required @if ($errors->has('is_visible')) aria-invalid="true" aria-describedby="testimonial-visibility-error" @endif>
                <option value="0" @selected((string) old('is_visible', (int) $testimonial->is_visible) === '0')>Tersembunyi (hidden)</option>
                <option value="1" @selected((string) old('is_visible', (int) $testimonial->is_visible) === '1')>Ditampilkan (visible)</option>
            </select>
            @error('is_visible')<span class="field__error" id="testimonial-visibility-error">{{ $message }}</span>@enderror
        </div>
        <div class="btn-row"><button class="btn btn--primary" type="submit">Simpan Testimoni</button><a class="btn btn--ghost" href="{{ route('admin.testimonials.index') }}">Batal</a></div>
    </form>
@endsection
