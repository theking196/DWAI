<?php

namespace App\Providers;

use App\Models\CanonEntry;
use App\Models\ReferenceImage;
use App\Models\Session;
use App\Observers\CanonObserver;
use App\Observers\ReferenceImageObserver;
use App\Observers\SessionObserver;
use Illuminate\Support\ServiceProvider;

class EmbeddingServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        CanonEntry::observe(CanonObserver::class);
        ReferenceImage::observe(ReferenceImageObserver::class);
        Session::observe(SessionObserver::class);
    }
}
