<?php

namespace Mehadi\CRUDGenerator;
use Illuminate\Support\ServiceProvider;
use Mehadi\CRUDGenerator\Console\Commands\CrudDeleteCommand;
use Mehadi\CRUDGenerator\Console\Commands\CrudMakeCommand;

class CRUDGeneratorServiceProvider extends ServiceProvider
{
    function boot(){
        $this->publishes([
            __DIR__.'/../config/crudconfig.php' => config_path('crudconfig.php'),
        ], 'config');


        $this->commands([
            CrudMakeCommand::class,
            CrudDeleteCommand::class,
        ]);
    }

    function register(){
        $this->app->singleton(CRUDGenerator::class,function (){
            return new CRUDGenerator();
        });
    }
}
