<?php

use App\Http\Controllers\Api\AgentController;
use App\Http\Controllers\Api\ApiTokenController;
use App\Http\Controllers\Api\UserController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Manager REST API. Bearer token (api_tokens) auth. Base: /api/v1
// Route names are prefixed with "api." so they never collide with the web
// resource route names (customers.*, domains.*, nodes.*).
Route::prefix('v1')->name('api.')->middleware('api.token')->group(function () {
    Route::get('me', fn (Request $r) => $r->user()->only(['id', 'name', 'email']));

    // Access & administration.
    Route::apiResource('users', UserController::class);
    Route::apiResource('api-tokens', ApiTokenController::class)->only(['index', 'store', 'destroy'])->parameters(['api-tokens' => 'apiToken']);
});

// Node agent API. Nodes dial out to this; nothing here is reachable inbound from
// the panel, so a node needs only outbound 443. Enrol is one-time-token based,
// everything after uses the per-node key. Base: /api/agent/v1
Route::prefix('agent/v1')->name('agent.')->group(function () {
    Route::post('enroll', [AgentController::class, 'enroll']);

    Route::middleware('agent.auth')->group(function () {
        // Config pull: domains, destinations, thresholds, rules.
        Route::get('config', [AgentController::class, 'config']);
        Route::post('heartbeat', [AgentController::class, 'heartbeat']);

        // Reporting.
        Route::post('quarantine', [AgentController::class, 'quarantine']);
        Route::post('log', [AgentController::class, 'log']);

        // Release work queue the node polls and reports back on.
        Route::get('releases', [AgentController::class, 'releases']);
        Route::post('releases/{uuid}', [AgentController::class, 'releaseResult']);
    });
});
