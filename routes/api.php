<?php

use App\Http\Controllers\Api\NewsletterController;
use App\Http\Controllers\Api\SupportController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

// Newsletter API Routes
Route::prefix('newsletter')->name('newsletter.')->group(function () {
    Route::get('/', [NewsletterController::class, 'index'])->name('index');
    Route::post('/', [NewsletterController::class, 'store'])->name('store');
    Route::get('/verify/{token}', [NewsletterController::class, 'verify'])->name('verify');
    Route::get('/{newsletter}', [NewsletterController::class, 'show'])->name('show');
    Route::put('/{newsletter}', [NewsletterController::class, 'update'])->name('update');
    Route::delete('/unsubscribe/{email}', [NewsletterController::class, 'unsubscribe'])->name('unsubscribe');
    Route::delete('/{newsletter}', [NewsletterController::class, 'destroy'])->name('destroy');
});

// Support System API Routes
Route::prefix('support')->name('support.')->group(function () {
    Route::get('/', [SupportController::class, 'index'])->name('index');
    Route::post('/', [SupportController::class, 'store'])->name('store');
    Route::get('/statistics', [SupportController::class, 'statistics'])->name('statistics');
    Route::get('/email/{email}', [SupportController::class, 'getByEmail'])->name('getByEmail');
    Route::get('/{ticketId}', [SupportController::class, 'show'])->name('show');
    Route::put('/{ticketId}', [SupportController::class, 'update'])->name('update');
    Route::delete('/{ticketId}', [SupportController::class, 'destroy'])->name('destroy');
    // Explicit contact endpoint
    Route::post('/contact', [SupportController::class, 'contact'])->name('contact');
});

// Health check endpoint
Route::get('/health', function () {
    return response()->json([
        'success' => true,
        'message' => 'Support API is running',
        'timestamp' => now(),
        'version' => '1.0.0',
    ]);
});
