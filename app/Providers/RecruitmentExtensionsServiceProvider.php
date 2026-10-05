<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Webkul\Recruitment\Filament\Clusters\Applications\Resources\ApplicantResource;
use Webkul\Recruitment\Filament\Clusters\Applications\Resources\CandidateResource;
use Webkul\Recruitment\Filament\Clusters\Applications\Resources\CoreApplicantResource;
use Webkul\Recruitment\Filament\Clusters\Applications\Resources\CoreCandidateResource;
use Webkul\Recruitment\Filament\Clusters\Applications\Resources\CoreJobByPositionResource;
use Webkul\Recruitment\Filament\Clusters\Applications\Resources\JobByPositionResource;
use Webkul\Recruitment\Filament\Clusters\Configurations;
use Webkul\Recruitment\Filament\Clusters\Configurations\Resources\ActivityTypeResource;
use Webkul\Recruitment\Filament\Clusters\Configurations\Resources\CoreJobPositionResource;
use Webkul\Recruitment\Filament\Clusters\Configurations\Resources\JobPositionResource;

class RecruitmentExtensionsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->registerConfigurationsClusterOverride();
        $this->registerActivityTypeResourceOverride();
        $this->registerApplicantResourceOverride();
        $this->registerCandidateResourceOverride();
        $this->registerJobByPositionResourceOverride();
        $this->registerJobPositionResourceOverride();
    }

    public function boot(): void
    {
        $this->loadTranslationsFrom(lang_path('recruitments-extensions'), 'recruitments-extensions');
    }

    protected function registerConfigurationsClusterOverride(): void
    {
        spl_autoload_register(
            function (string $class): bool {
                if ($class !== Configurations::class) {
                    return false;
                }

                require app_path('Overrides/Webkul/Recruitment/Filament/Clusters/Configurations.php');

                return true;
            },
            prepend: true,
        );
    }

    protected function registerActivityTypeResourceOverride(): void
    {
        spl_autoload_register(
            function (string $class): bool {
                if ($class !== ActivityTypeResource::class) {
                    return false;
                }

                require app_path('Overrides/Webkul/Recruitment/Filament/Clusters/Configurations/Resources/ActivityTypeResource.php');

                return true;
            },
            prepend: true,
        );
    }

    protected function registerApplicantResourceOverride(): void
    {
        spl_autoload_register(
            function (string $class): bool {
                if ($class === CoreApplicantResource::class) {
                    $this->ensureCoreApplicantResourceIsLoaded();

                    return true;
                }

                if ($class !== ApplicantResource::class) {
                    return false;
                }

                $this->ensureCoreApplicantResourceIsLoaded();

                require app_path('Overrides/Webkul/Recruitment/Filament/Clusters/Applications/Resources/ApplicantResource.php');

                return true;
            },
            prepend: true,
        );
    }

    protected function ensureCoreApplicantResourceIsLoaded(): void
    {
        if (class_exists(CoreApplicantResource::class, false)) {
            return;
        }

        $source = base_path('plugins/webkul/recruitments/src/Filament/Clusters/Applications/Resources/ApplicantResource.php');
        $cachePath = storage_path('framework/cache/core_applicant_resource.php');

        if (! file_exists($cachePath) || filemtime($cachePath) < filemtime($source)) {
            $code = file_get_contents($source);
            $code = preg_replace('/\bclass ApplicantResource\b/', 'class CoreApplicantResource', $code, 1);

            if (! is_dir(dirname($cachePath))) {
                mkdir(dirname($cachePath), 0755, true);
            }

            file_put_contents($cachePath, $code);
        }

        require $cachePath;
    }

    protected function registerCandidateResourceOverride(): void
    {
        spl_autoload_register(
            function (string $class): bool {
                if ($class === CoreCandidateResource::class) {
                    $this->ensureCoreCandidateResourceIsLoaded();

                    return true;
                }

                if ($class !== CandidateResource::class) {
                    return false;
                }

                $this->ensureCoreCandidateResourceIsLoaded();

                require app_path('Overrides/Webkul/Recruitment/Filament/Clusters/Applications/Resources/CandidateResource.php');

                return true;
            },
            prepend: true,
        );
    }

    protected function ensureCoreCandidateResourceIsLoaded(): void
    {
        if (class_exists(CoreCandidateResource::class, false)) {
            return;
        }

        $source = base_path('plugins/webkul/recruitments/src/Filament/Clusters/Applications/Resources/CandidateResource.php');
        $cachePath = storage_path('framework/cache/core_candidate_resource.php');

        if (! file_exists($cachePath) || filemtime($cachePath) < filemtime($source)) {
            $code = file_get_contents($source);
            $code = preg_replace('/\bclass CandidateResource\b/', 'class CoreCandidateResource', $code, 1);

            if (! is_dir(dirname($cachePath))) {
                mkdir(dirname($cachePath), 0755, true);
            }

            file_put_contents($cachePath, $code);
        }

        require $cachePath;
    }

    protected function registerJobByPositionResourceOverride(): void
    {
        spl_autoload_register(
            function (string $class): bool {
                if ($class === CoreJobByPositionResource::class) {
                    $this->ensureCoreJobByPositionResourceIsLoaded();

                    return true;
                }

                if ($class !== JobByPositionResource::class) {
                    return false;
                }

                $this->ensureCoreJobByPositionResourceIsLoaded();

                require app_path('Overrides/Webkul/Recruitment/Filament/Clusters/Applications/Resources/JobByPositionResource.php');

                return true;
            },
            prepend: true,
        );
    }

    protected function ensureCoreJobByPositionResourceIsLoaded(): void
    {
        if (class_exists(CoreJobByPositionResource::class, false)) {
            return;
        }

        $source = base_path('plugins/webkul/recruitments/src/Filament/Clusters/Applications/Resources/JobByPositionResource.php');
        $cachePath = storage_path('framework/cache/core_job_by_position_resource.php');

        if (! file_exists($cachePath) || filemtime($cachePath) < filemtime($source)) {
            $code = file_get_contents($source);
            $code = preg_replace('/\bclass JobByPositionResource\b/', 'class CoreJobByPositionResource', $code, 1);

            if (! is_dir(dirname($cachePath))) {
                mkdir(dirname($cachePath), 0755, true);
            }

            file_put_contents($cachePath, $code);
        }

        require $cachePath;
    }

    protected function registerJobPositionResourceOverride(): void
    {
        spl_autoload_register(
            function (string $class): bool {
                if ($class === CoreJobPositionResource::class) {
                    $this->ensureCoreJobPositionResourceIsLoaded();

                    return true;
                }

                if ($class !== JobPositionResource::class) {
                    return false;
                }

                $this->ensureCoreJobPositionResourceIsLoaded();

                require app_path('Overrides/Webkul/Recruitment/Filament/Clusters/Configurations/Resources/JobPositionResource.php');

                return true;
            },
            prepend: true,
        );
    }

    protected function ensureCoreJobPositionResourceIsLoaded(): void
    {
        if (class_exists(CoreJobPositionResource::class, false)) {
            return;
        }

        $source = base_path('plugins/webkul/recruitments/src/Filament/Clusters/Configurations/Resources/JobPositionResource.php');
        $cachePath = storage_path('framework/cache/core_job_position_resource.php');

        if (! file_exists($cachePath) || filemtime($cachePath) < filemtime($source)) {
            $code = file_get_contents($source);
            $code = preg_replace('/\bclass JobPositionResource\b/', 'class CoreJobPositionResource', $code, 1);

            if (! is_dir(dirname($cachePath))) {
                mkdir(dirname($cachePath), 0755, true);
            }

            file_put_contents($cachePath, $code);
        }

        require $cachePath;
    }
}
