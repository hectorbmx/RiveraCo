<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Database\Eloquent\Relations\Relation;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(
            \App\Services\Contracts\ImageOptimizerInterface::class,
            \App\Services\Image\InterventionImageOptimizer::class
        );
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
        Relation::morphMap([
                'segmento' => \App\Models\CatalogoSegmento::class,
                'maquina'  => \App\Models\Maquina::class,
            ]);
    }
}
