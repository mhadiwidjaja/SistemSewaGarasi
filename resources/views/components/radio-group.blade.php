{{-- A labelled group of inline radio buttons with its validation error. `options` maps value => label. --}}
@props(['name', 'label', 'options', 'selected' => null])

@php($checkedValue = old($name, $selected))

<fieldset {{ $attributes->merge(['class' => 'mb-3']) }}>
    <legend class="form-label fs-6 mb-2">{{ $label }}</legend>
    @foreach ($options as $value => $optionLabel)
        <div class="form-check form-check-inline">
            <input class="form-check-input @error($name) is-invalid @enderror" type="radio"
                   name="{{ $name }}" id="{{ $name }}_{{ $value }}" value="{{ $value }}"
                   @checked((string) $checkedValue === (string) $value)>
            <label class="form-check-label" for="{{ $name }}_{{ $value }}">{{ $optionLabel }}</label>
        </div>
    @endforeach
    @error($name)
        <div class="invalid-feedback d-block">{{ $message }}</div>
    @enderror
</fieldset>
