<?php

use App\Http\Controllers\MessengerController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\UserProfileController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    // Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__ . '/auth.php';

// Route::prefix('admin')->group(function () {
// });

Route::group(['middleware' => 'auth'], function () {

    Route::get('messenger', [MessengerController::class, 'index'])->name('home');

    Route::post('profile', [UserProfileController::class, 'update'])->name('profile.update');

    // todo: Search Route
    Route::get('messenger/search', [MessengerController::class, 'search'])->name('messenger.search');

    // todo: Fetch user by id

    Route::get('/messenger/id-info', action: [MessengerController::class, 'fetchIdInfo'])->name('messenger.id-info');

    // todo: Send Message
    Route::post('/messenger/send-message', [MessengerController::class, 'sendMessage'])->name('messenger.send-message');

    // todo: Fetch Message
    Route::get('/messenger/fetch-messages', [MessengerController::class, 'fetchMessages'])->name('messenger.fetch-messages');

});