<?php

use Webkul\PluginManager\Package;

it('renders the language switcher on the admin login page', function (): void {
    $this->get('/admin/login')
        ->assertSuccessful()
        ->assertSee('English', false)
        ->assertSee('العربية', false);
});

it('renders the language switcher on the customer login page', function (): void {
    if (! Package::isPluginInstalled('website')) {
        $this->markTestSkipped('Website plugin is not installed.');
    }

    $this->get('/')
        ->assertSuccessful()
        ->assertSee('English', false)
        ->assertSee('العربية', false);
});
