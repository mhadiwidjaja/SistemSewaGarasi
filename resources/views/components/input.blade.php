{{-- A labelled form input with its help text and validation error. Extra attributes go to the <input>. --}}
@props(['name', 'label', 'type' => 'text', 'help' => null])

<div class="mb-3">
    <label for="{{ $name }}" class="form-label">{{ $label }}</label>
    <input id="{{ $name }}" name="{{ $name }}" type="{{ $type }}"
           @if (! in_array($type, ['password', 'file'], true)) value="{{ old($name, $attributes->get('value')) }}" @endif
           @if ($help) aria-describedby="{{ $name }}_help" @endif
           {{ $attributes->except('value')->class(['form-control', 'is-invalid' => $errors->has($name)]) }}>
    @if ($help)
        <div class="form-text" id="{{ $name }}_help">{{ $help }}</div>
    @endif
    @error($name)
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>
