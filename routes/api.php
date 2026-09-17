<?php

use App\Http\Controllers\PostController;
use App\Http\Controllers\PostTagController;
use App\Http\Controllers\TagController;
use App\Http\Controllers\PublishController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

// Baseline endpoint — Resource контроллер для Posts
Route::apiResource('posts', PostController::class);

// Блок Б — код-ревью, не рефакторить
Route::post('/publish/batch', [PublishController::class, 'batch']);
Route::get('/publish/report', [PublishController::class, 'report']);

// Блок А — теги поста
Route::post('posts/{post}/tags', [PostTagController::class, 'store']);
Route::delete('posts/{post}/tags/{tag}', [PostTagController::class, 'destroy']);

// Блок А — CRUD тегов
Route::apiResource('tags', TagController::class);
