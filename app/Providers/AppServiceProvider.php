<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Guards\MySessionGuard;
use Illuminate\Support\Facades\Auth;
use Illuminate\Auth\EloquentUserProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        Auth::extend(
            'my_session_guard',
            function ($app) {
                $provider = new EloquentUserProvider($app['hash'], config('auth.providers.users.model'));
                $guard = new MySessionGuard('my_session_guard', $provider, app()->make('session.store'), request());
                $guard->setCookieJar($this->app['cookie']);
                $guard->setDispatcher($this->app['events']);
                $guard->setRequest($this->app->refresh('request', $guard, 'setRequest'));
                return $guard;
            }
        );
    }
}
