<?php namespace WebEd\Base\ModulesManagement\Providers;

use \Illuminate\Support\ServiceProvider;
use File;

class LoadModulesServiceProvider extends ServiceProvider
{
    /**
     * Modules information
     * @var array
     */
    protected $modules = [];

    protected $notLoadedModules = [];

    public function register()
    {

    }

    public function boot()
    {
        app()->booted(function () {
            $this->booted();
        });
    }

    private function booted()
    {
        $this->modules = collect(get_all_module_information());

        foreach ($this->modules->where('type', '=', 'plugins') as $module) {
            if (array_get($module, 'enabled', null) === true) {
                /**
                 * Register module
                 */
                $moduleProvider = $module['namespace'] . '\Providers\ModuleProvider';

                if (class_exists($moduleProvider)) {
                    $this->app->register($moduleProvider);
                } else {
                    $this->notLoadedModules[] = $moduleProvider;
                }
            }
        }

        if(!app()->runningInConsole()) {
            if ($this->notLoadedModules) {
                foreach ($this->notLoadedModules as $key => $module) {
                    /**
                     * Use hook here
                     * Show the error messages
                     */
                    add_action('flash_messages', function () use ($module) {
                        echo \Html::note(
                            'The base module of this class is enabled, but class not found: ' . $module . '. Please review and add the namespace of this module to composer autoload section, then run <b>composer dump-autoload</b>',
                            'error',
                            false
                        );
                    }, $key);
                }
            }
        }

        \ModulesManagement::setModules($this->modules);
    }
}
