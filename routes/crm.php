<?php

use App\Http\Controllers\Admin\Crm\CrmProjectController;
use App\Http\Controllers\Admin\Crm\CrmTaskController;
use App\Http\Controllers\Admin\Crm\CrmPersonnelController;
use App\Http\Controllers\Admin\Crm\CrmMicrotaskController;
use App\Http\Controllers\Admin\Crm\CrmCommentController;
use App\Http\Controllers\Admin\Crm\CrmActivityController;
use App\Http\Controllers\Admin\Crm\CrmAttendanceController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| CRM API Routes
|--------------------------------------------------------------------------
| همه route‌های CRM اینجا هستند — جدا از بقیه route‌های پروژه
| prefix: /admin/crm-api
| middleware: web + auth:admin
*/

Route::middleware(['web', 'auth:admin'])
    ->prefix('admin/crm-api')
    ->name('admin.crm.')
    ->group(function () {

        // ── Personnel ─────────────────────────────────────────
        Route::get   ('personnel',                     [CrmPersonnelController::class, 'index']);
        Route::post  ('personnel',                     [CrmPersonnelController::class, 'store']);
        Route::get   ('personnel/{id}',                [CrmPersonnelController::class, 'show']);
        Route::put   ('personnel/{id}',                [CrmPersonnelController::class, 'update']);
        Route::delete('personnel/{id}',                [CrmPersonnelController::class, 'destroy']);
        Route::post  ('personnel/{id}/assign-projects',[CrmPersonnelController::class, 'assignProjects']);

        // ── Projects ──────────────────────────────────────────
        Route::get   ('projects',      [CrmProjectController::class, 'index']);
        Route::post  ('projects',      [CrmProjectController::class, 'store']);
        Route::get   ('projects/{id}', [CrmProjectController::class, 'show']);
        Route::put   ('projects/{id}', [CrmProjectController::class, 'update']);
        Route::delete('projects/{id}', [CrmProjectController::class, 'destroy']);

        // ── Tasks ─────────────────────────────────────────────
        Route::get   ('tasks',            [CrmTaskController::class, 'index']);
        Route::post  ('tasks',            [CrmTaskController::class, 'store']);
        Route::get   ('tasks/{id}',       [CrmTaskController::class, 'show']);
        Route::put   ('tasks/{id}',       [CrmTaskController::class, 'update']);
        Route::delete('tasks/{id}',       [CrmTaskController::class, 'destroy']);
        Route::post  ('tasks/{id}/toggle',[CrmTaskController::class, 'toggle']);

        // ── Microtasks ────────────────────────────────────────
        Route::post  ('tasks/{taskId}/microtasks/{microId}/toggle', [CrmMicrotaskController::class, 'toggle']);
        Route::delete('tasks/{taskId}/microtasks/{microId}',        [CrmMicrotaskController::class, 'destroy']);

        // ── Comments ──────────────────────────────────────────
        Route::get   ('tasks/{taskId}/comments',               [CrmCommentController::class, 'index']);
        Route::post  ('tasks/{taskId}/comments',               [CrmCommentController::class, 'store']);
        Route::delete('tasks/{taskId}/comments/{commentId}',   [CrmCommentController::class, 'destroy']);

        // ── Activity Log ──────────────────────────────────────
        Route::get('activity', [CrmActivityController::class, 'index']);

        // ── Attendance ────────────────────────────────────────
        Route::get   ('attendance',        [CrmAttendanceController::class, 'index']);
        Route::post  ('attendance',        [CrmAttendanceController::class, 'store']);
        Route::put   ('attendance/{id}',   [CrmAttendanceController::class, 'update']);
        Route::delete('attendance/{id}',   [CrmAttendanceController::class, 'destroy']);
        Route::get   ('attendance/export', [CrmAttendanceController::class, 'export']);
    });
