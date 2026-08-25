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
        // Semua waktu DISIMPAN dalam UTC, tapi DITAMPILKAN dalam waktu Jakarta.
        // Dipakai lewat ->wib() di view: $x->wib()->translatedFormat('d M Y H:i').
        // Aman dipanggil berulang, dan tidak mengubah objek aslinya.
        Carbon::macro('wib', function () {
            /** @var \Carbon\Carbon $this */
            return $this->copy()->setTimezone(config('app.display_timezone', 'Asia/Jakarta'));
        });

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
