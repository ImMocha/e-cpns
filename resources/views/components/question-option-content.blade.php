@props(['index' => 0, 'value' => '', 'imageUrl' => '', 'source' => null, 'removeImage' => false])
<div class="question-option-content" data-option-content data-existing-image="{{ $imageUrl }}">
    <textarea class="textarea" name="options[]" rows="2" maxlength="2000" aria-label="Pilihan {{ chr(65 + $index) }}" @required(!$imageUrl)>{{ $value }}</textarea>
    <input type="hidden" name="option_image_sources[]" value="{{ $source }}" data-option-image-source>
    <label class="question-option-upload"><span data-option-image-label>Gambar pilihan {{ chr(65 + $index) }} <span class="label__opt">(opsional)</span></span><input class="input" type="file" name="option_images[{{ $index }}]" accept="image/jpeg,image/png,image/webp" data-option-image></label>
    <label class="check" data-existing-option-image-control @if(!$imageUrl) hidden @endif><input type="checkbox" name="remove_option_images[{{ $index }}]" value="1" data-remove-option-image @checked($removeImage)> Hapus gambar lama</label>
    <img class="question-option-image question-option-thumbnail" @if($imageUrl) src="{{ $imageUrl }}" @endif alt="Gambar pilihan {{ chr(65 + $index) }}" data-option-image-preview @if(!$imageUrl) hidden @endif>
    <p class="question-error" data-option-image-error role="alert" hidden></p>
</div>
