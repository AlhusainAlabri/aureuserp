<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Webkul\Chatter\Filament\Actions\ChatterAction;
use Webkul\Chatter\Filament\Actions\CoreChatterAction;

class ChatterExtensionsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->registerChatterActionOverride();
    }

    protected function registerChatterActionOverride(): void
    {
        spl_autoload_register(
            function (string $class): bool {
                if ($class !== ChatterAction::class) {
                    return false;
                }

                $this->ensureCoreChatterActionIsLoaded();

                require app_path('Overrides/Webkul/Chatter/Filament/Actions/ChatterAction.php');

                return true;
            },
            prepend: true,
        );
    }

    protected function ensureCoreChatterActionIsLoaded(): void
    {
        if (class_exists(CoreChatterAction::class, false)) {
            return;
        }

        $source = base_path('plugins/webkul/chatter/src/Filament/Actions/ChatterAction.php');
        $cachePath = storage_path('framework/cache/core_chatter_action.php');

        if (! file_exists($cachePath) || filemtime($cachePath) < filemtime($source)) {
            $code = file_get_contents($source);
            $code = preg_replace('/\bclass ChatterAction\b/', 'class CoreChatterAction', $code, 1);

            if (! is_dir(dirname($cachePath))) {
                mkdir(dirname($cachePath), 0755, true);
            }

            file_put_contents($cachePath, $code);
        }

        require $cachePath;
    }
}
