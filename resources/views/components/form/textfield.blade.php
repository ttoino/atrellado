@props(['name', 'type' => 'text', 'feedback' => 'Invalid value'])
@php($id ??= $name)

<div @class([
    'form-floating',
    'input-group has-validation password-input' => $type == 'password',
])>
    <input
        {{ $attributes->merge([
            'class' => 'form-control' . ($errors->has($name) ? ' is-invalid' : ''),
            'placeholder' => '',
            'name' => $name,
            'id' => $id,
            'type' => $type,
            'value' => $type === 'password' ? '' : old($name),
        ]) }}
        aria-describedby="{{ $id }}-feedback">
    <label for="{{ $id }}" class="form-label">{{ $slot }}</label>
    @if ($type == 'password')
        <x-button outline icon />
    @endif
    <div class="invalid-feedback" id="{{ $id }}-feedback">
        @error($name)
            {{ $message }}
        @else
            {{ $feedback }}
        @enderror
    </div>
</div>
