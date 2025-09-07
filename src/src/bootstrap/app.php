<?php

use App\Http\Controllers\EntityController;
use App\Http\Controllers\QueryController;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Middleware;
use App\Http\Middleware\ApiKeyAuth;
use Illuminate\Support\Facades\Route;

Route::middleware('api.key')->group(function () {
    Route::post  ('/crud/{entity}/save',         [EntityController::class, 'save']);
    Route::get   ('/crud/{entity}/{id}',         [EntityController::class, 'show'])->whereNumber('id');
    Route::delete('/crud/{entity}/{id}',         [EntityController::class, 'delete'])->whereNumber('id');
    Route::post  ('/crud/{entity}/{id}/restore', [EntityController::class, 'restore'])->whereNumber('id');

    Route::get('/orgs/near',      [QueryController::class, 'near']);
    Route::get('/buildings/list', [QueryController::class, 'buildings']);
});

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias(['api.key' => ApiKeyAuth::class]);
    })
    ->create();

