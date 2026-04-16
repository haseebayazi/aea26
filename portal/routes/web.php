<?php

use App\Http\Controllers\AnalyticsController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ExportController;
use App\Http\Controllers\FileController;
use App\Http\Controllers\ImportController;
use App\Http\Controllers\ReviewController;
use App\Http\Controllers\StudentController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

// ── Auth (public) ────────────────────────────────────────────────────────────
Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'authenticate'])->name('login.post');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

// ── Authenticated routes ─────────────────────────────────────────────────────
Route::middleware(['auth.portal'])->group(function () {

    // Dashboard
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

    // Students
    Route::get('/students', [StudentController::class, 'index'])->name('students.index');
    Route::get('/students/{student}', [StudentController::class, 'show'])->name('students.show');

    // Reviews
    Route::post('/students/{student}/review', [ReviewController::class, 'store'])->name('reviews.store');
    Route::put('/students/{student}/review', [ReviewController::class, 'update'])->name('reviews.update');
    Route::post('/students/{student}/review/autosave', [ReviewController::class, 'autosave'])->name('reviews.autosave');
    Route::post('/students/{student}/review/complete', [ReviewController::class, 'complete'])->name('reviews.complete');
    Route::get('/my-reviews', [ReviewController::class, 'myReviews'])->name('reviews.mine');

    // Files (accessible by all authenticated users; rate-limited to prevent scraping)
    Route::get('/files/{file}/download', [FileController::class, 'download'])
        ->name('files.download')
        ->middleware('throttle:120,1');

    // ── Admin-only routes ────────────────────────────────────────────────────
    Route::middleware(['role:admin'])->group(function () {

        // Analytics
        Route::get('/analytics', [AnalyticsController::class, 'index'])->name('analytics.index');
        Route::get('/analytics/chart/{chart}', [AnalyticsController::class, 'chartData'])->name('analytics.chart');

        // Import wizard
        Route::get('/admin/import', [ImportController::class, 'index'])->name('admin.import');
        Route::post('/admin/import/upload', [ImportController::class, 'upload'])->name('admin.import.upload');
        Route::post('/admin/import/map', [ImportController::class, 'map'])->name('admin.import.map');
        Route::post('/admin/import/execute', [ImportController::class, 'execute'])->name('admin.import.execute');
        Route::get('/admin/import/reset', [ImportController::class, 'reset'])->name('admin.import.reset');

        // Users management
        Route::resource('/admin/users', UserController::class)->names('admin.users');
        Route::post('/admin/users/{user}/assign', [UserController::class, 'assignCategories'])->name('admin.users.assign');
        Route::post('/admin/users/{user}/toggle', [UserController::class, 'toggleActive'])->name('admin.users.toggle');
        Route::post('/admin/users/{user}/reset-password', [UserController::class, 'resetPassword'])->name('admin.users.reset-password');

        // File uploads
        Route::post('/students/{student}/files', [FileController::class, 'upload'])->name('students.files.upload');
        Route::delete('/files/{file}', [FileController::class, 'destroy'])->name('files.destroy');
        Route::post('/admin/files/bulk-upload', [FileController::class, 'bulkUpload'])->name('admin.files.bulk');

        // Exports
        Route::get('/admin/export', [ExportController::class, 'index'])->name('admin.export');
        Route::get('/admin/export/full', [ExportController::class, 'fullExcel'])->name('admin.export.full');
        Route::get('/admin/export/summary', [ExportController::class, 'summaryCsv'])->name('admin.export.summary');
        Route::get('/admin/export/winners', [ExportController::class, 'winnersReport'])->name('admin.export.winners');
        Route::get('/admin/export/student/{student}/pdf', [ExportController::class, 'studentPdf'])->name('admin.export.student-pdf');
    });
});
