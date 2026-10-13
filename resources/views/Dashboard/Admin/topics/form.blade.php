@extends('layouts.admin.main')
@section('title', ($topic->exists ? 'Edit Topik' : 'Tambah Topik') . ' — LONTAR')
@section('content')
    <nav class="crumbs"><a href="{{ route('admin.topics.index') }}">Topik Materi</a><span class="crumbs__sep"><svg data-icon="chevron-right"></svg></span><span>{{ $topic->exists ? 'Edit' : 'Tambah' }}</span></nav>
    <header class="page-head"><h1>{{ $topic->exists ? 'Edit Topik' : 'Tambah Topik' }}</h1><p>Satu topik berada dalam satu bidang dan dapat berisi materi PDF maupun video.</p></header>
    <form class="card card--pop" style="max-width:800px" method="POST" action="{{ $topic->exists ? route('admin.topics.update', $topic) : route('admin.topics.store') }}">
      @csrf
      <input type="hidden" name="workspace" value="1">
      @if ($topic->exists) @method('PUT') @endif
      <div class="stack" style="--stack:1.25rem">
        <div class="field{{ $errors->has('section_id') ? ' is-invalid' : '' }}">
          <label class="label" for="section_id">Bidang</label>
          <select class="select" id="section_id" name="section_id" required>
            <option value="">Pilih bidang...</option>
            @foreach ($sections as $section)
              <option value="{{ $section->id }}" @selected((string) old('section_id', $topic->section_id) === (string) $section->id)>{{ $section->name }}{{ $section->is_active ? '' : ' — nonaktif' }}</option>
            @endforeach
          </select>
          <span class="field__error"><svg data-icon="alert"></svg><span>{{ $errors->first('section_id') }}</span></span>
        </div>
        <div class="field{{ $errors->has('name') ? ' is-invalid' : '' }}">
          <label class="label" for="name">Nama Topik</label>
          <input class="input" id="name" name="name" value="{{ old('name', $topic->name) }}" placeholder="Contoh: Nasionalisme" required maxlength="120">
          <span class="field__error"><svg data-icon="alert"></svg><span>{{ $errors->first('name') }}</span></span>
        </div>
        <div class="field{{ $errors->has('description') ? ' is-invalid' : '' }}">
          <label class="label" for="description">Deskripsi <span class="label__opt">(opsional)</span></label>
          <textarea class="textarea" id="description" name="description" maxlength="1000">{{ old('description', $topic->description) }}</textarea>
          <span class="field__error"><svg data-icon="alert"></svg><span>{{ $errors->first('description') }}</span></span>
        </div>
        <div class="field{{ $errors->has('position') ? ' is-invalid' : '' }}">
          <label class="label" for="position">Urutan Topik</label>
          <input class="input" type="number" id="position" name="position" min="0" max="999" value="{{ old('position', $topic->position) }}">
          <span class="field__error"><svg data-icon="alert"></svg><span>{{ $errors->first('position') }}</span></span>
        </div>
        <label class="switch"><input type="checkbox" name="is_active" value="1" @checked(old('is_active', $topic->is_active))><span>Topik aktif</span></label>
        <p class="text-sm text-soft">Topik nonaktif beserta seluruh materinya disembunyikan dari peserta.</p>
        <div class="btn-row"><button class="btn btn--primary" type="submit">Simpan Topik</button><a class="btn btn--ghost" href="{{ route('admin.topics.index', array_filter(['section_id' => $topic->section_id])) }}">Batal</a></div>
      </div>
    </form>
@endsection
