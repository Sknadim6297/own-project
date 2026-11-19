<?php

// QA only: renders the history page for an existing customer into static files.
require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
app('url')->forceRootUrl('http://127.0.0.1:8000');

$key = $argv[1] ?? 'cd8ce336-1395-4223-9df5-9fd4cf3d8829';
$record = App\Models\PsychologyMoodCheckin::query()->where('completion_key', $key)->firstOrFail();
$user = App\Models\User::query()->findOrFail($record->user_id);

$request = Illuminate\Http\Request::create('http://127.0.0.1:8000/tools/psychology/mood/history');
$route = app('router')->getRoutes()->match($request);
$request->setRouteResolver(fn () => $route);
app()->instance('request', $request);
Illuminate\Support\Facades\Auth::guard('user')->setUser($user);

$view = app()->call([app(App\Http\Controllers\Frontend\MoodCheckinController::class), 'history'], ['request' => $request]);
file_put_contents(__DIR__.'/../public/_qa_history.html', $view->render());

$empty = view('frontend.sections.mood-history', [
    'items' => [],
    'entries' => new Illuminate\Pagination\LengthAwarePaginator([], 0, 8, 1),
    'stats' => null,
])->render();
file_put_contents(__DIR__.'/../public/_qa_history_empty.html', $empty);

echo 'ok entries='.App\Models\PsychologyMoodCheckin::query()->where('user_id', $user->id)->count().PHP_EOL;
