@extends('layouts.centered-form')
@use('App\Enums\ProviderType')

@section('title', 'Login')

@section('form')

    @if (session('error'))
        <div class="alert alert-danger m-0" role="alert">{{ session('error') }}</div>
    @endif

    <x-form.textfield type="email" name="email" autocomplete="email" required autofocus feedback="Invalid email">
        Email
    </x-form.textfield>
    <x-form.textfield type="password" name="password" autocomplete="current-password" required
        feedback="Invalid password">
        Password
    </x-form.textfield>

    <x-form.check name="remember">Remember me</x-form.check>

    <x-button type="submit">
        Login
    </x-button>

    @foreach (ProviderType::configured() as $provider)
        <x-dynamic-component :component="'button.'.$provider->value" />
    @endforeach

    <div class="text-center">
        Don't have an account?
        <a href="{{ route('register') }}" class="link-primary">Register</a>
    </div>

    <div class="text-center">
        Forgot your password?
        <a href="{{ route('password.request') }}" class="link-primary">Reset it</a>
    </div>
@endsection
