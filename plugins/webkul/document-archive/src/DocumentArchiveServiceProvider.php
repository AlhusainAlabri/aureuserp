<?php

namespace Webkul\DocumentArchive;

use Filament\Panel;
use Illuminate\Support\Facades\Gate;
use Webkul\DocumentArchive\Console\ArchiveExpiredDocuments;
use Webkul\DocumentArchive\Console\CleanupExpiredShareLinks;
use Webkul\DocumentArchive\Models\DocFile;
use Webkul\DocumentArchive\Models\DocFolder;
use Webkul\DocumentArchive\Policies\DocFilePolicy;
use Webkul\DocumentArchive\Policies\DocFolderPolicy;
use Webkul\PluginManager\Console\Commands\InstallCommand;
use Webkul\PluginManager\Console\Commands\UninstallCommand;
use Webkul\PluginManager\Package;
use Webkul\PluginManager\PackageServiceProvider;

class DocumentArchiveServiceProvider extends PackageServiceProvider
{
    public static string $name = 'document-archive';

    public static string $viewNamespace = 'document-archive';

    public function configureCustomPackage(Package $package): void
    {
        $package->name(static::$name)
            ->hasViews()
            ->hasTranslations()
            ->hasRoutes(['web'])
            ->hasConfigFile(['document-archive'])
            ->hasMigrations([
                '2026_05_17_100000_create_doc_folders_table',
                '2026_05_17_100001_create_doc_folder_permissions_table',
                '2026_05_17_100002_create_doc_files_table',
                '2026_05_17_100003_create_doc_file_versions_table',
                '2026_05_17_100004_create_doc_share_links_table',
                '2026_05_17_100005_create_doc_file_activities_table',
            ])
            ->runsMigrations()
            ->hasInstallCommand(function (InstallCommand $command): void {
                $command
                    ->runsMigrations()
                    ->endWith(function (InstallCommand $command): void {
                        $command->call('shield:generate', [
                            '--resource' => 'DocFileResource,DocFolderResource',
                            '--panel'    => 'admin',
                        ]);

                        $en = require __DIR__.'/../resources/lang/en/document-archive.php';

                        $command->info($en['install']['success']);
                    });
            })
            ->hasUninstallCommand(function (UninstallCommand $command): void {})
            ->icon('document-archive');
    }

    public function packageRegistered(): void
    {
        Panel::configureUsing(function (Panel $panel): void {
            $panel->plugin(DocumentArchivePlugin::make());
        });
    }

    public function packageBooted(): void
    {
        Gate::policy(DocFile::class, DocFilePolicy::class);
        Gate::policy(DocFolder::class, DocFolderPolicy::class);

        $this->commands([
            ArchiveExpiredDocuments::class,
            CleanupExpiredShareLinks::class,
        ]);
    }
}
