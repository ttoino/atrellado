@extends('layouts.bare')

@section('body')
    @yield('above-footer')

    <footer id="footer">
        <nav>
            <a href="{{ route('static.about') }}">About us</a>
            <a href="{{ route('static.faq') }}">FAQ</a>
            <a href="{{ route('static.contacts') }}">Contacts</a>
            <a href="{{ route('static.services') }}">Services</a>
        </nav>
    </footer>
@endsection
