@props(['value' => null])
<div class="field{{ $errors->has('province') ? ' is-invalid' : '' }}">
    <label class="label" for="province">Provinsi tempat tinggal</label>
    <select class="input" id="province" name="province" required autocomplete="address-level1" aria-describedby="province-hint" @error('province') aria-invalid="true" @enderror>
        <option value="">Pilih provinsi tempat tinggal</option>
        @foreach (\App\Enums\Province::cases() as $provinceOption)
            <option value="{{ $provinceOption->value }}" @selected(old('province', $value) === $provinceOption->value)>{{ $provinceOption->value }}</option>
        @endforeach
    </select>
    <p class="field__hint" id="province-hint">Provinsi hanya digunakan untuk leaderboard: menentukan peringkat lokal dan ditampilkan bersama namamu di papan peringkat.</p>
    <span class="field__error"><svg data-icon="alert"></svg><span>{{ $errors->first('province') }}</span></span>
</div>
