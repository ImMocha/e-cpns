@php($selectedTopic = $selectedTopic ?? null)
@php($sectionParams = array_filter(['section_id' => $selectedSection?->id, 'topic_id' => $selectedTopic?->id]))
<div class="content-toolbar">
  <nav class="content-tabs" aria-label="Kelola konten belajar">
    <a href="{{ route('admin.materials.index', $sectionParams) }}" class="{{ request()->routeIs('admin.materials.*') && ! request()->routeIs('admin.materials.quizzes.*') ? 'is-active' : '' }}" @if(request()->routeIs('admin.materials.*') && ! request()->routeIs('admin.materials.quizzes.*')) aria-current="page" @endif><svg data-icon="book"></svg> Materi</a>
    <a href="{{ route('admin.topics.index', array_filter(['section_id' => $selectedSection?->id])) }}" class="{{ request()->routeIs('admin.topics.*') ? 'is-active' : '' }}" @if(request()->routeIs('admin.topics.*')) aria-current="page" @endif><svg data-icon="layers"></svg> Topik</a>
    <a href="{{ route('admin.materials.quizzes.index', $sectionParams) }}" class="{{ request()->routeIs('admin.materials.quizzes.*') ? 'is-active' : '' }}" @if(request()->routeIs('admin.materials.quizzes.*')) aria-current="page" @endif><svg data-icon="list-checks"></svg> Latihan</a>
  </nav>
  @if(request()->routeIs('admin.topics.*'))
    <form method="GET" action="{{ route('admin.topics.index') }}" class="content-field-picker">
      <label for="workspace-section">Bidang</label>
      <select id="workspace-section" name="section_id" class="select" onchange="this.form.requestSubmit()">
        <option value="">Semua bidang</option>
        @foreach($sections as $section)
          <option value="{{ $section->id }}" @selected($selectedSection?->id === $section->id)>{{ $section->name }} · {{ $section->topics_count }} topik{{ $section->is_active ? '' : ' · nonaktif' }}</option>
        @endforeach
      </select>
      <button class="btn btn--ghost btn--sm" type="submit" aria-label="Tampilkan bidang"><svg data-icon="arrow-right"></svg></button>
    </form>
  @endif
</div>
