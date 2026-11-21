{{-- A labelled form input with its help text and validation error. Extra attributes go to the <input>. --}}
@props(['name', 'label', 'type' => 'text', 'help' => null])

<div class="mb-3">
    <label for="{{ $name }}" class="form-label">{{ $label }}</label>
    <input id="{{ $name }}" name="{{ $name }}" type="{{ $type }}"
           @if ($type !== 'password') value="{{ old($name) }}" @endif
           {{ $attributes->class(['form-control', 'is-invalid' => $errors->has($name)]) }}>
    @if ($help)
        <div class="form-text">{{ $help }}</div>
    @endif
    @error($name)
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>
