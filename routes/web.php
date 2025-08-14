<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::view('/', 'welcome');

Route::view('dashboard', 'dashboard')
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::view('profile', 'profile')
    ->middleware(['auth'])
    ->name('profile');

Route::view('/qr-attendance', 'qr-attendance')->name('qr-attendance');

Route::view('/attendance/in', 'qr-attendance')->name('attendance.in');
Route::view('/attendance/out', 'qr-attendance')->name('attendance.out');

require __DIR__ . '/auth.php';
