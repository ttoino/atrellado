@extends('layouts.centered-form')

@section('title', 'Reset password')

@section('action', route('password.reset-action'))

@section('form')
    <x-form.textfield type="email" name="email" autocomplete="email" value="{{ request()->query('email') }}" required
        autofocus feedback="Invalid email">
        Email
    </x-form.textfield>

    <x-form.textfield type="password" name="password" autocomplete="new-password" required
        feedback="Invalid password">
        Password
    </x-form.textfield>

    <x-form.textfield type="password" name="password_confirmation" autocomplete="new-password" required
        feedback="Passwords don't match">
        Confirm password
    </x-form.textfield>

    <input type="hidden" name="token" value="{{ $token }}">

    <x-button type="submit">
        Reset password
    </x-button>
@endsection
