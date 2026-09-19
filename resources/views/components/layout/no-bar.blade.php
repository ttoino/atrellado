@props(['body-class' => ''])

<x-layout.bare :$body-class>
    {{ $slot }}

    <footer id="footer">
        <nav>
            <a href="{{ route('static.about') }}">About us</a>
            <a href="{{ route('static.faq') }}">FAQ</a>
            <a href="{{ route('static.contacts') }}">Contacts</a>
            <a href="{{ route('static.services') }}">Services</a>
        </nav>
    </footer>
</x-layout.bare>
