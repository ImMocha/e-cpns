<aside class="card topic-study__sidebar" aria-label="Daftar materi topik">
  <div class="topic-study__intro">
    <a class="text-sm" href="{{ route('materials.index', ['section_id' => $material->section_id]) }}"><svg data-icon="arrow-left"></svg> Semua topik</a>
    <span class="eyebrow">{{ $material->section?->name }}</span>
    <h2>{{ $material->topic?->name ?? 'Materi belajar' }}</h2>
    @if ($material->topic?->description)<p class="text-sm text-soft">{{ $material->topic->description }}</p>@endif
    <div class="flex items-center justify-between text-xs text-soft">
      <span>{{ $lessons->count() }} materi</span>
      <span>{{ count($completedMaterialIds) }} selesai</span>
    </div>
    <progress class="topic-study__progress" value="{{ count($completedMaterialIds) }}" max="{{ max(1, $lessons->count()) }}" aria-label="Progres topik"></progress>
  </div>
  <nav class="topic-study__lessons" aria-label="Pilih materi">
    @foreach ($lessons as $lesson)
      @php($open = $canOpen($lesson))
      <a class="topic-study__lesson {{ $lesson->is($material) ? 'is-active' : '' }}"
         href="{{ route('materials.topic', ['topic' => $lesson->topic_id, 'material' => $lesson->id, 'show_drafts' => $showDrafts ? 1 : null]) }}"
         @if ($lesson->is($material)) aria-current="page" @endif>
        <span class="topic-study__lesson-number">@if (isset($completedMaterialIds[$lesson->id]))<svg data-icon="check"></svg>@else{{ $loop->iteration }}@endif</span>
        <span class="topic-study__lesson-info">
          <strong>{{ $lesson->title }}</strong>
          <span class="text-xs text-soft"><svg data-icon="{{ $open ? $lesson->type->icon() : 'lock' }}"></svg>
            {{ $open ? ($lesson->humanDuration() ?? ($lesson->page_count ? $lesson->page_count.' halaman' : 'PDF')) : 'Premium · Terkunci' }}
            @if (isset($completedMaterialIds[$lesson->id])) · Selesai @endif
            @if (auth()->user()->isAdmin()) · {{ $lesson->status->label() }} @endif
          </span>
        </span>
      </a>
    @endforeach
  </nav>
  @if ($material->topic && $topicQuizzes->isNotEmpty())
    <div class="topic-study__quizzes">
      <x-topic-quizzes :topic="$material->topic" :banks="$topicQuizzes" />
    </div>
  @endif
  @if(auth()->user()->isAdmin() && $material->topic)
    <div class="topic-study__admin stack" style="--stack:.75rem">
      <x-material-publish scope="topic" :topic-id="$material->topic_id" :label="'Terbitkan topik: '.$material->topic->name" />
      <form method="POST" action="{{ route('admin.materials.status', $material) }}">
        @csrf @method('PATCH')
        <input type="hidden" name="member_listing" value="1">
        <button class="btn btn--ghost btn--sm" type="submit">{{ $material->isPublished() ? 'Jadikan draft' : 'Terbitkan' }}</button>
      </form>
      <a class="btn btn--ghost btn--sm" href="{{ route('admin.materials.edit', $material) }}">Edit materi aktif</a>
    </div>
  @endif
</aside>
