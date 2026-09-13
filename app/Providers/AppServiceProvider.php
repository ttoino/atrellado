<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\URL;

use Illuminate\Support\Collection;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;
use Illuminate\Pagination\Paginator;
use Illuminate\Pagination\LengthAwarePaginator;

class AppServiceProvider extends ServiceProvider {
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register() {
        //
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot() {
        // workers-php: `workers-email` mail transport over the EMAIL
        // send_email binding, and the `r2` filesystem driver over the
        // FILES bucket binding. Both closures resolve lazily — WorkersPHP
        // classes only exist inside the worker.
        \Illuminate\Support\Facades\Mail::extend('workers-email', fn () =>
            new \App\Support\Mailer\WorkersEmailTransport(
                new \WorkersPHP\SendEmailBinding('EMAIL')
            )
        );

        \Illuminate\Support\Facades\Storage::extend('r2', function ($app, $config) {
            $adapter = new \App\Support\WorkersR2Adapter(
                new \WorkersPHP\R2Bucket($config['binding'] ?? 'FILES')
            );

            return new \Illuminate\Filesystem\FilesystemAdapter(
                new \League\Flysystem\Filesystem($adapter, $config),
                $adapter,
                $config
            );
        });

        // workers-php: the bridge stages multipart uploads outside Zend's
        // rfc1867 registry, so is_uploaded_file() fails for them. Re-mark
        // as test files so UploadedFile::isValid() accepts them.
        if (class_exists(\WorkersPHP\Env::class)) {
            $request = $this->app['request'];
            $files = [];
            foreach ($request->files->all() as $key => $file) {
                // Laravel wraps lazily on ->file() access; the bag itself
                // holds Symfony instances.
                $files[$key] = $file instanceof \Symfony\Component\HttpFoundation\File\UploadedFile
                    ? new \Illuminate\Http\UploadedFile(
                        $file->getPathname(), $file->getClientOriginalName(),
                        $file->getClientMimeType(), (int) $file->getError(), true,
                    )
                    : $file;
            }
            $request->files->replace($files);
        }

        Paginator::useBootstrapFive();
        Route::pattern('id', '[0-9]+');
        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(60)->by(optional($request->user())->id ?: $request->ip());
        });

        Collection::macro('paginate', function ($perPage, $total = null, $page = null, $pageName = 'page') {
            $page = $page ?: LengthAwarePaginator::resolveCurrentPage($pageName);

            return new LengthAwarePaginator(
                $this->forPage($page, $perPage),
                $total ?: $this->count(),
                $perPage,
                $page,
                [
                    'path' => LengthAwarePaginator::resolveCurrentPath(),
                    'pageName' => $pageName,
                ]
            );
        });

        if (env('FORCE_HTTPS', false)) {
            error_log('configuring https');
            $app_url = config("app.url");
            URL::forceRootUrl($app_url);
            $schema = explode(':', $app_url)[0];
            URL::forceScheme($schema);
        }
    }
}