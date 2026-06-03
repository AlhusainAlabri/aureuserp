<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('document-archive::document-archive.password.title') }}</title>
    <style>
        body { font-family: system-ui, sans-serif; display: grid; place-items: center; min-height: 100vh; margin: 0; background: #f8fafc; }
        .card { background: #fff; padding: 2rem; border-radius: 0.75rem; box-shadow: 0 10px 25px rgba(15, 23, 42, 0.08); width: min(420px, 92vw); }
        label { display: block; margin-bottom: 0.5rem; font-weight: 600; }
        input { width: 100%; padding: 0.75rem; border: 1px solid #cbd5e1; border-radius: 0.5rem; }
        button { margin-top: 1rem; width: 100%; padding: 0.75rem; border: 0; border-radius: 0.5rem; background: #ea580c; color: #fff; font-weight: 600; cursor: pointer; }
        .error { color: #b91c1c; margin-top: 0.75rem; }
    </style>
</head>
<body>
    <div class="card">
        <h1>{{ __('document-archive::document-archive.password.title') }}</h1>
        <p>{{ __('document-archive::document-archive.password.body', ['name' => $file->name]) }}</p>

        <form method="POST" action="{{ $actionUrl }}">
            @csrf
            <label for="password">{{ __('document-archive::document-archive.fields.password') }}</label>
            <input id="password" type="password" name="password" required autofocus>
            @if ($errors->has('password'))
                <div class="error">{{ $errors->first('password') }}</div>
            @endif
            <button type="submit">{{ __('document-archive::document-archive.password.submit') }}</button>
        </form>
    </div>
</body>
</html>
