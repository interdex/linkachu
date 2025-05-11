<?php
// routes/api.php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserController;
use App\Http\Controllers\AuthController;
use App\Http\Middleware\JWTMiddleware;
use App\Http\Controllers\PBNServiceController;
use App\Http\Controllers\ScanningController;


// Маршруты, которые не требуют аутентификации
Route::post('/register', [AuthController::class, 'register'])->name('register')
    ->withoutMiddleware([JWTMiddleware::class]);
Route::post('/login', [AuthController::class, 'login'])->name('login')
    ->withoutMiddleware([JWTMiddleware::class]);

// Группа маршрутов, требующих аутентификации
Route::middleware(JWTMiddleware::class)->group(function() {
    Route::get('/example', [AuthController::class, 'example'])->name('example');
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    Route::get('/current-user', [AuthController::class, 'currentUser'])->name('currentUser');
    Route::get('/users', [UserController::class, 'index'])->name('users.index');
    Route::post('/users', [UserController::class, 'store'])->name('users.store');
    Route::get('/users/{id}', [UserController::class, 'show'])->name('users.show');
    Route::put('/users/{id}', [UserController::class, 'update'])->name('users.update');
    Route::delete('/users/{id}', [UserController::class, 'destroy'])->name('users.destroy');


    Route::post('/pbn_sites', [PBNServiceController::class, 'store']);
    Route::get('/pbn_sites', [PBNServiceController::class, 'index']);
    Route::get('/pbn_sites/{id}', [PBNServiceController::class, 'show']);

    //PBN Сервис
    Route::post('/start-scanning/{id}', [ScanningController::class, 'startScanning']);
    Route::get('/scanning-status/{id}', [ScanningController::class, 'scanningStatus']);
    Route::get('/domains/{id}', [ScanningController::class, 'getDomains']);
});
