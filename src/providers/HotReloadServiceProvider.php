<?php

namespace Elphis\Providers;

use Elphis\Providers\ServiceProvider;
use Elphis\Utils\Config;
use Elphis\Utils\HotReloader;
use Swoole\Http\Server;

class HotReloadServiceProvider extends ServiceProvider
{
    public function register()
    { }

    public function boot()
    {
        $config = $this->app->resolve(Config::class);
        $server = $this->app->resolve(Server::class);

        if ($config->get('app.env') !== 'production' && $config->get('app.env') !== 'prod') {
            $reloader = new HotReloader([
                base_path('app'),
                base_path('config')
            ]);
            $reloader->watch($server);
        }
    }
}
