<?php

use Illuminate\Support\Facades\Route;
use Webkul\MyNotes\Models\Note;

Route::middleware(['web', 'auth'])->group(function (): void {
    Route::get('/my-notes/audio/{ulid}', function (string $ulid): mixed {
        $note = Note::withoutGlobalScopes()
            ->where('ulid', $ulid)
            ->where('user_id', auth()->id())
            ->whereNotNull('audio_path')
            ->firstOrFail();

        $path = storage_path('app/'.$note->audio_path);

        abort_unless(file_exists($path), 404);

        return response()->file($path, ['Content-Type' => 'audio/webm']);
    })->name('my-notes.audio.serve');
});
