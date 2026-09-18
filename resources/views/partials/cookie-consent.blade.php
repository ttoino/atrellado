{{-- Inlined from spatie/laravel-cookie-consent's published views so the
    package (and its eager provider) can be dropped. --}}
@if (!request()->hasCookie('laravel_cookie_consent'))
    <div class="js-cookie-consent cookie-consent fixed bottom-0 inset-x-0 pb-2">
        <div class="max-w-7xl mx-auto px-6">
            <div class="p-2 rounded-lg bg-yellow-100">
                <div class="flex items-center justify-between flex-wrap">
                    <div class="w-0 flex-1 items-center hidden md:inline">
                        <p class="ms-3 text-black cookie-consent__message">
                            Your experience on this site will be improved by allowing cookies.
                        </p>
                    </div>
                    <div class="mt-2 flex-shrink-0 w-full sm:mt-0 sm:w-auto">
                        <button
                            class="js-cookie-consent-agree cookie-consent__agree cursor-pointer flex items-center justify-center px-4 py-2 rounded-md text-sm font-medium text-yellow-800 bg-yellow-400 hover:bg-yellow-300">
                            Allow cookies
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        window.laravelCookieConsent = (function() {

            const COOKIE_VALUE = 1;
            const COOKIE_DOMAIN = '{{ config('session.domain') ?? request()->getHost() }}';

            function consentWithCookies() {
                setCookie('laravel_cookie_consent', COOKIE_VALUE, 7300);
                hideCookieDialog();
            }

            function cookieExists(name) {
                return (document.cookie.split('; ').indexOf(name + '=' + COOKIE_VALUE) !== -1);
            }

            function hideCookieDialog() {
                const dialogs = document.getElementsByClassName('js-cookie-consent');

                for (let i = 0; i < dialogs.length; ++i) {
                    dialogs[i].style.display = 'none';
                }
            }

            function setCookie(name, value, expirationInDays) {
                const date = new Date();
                date.setTime(date.getTime() + (expirationInDays * 24 * 60 * 60 * 1000));
                document.cookie = name + '=' + value +
                    ';expires=' + date.toUTCString() +
                    ';domain=' + COOKIE_DOMAIN +
                    ';path=/{{ config('session.secure') ? ';secure' : null }}' +
                    '{{ config('session.same_site') ? ';samesite=' . config('session.same_site') : null }}';
            }

            if (cookieExists('laravel_cookie_consent')) {
                hideCookieDialog();
            }

            const buttons = document.getElementsByClassName('js-cookie-consent-agree');

            for (let i = 0; i < buttons.length; ++i) {
                buttons[i].addEventListener('click', consentWithCookies);
            }

            return {
                consentWithCookies: consentWithCookies,
                hideCookieDialog: hideCookieDialog
            };
        })();
    </script>
@endif
