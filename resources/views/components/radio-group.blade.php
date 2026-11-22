{{--
    A labelled group of inline radio buttons with its validation error.
    `options` is a list of enum cases; each one gives its value and its label().
    `selected` is the value checked by default. Extra attributes go to the <fieldset>.
--}}
@props(['name', 'label', 'options', 'selected' => null])

@php($checkedValue = old($name, $selected))

<fieldset {{ $attributes->merge(['class' => 'mb-3']) }}>
    <legend class="form-label fs-6 mb-2">{{ $label }}</legend>
    @foreach ($options as $option)
        <div class="form-check form-check-inline">
            <input class="form-check-input @error($name) is-invalid @enderror" type="radio"
                   name="{{ $name }}" id="{{ $name }}_{{ $option->value }}" value="{{ $option->value }}"
                   @checked((string) $checkedValue === $option->value)>
            <label class="form-check-label" for="{{ $name }}_{{ $option->value }}">{{ $option->label() }}</label>
        </div>
    @endforeach
    @error($name)
        <div class="invalid-feedback d-block">{{ $message }}</div>
    @enderror
</fieldset>
