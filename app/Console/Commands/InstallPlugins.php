<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;
use ReflectionClass;
use Symfony\Component\Process\Process;
use Throwable;
use Webkul\PluginManager\Console\Commands\InstallCommand;
use Webkul\PluginManager\Models\Plugin;
use Webkul\PluginManager\Package;
use Webkul\PluginManager\PackageServiceProvider;

use function Laravel\Prompts\multiselect;

class InstallPlugins extends Command
{
    protected $signature = 'plugins:install
        {--all : Install all currently not-installed installable plugins without prompting}
        {--only=* : Install only the given plugin names without prompting}
        {--include-core : Include core plugins in the selectable install list}
        {--stop-on-error : Stop after the first plugin installation error}
        {--timeout=600 : Seconds to allow each plugin install process to run}';

    protected $description = 'List Webkul plugins and install the selected missing plugins.';

    public function handle(): int
    {
        $plugins = $this->discoverPlugins();

        if ($plugins->isEmpty()) {
            $this->error('No Webkul plugins were found in plugins/webkul.');

            return self::FAILURE;
        }

        $this->displayPlugins($plugins);

        $selectedPluginNames = $this->resolveSelectedPluginNames($plugins);

        if ($selectedPluginNames === null) {
            return self::FAILURE;
        }

        if ($selectedPluginNames === []) {
            $this->warn('No plugins selected.');

            return self::SUCCESS;
        }

        $selectedPluginNames = $this->sortByDependencies($selectedPluginNames, $plugins);

        $this->newLine();
        $this->info('Installing selected plugins in this order: '.implode(', ', $selectedPluginNames));
        $this->newLine();

        $results = [];

        foreach ($selectedPluginNames as $pluginName) {
            $results[$pluginName] = $this->installPlugin($pluginName);

            if (! $results[$pluginName]['successful'] && $this->option('stop-on-error')) {
                break;
            }
        }

        $this->newLine();
        $this->displaySummary($results);

        return collect($results)->contains(fn (array $result): bool => ! $result['successful'])
            ? self::FAILURE
            : self::SUCCESS;
    }

    /**
     * @return Collection<int, array{
     *     name: string,
     *     status: string,
     *     is_installed: bool,
     *     is_core: bool,
     *     has_install_command: bool,
     *     dependencies: array<int, string>,
     *     provider: class-string,
     * }>
     */
    protected function discoverPlugins(): Collection
    {
        return collect(glob(base_path('plugins/webkul/*/composer.json')) ?: [])
            ->flatMap(function (string $composerPath): array {
                $composerData = json_decode(file_get_contents($composerPath), true);

                if (! is_array($composerData)) {
                    return [];
                }

                return collect(data_get($composerData, 'extra.laravel.providers', []))
                    ->map(fn (string $providerClass): ?array => $this->resolvePluginFromProvider($providerClass))
                    ->filter()
                    ->all();
            })
            ->sortBy('name')
            ->values();
    }

    /**
     * @param  class-string  $providerClass
     * @return array{name: string, status: string, is_installed: bool, is_core: bool, has_install_command: bool, dependencies: array<int, string>, provider: class-string}|null
     */
    protected function resolvePluginFromProvider(string $providerClass): ?array
    {
        if (! class_exists($providerClass)) {
            return null;
        }

        $reflection = new ReflectionClass($providerClass);

        if (! $reflection->isSubclassOf(PackageServiceProvider::class)) {
            return null;
        }

        /** @var PackageServiceProvider $serviceProvider */
        $serviceProvider = new $providerClass(app());

        $package = new Package;
        $package->setBasePath(dirname($reflection->getFileName()));

        $serviceProvider->configureCustomPackage($package);

        $isInstalled = $this->isPluginInstalled($package->name);

        return [
            'name'                => $package->name,
            'status'              => $isInstalled ? 'installed' : 'not installed',
            'is_installed'        => $isInstalled,
            'is_core'             => $package->isCore,
            'has_install_command' => collect($package->consoleCommands)
                ->contains(fn (object $command): bool => $command instanceof InstallCommand),
            'dependencies'        => array_values(array_unique($package->dependencies)),
            'provider'            => $providerClass,
        ];
    }

    protected function isPluginInstalled(string $pluginName): bool
    {
        try {
            if (! Schema::hasTable('plugins')) {
                return false;
            }

            return Plugin::query()
                ->where('name', $pluginName)
                ->where('is_installed', true)
                ->exists();
        } catch (Throwable) {
            return false;
        }
    }

