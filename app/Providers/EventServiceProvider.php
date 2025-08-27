<?php

namespace App\Providers;

use Illuminate\Auth\Events\Registered;
use App\Listeners\AssignDefaultRole;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        Registered::class => [
            AssignDefaultRole::class,
        ],
    ];

    public function boot(): void
    {
        //
    }
}
