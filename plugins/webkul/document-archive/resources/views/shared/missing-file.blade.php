<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('document-archive::document-archive.missing_file.title') }}</title>
    <style>
        body { font-family: system-ui, sans-serif; display: grid; place-items: center; min-height: 100vh; margin: 0; background: #f8fafc; }
        .card { background: #fff; padding: 2rem; border-radius: 0.75rem; box-shadow: 0 10px 25px rgba(15, 23, 42, 0.08); width: min(480px, 92vw); text-align: center; }
        h1 { margin-top: 0; font-size: 1.25rem; }
        p { color: #475569; line-height: 1.6; }
        a { display: inline-block; margin-top: 1rem; padding: 0.75rem 1.25rem; border-radius: 0.5rem; background: #ea580c; color: #fff; font-weight: 600; text-decoration: none; }
        .ref { font-family: monospace; color: #64748b; font-size: 0.875rem; }
    </style>
</head>
<body>
    <div class="card">
        <h1>{{ __('document-archive::document-archive.missing_file.title') }}</h1>
        <p>{{ __('document-archive::document-archive.missing_file.body', ['name' => $file->name]) }}</p>
        <p class="ref">{{ $file->reference_number }}</p>
        <a href="{{ url()->previous() !== url()->current() ? url()->previous() : route('filament.admin.pages.document-manager') }}">
            {{ __('document-archive::document-archive.missing_file.back') }}
        </a>
    </div>
</body>
</html>
