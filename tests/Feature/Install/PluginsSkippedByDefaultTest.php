<?php

use App\Console\Commands\InstallPlugins;
use Illuminate\Support\Collection;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\NullOutput;

function selectPluginsFor(array $input): array
{
    $command = new class extends InstallPlugins
    {
        public array $selected = [];

        public function handle(): int
        {
            $plugin = fn (string $name, array $dependencies = []): array => [
                'name'                => $name,
                'status'              => 'not installed',
                'is_installed'        => false,
                'is_core'             => false,
                'has_install_command' => true,
                'dependencies'        => $dependencies,
                'provider'            => 'Fake',
            ];

            $this->selected = $this->resolveSelectedPluginNames(new Collection([
                $plugin('sales'),
                $plugin('website'),
                $plugin('blogs', ['website']),
            ])) ?? [];

            return self::SUCCESS;
        }
    };

    $command->setLaravel(app());
    $command->run(new ArrayInput($input), new NullOutput);

    return $command->selected;
}

it('leaves website and blogs out of a default install', function (): void {
    expect(selectPluginsFor(['--all' => true]))->toBe(['sales']);
});

it('still installs website when asked for it by name', function (): void {
    expect(selectPluginsFor(['--only' => ['website']]))->toBe(['website']);
});
