<?php

// QA only: renders the completion page for an existing check-in into a static file.
require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
app('url')->forceRootUrl('http://127.0.0.1:8000');

$key = $argv[1] ?? 'cd8ce336-1395-4223-9df5-9fd4cf3d8829';
$note = $argv[2] ?? null;
$record = App\Models\PsychologyMoodCheckin::query()->where('completion_key', $key)->firstOrFail();

$request = Illuminate\Http\Request::create('http://127.0.0.1:8000/tools/psychology/mood/complete/'.$key);
$route = app('router')->getRoutes()->match($request);
$request->setRouteResolver(fn () => $route);
app()->instance('request', $request);
$summary = app(App\Services\MoodCheckin::class)->summary($record->mood_key, $record->emotion_keys ?? []);

$html = view('frontend.sections.mood-complete', [
    'moodKey' => $record->mood_key,
    'checkedAt' => $record->checked_in_at?->copy()->timezone('Asia/Kolkata'),
    'mood' => $summary['mood'],
    'emotions' => $summary['emotions'],
    'reflection' => $note === '-' ? null : ($note ?? $record->reflection),
    'psychologistsUrl' => app(App\Services\PsychologyHome::class)->psychologistsUrl(),
])->render();

file_put_contents(__DIR__.'/../public/_qa_complete.html', $html);
echo "ok mood={$record->mood_key} emotions=".count($summary['emotions']).' note='.(filled($record->reflection) ? 'yes' : 'no').PHP_EOL;
