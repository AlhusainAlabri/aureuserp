<?php

use App\Filament\Concerns\HasEmployeeRecordNavigationTabs;
use App\Http\Middleware\SetLocale;
use App\Support\FilamentUrl;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Session;
use Webkul\Security\Models\User;

it('appends locale to employee record navigation urls', function (): void {
    App::setLocale('ar');

    $trait = new class
    {
        use HasEmployeeRecordNavigationTabs;

        public function mapItems(array $items): array
        {
            return $this->convertNavigationItemsToArray($items);
        }
    };

    $item = new class
    {
        public function isHidden(): bool
        {
            return false;
        }

        public function getLabel(): string
        {
            return 'Contracts';
        }

        public function getUrl(): string
        {
            return '/admin/employees/employees/1/contracts';
        }

        public function isActive(): bool
        {
            return false;
        }

        public function getIcon(): ?string
        {
            return 'heroicon-o-document-duplicate';
        }

        public function getactiveIcon(): ?string
        {
            return null;
        }

        public function getBadge(): ?string
        {
            return null;
        }

        public function getBadgeColor(): ?string
        {
            return null;
        }
    };

    $mapped = $trait->mapItems([$item]);

    expect($mapped[0]['url'])->toBe('/admin/employees/employees/1/contracts?lang=ar');
});

it('splits employee navigation into primary and overflow groups', function (): void {
    expect((new class
    {
        use HasEmployeeRecordNavigationTabs;

        public static function count(): int
        {
            return self::primaryEmployeeNavigationTabCount();
        }
    })::count())->toBe(8);
});

it('persists query locale in session for authenticated users', function (): void {
    $user = User::factory()->create(['language' => 'en']);

    $middleware = new SetLocale;

    $first = Request::create('/admin/employees/employees?lang=ar', 'GET');
    $first->setLaravelSession(app('session.store'));
    $first->setUserResolver(fn () => $user);

    $middleware->handle($first, fn () => response('ok'));

    expect(App::getLocale())->toBe('ar')
        ->and(Session::get('locale'))->toBe('ar');

    $second = Request::create('/admin/employees/employees', 'GET');
    $second->setLaravelSession(app('session.store'));
    $second->setUserResolver(fn () => $user);

    $middleware->handle($second, fn () => response('ok'));

    expect(App::getLocale())->toBe('ar');
});

it('uses session locale when building filament urls', function (): void {
    App::setLocale('ar');

    expect(FilamentUrl::appendLocaleToUrl('/admin/employees/employees/1/overview'))
        ->toBe('/admin/employees/employees/1/overview?lang=ar');
});

it('defines overflow dropdown items as anchor links so navigation works', function (): void {
    $contents = file_get_contents(resource_path('views/filament/widgets/employee-record-navigation-tabs.blade.php'));

    expect($contents)
        ->toContain('<x-filament::dropdown.list.item')
        ->toContain('tag="a"')
        ->toContain(':href="$item[\'url\']"');
});
