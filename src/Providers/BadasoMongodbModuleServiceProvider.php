<?php

namespace Uasoft\Badaso\Module\Mongodb\Providers;

use Illuminate\Contracts\Http\Kernel;
use Illuminate\Foundation\AliasLoader;
use Illuminate\Support\ServiceProvider;
use Uasoft\Badaso\Module\Mongodb\BadasoMongodbModule;
use Uasoft\Badaso\Module\Mongodb\Commands\BadasoMongodbSetup;
use Uasoft\Badaso\Module\Mongodb\Commands\BadasoGenerateSeeder;
use Uasoft\Badaso\Module\Mongodb\Facades\BadasoMongodbModule as FacadesBadasoMongodbModule;

class BadasoMongodbModuleServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        $kernel = $this->app->make(Kernel::class);

        $loader = AliasLoader::getInstance();
        $loader->alias('BadasoMongodbModule', FacadesBadasoMongodbModule::class);

        $this->app->singleton('badaso-mongodb-module', function () {
            return new BadasoMongodbModule();
        });

        $this->publishes([
            __DIR__.'/../Seeder/MongodbManualGenerate' => database_path('seeders/Badaso/MongodbManualGenerate'),
        ], 'BadasoMongodbModule');
    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $this->registerConsoleCommands();
    }

    /**
     * Register the commands accessible from the Console.
     */
    private function registerConsoleCommands()
    {
        $this->commands(BadasoMongodbSetup::class);
        $this->commands(BadasoGenerateSeeder::class);
    }
}
