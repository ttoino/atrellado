@extends('layouts.centered-form')
@use('App\Enums\ProviderType')

@section('title', 'Register')

@section('form')
    @if (session('error'))
        <div class="alert alert-danger m-0" role="alert">{{ session('error') }}</div>
    @endif

    <x-form.textfield name="name" autocomplete="username" minlength=6 maxlength=255 required autofocus
        feedback="Invalid name">
        Name
    </x-form.textfield>

    <x-form.textfield type="email" name="email" autocomplete="email" required feedback="Invalid email">
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

    <x-button type="submit">
        Register
    </x-button>

    @foreach (ProviderType::configured() as $provider)
        <x-dynamic-component :component="'button.'.$provider->value" />
    @endforeach

    <div class="text-center">Already have an account?
        <a class="link-primary" href="{{ route('login') }}">Login</a>
    </div>
@endsection
