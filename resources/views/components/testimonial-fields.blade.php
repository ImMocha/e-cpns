@props(['testimonial' => null, 'readonlyName' => false])

<div class="field{{ $errors->has('name') ? ' is-invalid' : '' }}">
    <label class="label" for="testimonial-name">Nama</label>
    <input class="input" id="testimonial-name" name="name" value="{{ $readonlyName ? $testimonial?->name : old('name', $testimonial?->name) }}" @readonly($readonlyName) required maxlength="120" autocomplete="name" @if ($errors->has('name')) aria-invalid="true" aria-describedby="testimonial-name-error" @endif>
    @error('name')<span class="field__error" id="testimonial-name-error">{{ $message }}</span>@enderror
</div>
<div class="field{{ $errors->has('institution') ? ' is-invalid' : '' }}">
    <label class="label" for="testimonial-institution">Instansi <span class="label__opt">(opsional)</span></label>
    <input class="input" id="testimonial-institution" name="institution" value="{{ old('institution', $testimonial?->institution) }}" maxlength="180" autocomplete="organization" @if ($errors->has('institution')) aria-invalid="true" aria-describedby="testimonial-institution-error" @endif>
    @error('institution')<span class="field__error" id="testimonial-institution-error">{{ $message }}</span>@enderror
</div>
<div class="field{{ $errors->has('content') ? ' is-invalid' : '' }}">
    <label class="label" for="testimonial-content">Pengalaman Belajar</label>
    <textarea class="textarea" id="testimonial-content" name="content" rows="6" required minlength="10" maxlength="2000" aria-describedby="testimonial-content-help{{ $errors->has('content') ? ' testimonial-content-error' : '' }}" @if ($errors->has('content')) aria-invalid="true" @endif>{{ old('content', $testimonial?->content) }}</textarea>
    <span class="text-sm text-soft" id="testimonial-content-help">10–2000 karakter. Ceritakan pengalaman belajar yang kamu alami.</span>
    @error('content')<span class="field__error" id="testimonial-content-error">{{ $message }}</span>@enderror
</div>
