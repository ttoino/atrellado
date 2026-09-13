<?php

namespace App\Providers;

use Illuminate\Database\SQLiteConnection;
use Illuminate\Support\ServiceProvider;
use WorkersPHP\D1PDO;

// workers-php: registers a `d1` database driver whose PDO handle speaks
// to the Worker's D1 binding instead of a local SQLite file.
class D1ServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // The name is set so Connection::getName() works — Migrator::runMethod
        // promotes it to the default connection while a migration runs.
        $this->app['db']->extend('d1', function (array $config, string $name) {
            $config['name'] = $name;

            return new SQLiteConnection(
                new D1PDO($config['binding'] ?? 'DB'),
                $config['database'] ?? ':memory:',
                $config['prefix'] ?? '',
                $config
            );
        });
    }
}
