@props(['scope', 'label', 'sectionId' => null, 'topicId' => null])
<form method="POST" action="{{ route('admin.materials.publish') }}" data-content-confirm data-confirm-title="Terbitkan materi?" data-confirm-body="{{ $label }}: semua draft dalam cakupan ini akan diterbitkan, termasuk pada halaman lain. Draft tanpa sumber dilewati. Filter pencarian dan format tidak membatasi aksi ini." data-confirm-ok="Ya, terbitkan">
  @csrf
  <input type="hidden" name="scope" value="{{ $scope }}">
  @if($sectionId)<input type="hidden" name="section_id" value="{{ $sectionId }}">@endif
  @if($topicId)<input type="hidden" name="topic_id" value="{{ $topicId }}">@endif
  <button class="btn btn--ghost btn--sm" type="submit"><svg data-icon="send"></svg><span>{{ $label }}</span></button>
</form>