    /**
     * @param  Collection<int, array{name: string, status: string, is_core: bool, has_install_command: bool, dependencies: array<int, string>}>  $plugins
     */
    protected function displayPlugins(Collection $plugins): void
    {
        $this->table(
            ['Plugin', 'Status', 'Type', 'Install command', 'Dependencies'],
            $plugins
                ->map(fn (array $plugin): array => [
                    $plugin['name'],
                    $plugin['status'],
                    $plugin['is_core'] ? 'core' : 'installable',
                    $plugin['has_install_command'] ? 'yes' : 'no',
                    $plugin['dependencies'] ? implode(', ', $plugin['dependencies']) : '-',
                ])
                ->all()
        );
    }

    /**
     * @param  Collection<int, array{name: string, is_installed: bool, is_core: bool, has_install_command: bool, dependencies: array<int, string>}>  $plugins
     * @return array<int, string>|null
     */
    protected function resolveSelectedPluginNames(Collection $plugins): ?array
    {
        $installablePlugins = $plugins
            ->filter(fn (array $plugin): bool => $plugin['has_install_command'])
            ->filter(fn (array $plugin): bool => $this->option('include-core') || ! $plugin['is_core'])
            ->values();

        if ($installablePlugins->isEmpty()) {
            $this->warn('No installable plugins are available.');

            return [];
        }

        $onlyPluginNames = array_values(array_filter($this->option('only')));

        if ($onlyPluginNames !== []) {
            $unknownPluginNames = array_values(array_diff(
                $onlyPluginNames,
                $installablePlugins->pluck('name')->all()
            ));

            if ($unknownPluginNames !== []) {
                $this->error('These plugins are not installable: '.implode(', ', $unknownPluginNames));

                return null;
            }

            return $onlyPluginNames;
        }

        $default = $installablePlugins
            ->reject(fn (array $plugin): bool => $plugin['is_installed'])
            ->pluck('name')
            ->values()
            ->all();

        if ($this->option('all') || ! $this->input->isInteractive()) {
            return $default;
        }

        $options = $installablePlugins
            ->mapWithKeys(function (array $plugin): array {
                $dependencies = $plugin['dependencies']
                    ? ' dependencies: '.implode(', ', $plugin['dependencies'])
                    : '';

                return [$plugin['name'] => "{$plugin['name']} ({$plugin['status']}){$dependencies}"];
            })
            ->all();

        return multiselect(
            label: 'Select plugins to install',
            options: $options,
            default: $default,
            scroll: 15,
            hint: 'Non-installed installable plugins are selected by default. Use space to select or unselect.'
        );
    }

    /**
     * @param  array<int, string>  $pluginNames
     * @param  Collection<int, array{name: string, dependencies: array<int, string>}>  $plugins
     * @return array<int, string>
     */
    protected function sortByDependencies(array $pluginNames, Collection $plugins): array
    {
        $selected = array_fill_keys($pluginNames, true);
        $pluginsByName = $plugins->keyBy('name');
        $sorted = [];
        $visited = [];

        $visit = function (string $pluginName) use (&$visit, &$sorted, &$visited, $selected, $pluginsByName): void {
            if (isset($visited[$pluginName])) {
                return;
            }

            $visited[$pluginName] = true;

            foreach ($pluginsByName->get($pluginName)['dependencies'] ?? [] as $dependencyName) {
                if (isset($selected[$dependencyName])) {
                    $visit($dependencyName);
                }
            }

            $sorted[] = $pluginName;
        };

        foreach ($pluginNames as $pluginName) {
            $visit($pluginName);
        }

        return $sorted;
    }

    /**
     * @return array{successful: bool, message: string}
     */
    protected function installPlugin(string $pluginName): array
    {
        $this->info("Installing {$pluginName}...");

        try {
            $process = new Process([
                PHP_BINARY,
                base_path('artisan'),
                "{$pluginName}:install",
                '--no-interaction',
            ]);

            $process->setTimeout((int) $this->option('timeout'));
            $process->run(function (string $type, string $buffer): void {
                $this->output->write($buffer);
            });

            if (! $process->isSuccessful()) {
                return [
                    'successful' => false,
                    'message'    => trim($process->getErrorOutput() ?: $process->getOutput()) ?: 'Installation command failed.',
                ];
            }

            $isInstalled = $this->isPluginInstalled($pluginName);

            return [
                'successful' => $isInstalled,
                'message'    => $isInstalled
                    ? 'Installed'
                    : 'Command finished, but plugin is still marked not installed.',
            ];
        } catch (Throwable $exception) {
            return [
                'successful' => false,
                'message'    => $exception->getMessage(),
            ];
        }
    }

    /**
     * @param  array<string, array{successful: bool, message: string}>  $results
     */
    protected function displaySummary(array $results): void
    {
        $this->table(
            ['Plugin', 'Result', 'Message'],
            collect($results)
                ->map(fn (array $result, string $pluginName): array => [
                    $pluginName,
                    $result['successful'] ? 'success' : 'failed',
                    $result['message'],
                ])
                ->values()
                ->all()
        );
    }
}
