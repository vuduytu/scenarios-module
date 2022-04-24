<?php

namespace Modules\Scenarios\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Scenarios\Http\Middleware\UserStoreHasScenario;
use Modules\Scenarios\Repositories\Scenario\IScenarioRepo;
use Modules\Scenarios\Repositories\Scenario\ScenarioRepo;
use Modules\Scenarios\Repositories\ScenarioSetting\IScenarioSettingRepo;
use Modules\Scenarios\Repositories\ScenarioSetting\ScenarioSettingRepo;
use Modules\Scenarios\Repositories\ScenarioMessage\IScenarioMessageRepo;
use Modules\Scenarios\Repositories\ScenarioMessage\ScenarioMessageRepo;
use Modules\Scenarios\Repositories\ScenarioTalk\IScenarioTalkRepo;
use Modules\Scenarios\Repositories\ScenarioTalk\ScenarioTalkRepo;
use Modules\Scenarios\Repositories\ScenarioTextMapping\ScenarioTextMappingRepo;
use Modules\Scenarios\Repositories\ScenarioTextMapping\IScenarioTextMappingRepo;
use Modules\Scenarios\Repositories\ScenarioUserMessage\IScenarioUserMessageRepo;

class ScenarioServiceProvider extends ServiceProvider
{
    public $bindings = [
        IScenarioRepo::class => ScenarioRepo::class,
        IScenarioSettingRepo::class => ScenarioSettingRepo::class,
        IScenarioMessageRepo::class => ScenarioMessageRepo::class,
        IScenarioTalkRepo::class => ScenarioTalkRepo::class,
        IScenarioTextMappingRepo::class => ScenarioTextMappingRepo::class,
        IScenarioUserMessageRepo::class => ScenarioMessageRepo::class
    ];
    /**
     * @var string $moduleName
     */
    protected $moduleName = 'Scenarios';

    /**
     * @var string $moduleNameLower
     */
    protected $moduleNameLower = 'scenario';

    /**
     * Boot the application events.
     *
     * @return void
     */
    public function boot()
    {
        $this->registerTranslations();
        $this->registerConfig();
        $this->loadMigrationsFrom($this->modulePath($this->moduleName, 'Database/Migrations'));
        app()->make('router')->aliasMiddleware('user_store_has_scenario', UserStoreHasScenario::class);
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->app->register(RouteServiceProvider::class);
    }

    /**
     * Register config.
     *
     * @return void
     */
    protected function registerConfig()
    {
        $this->publishes([
            $this->modulePath($this->moduleName, 'Config/config.php') => config_path($this->moduleNameLower . '.php'),
        ], 'config');
        $this->mergeConfigFrom(
            $this->modulePath($this->moduleName, 'Config/config.php'),
            $this->moduleNameLower
        );
    }

    /**
     * Register views.
     *
     * @return void
     */
    public function registerViews()
    {
        $viewPath = resource_path('views/modules/' . $this->moduleNameLower);

        $sourcePath = $this->modulePath($this->moduleName, 'Resources/views');

        $this->publishes([
            $sourcePath => $viewPath
        ], ['views', $this->moduleNameLower . '-module-views']);

        $this->loadViewsFrom(array_merge($this->getPublishableViewPaths(), [$sourcePath]), $this->moduleNameLower);
    }

    /**
     * Register translations.
     *
     * @return void
     */
    public function registerTranslations()
    {
        $langPath = resource_path('lang/modules/' . $this->moduleNameLower);

        if (is_dir($langPath)) {
            $this->loadTranslationsFrom($langPath, $this->moduleNameLower);
        } else {
            $this->loadTranslationsFrom($this->modulePath($this->moduleName, 'Resources/lang'), $this->moduleNameLower);
        }
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return [];
    }

    private function getPublishableViewPaths()
    {
        $paths = [];
        foreach (\Config::get('view.paths') as $path) {
            if (is_dir($path . '/modules/' . $this->moduleNameLower)) {
                $paths[] = $path . '/modules/' . $this->moduleNameLower;
            }
        }

        return $paths;
    }


    protected function modulePath($name, $path = '')
    {
        return app()->basePath() . '/Modules/' . $name . '/' . $path;
    }
}
