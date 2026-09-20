<?php

use App\Http\Controllers\ArticleController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\FeedController;
use App\Http\Controllers\SettingController;
use App\Http\Controllers\TagController;
use App\Http\Controllers\TranslationController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => redirect()->route('articles.index'));

Route::get('/login', [LoginController::class, 'show'])->name('login')->middleware('guest');
Route::post('/login', [LoginController::class, 'login'])->name('login.submit')->middleware('guest');
Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

Route::middleware('auth')->group(function () {
    Route::get('/articles', [ArticleController::class, 'index'])->name('articles.index');
    Route::post('/articles/fetch-now', [ArticleController::class, 'triggerFetch'])->name('articles.fetch-now');
    Route::post('/articles/{article}/read', [ArticleController::class, 'markRead'])->name('articles.read');
    Route::get('/articles/{article}/content', [ArticleController::class, 'content'])->name('articles.content');
    Route::post('/articles/{article}/translate', [TranslationController::class, 'translate'])->name('articles.translate');
    Route::post('/articles/{article}/translate-card', [TranslationController::class, 'translateCard'])->name('articles.translate-card');

    Route::resource('feeds', FeedController::class)->except(['show']);

    Route::get('/settings', [SettingController::class, 'edit'])->name('settings.edit');
    Route::put('/settings', [SettingController::class, 'update'])->name('settings.update');

    Route::get('/tags', [TagController::class, 'index'])->name('tags.index');
    Route::post('/tags/set', [TagController::class, 'setTag'])->name('tags.set');
});
