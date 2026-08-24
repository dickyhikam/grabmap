<?php

namespace App\Providers;

use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Setiap kunci izin di config/permissions.php jadi Gate, sehingga bisa dipakai
        // lewat @can('users.manage') di Blade maupun $user->can(...) di kode.
        foreach (collect(config('permissions', []))->flatten() as $permission) {
            Gate::define($permission, fn ($user) => $user->hasPermission($permission));
        }

        // Email verification link valid for 24 hours
        VerifyEmail::createUrlUsing(function ($notifiable) {
            return URL::temporarySignedRoute(
                'verification.verify',
                Carbon::now()->addHours(24),
                [
                    'id' => $notifiable->getKey(),
                    'hash' => sha1($notifiable->getEmailForVerification()),
                ]
            );
        });
    }
}
