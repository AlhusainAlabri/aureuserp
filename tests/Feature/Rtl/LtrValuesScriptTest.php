it('ships the right-to-left value isolation script on admin pages', function (): void {
    $this->get('/admin/login')->assertSuccessful()->assertSee('unicodeBidi', false);
});
