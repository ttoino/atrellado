<?php

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/
// Home

use App\Enums\ProviderType;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\Auth\OAuthController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\TagController;
use App\Http\Controllers\TaskCommentController;
use App\Http\Controllers\TaskController;
use App\Http\Controllers\TaskGroupController;
use App\Http\Controllers\ThreadCommentController;
use App\Http\Controllers\ThreadController;
use App\Http\Controllers\UserController;
use App\Models\Project;
use Illuminate\Support\Facades\Route;
use Laravel\Fortify\Http\Controllers\AuthenticatedSessionController;
use Laravel\Fortify\Http\Controllers\EmailVerificationNotificationController;
use Laravel\Fortify\Http\Controllers\EmailVerificationPromptController;
use Laravel\Fortify\Http\Controllers\NewPasswordController;
use Laravel\Fortify\Http\Controllers\PasswordResetLinkController;
use Laravel\Fortify\Http\Controllers\RegisteredUserController;
use Laravel\Fortify\Http\Controllers\VerifyEmailController;

Route::get('', [HomeController::class, 'show'])->name('home');

// Static
Route::view('/about', 'static.about')->name('static.about');
Route::view('/contacts', 'static.contacts')->name('static.contacts');
Route::view('/faq', 'static.faq')->name('static.faq');
Route::view('/services', 'static.services')->name('static.services');

// User
Route::prefix('/user')->middleware(['auth', 'verified'])->name('user.')->controller(UserController::class)->group(function () {
    Route::prefix('/{user}')->where(['user' => '[0-9]+'])->group(function () {
        Route::get('', 'show')->name('profile');

        Route::prefix('/edit')->group(function () {
            Route::get('', 'edit')->name('edit');
            Route::post('', 'update')->name('edit-action');
        });

        Route::prefix('/report')->group(function () {
            Route::get('', 'showReportForm')->name('report');
            Route::post('', 'report')->name('report-action');
        });
    });
});

Route::get('/notifications', [UserController::class, 'showNotifications'])->middleware(['auth', 'verified'])->name('notifications');

// Project
Route::prefix('/project')->middleware(['auth', 'verified'])->name('project')->controller(ProjectController::class)->group(function () {
    Route::get('', 'index')->name('.list');

    Route::prefix('/new')->group(function () {
        Route::get('', 'create')->name('.new');
        Route::post('', 'store')->name('.new-action');
    });

    Route::prefix('/{project}')->where(['project' => '[0-9]+'])->middleware('withOtherProjects')->group(function () {

        // Report project
        Route::prefix('/report')->group(function () {
            Route::get('', 'showReportForm')->name('.report');
            Route::post('', 'report')->name('.report-action');
        });

        Route::any('', fn (Project $project) => redirect()->route('project.board', $project))->name('');

        Route::get('/info', 'showProjectInfo')->name('.info');
        Route::get('/members', 'getProjectMembers')->name('.members');
        Route::get('/tags', 'getProjectTags')->name('.tags');
        Route::get('/board', 'showProjectBoard')->name('.board');
        Route::get('/tasks', 'getProjectTasks')->name('.tasks');
        Route::get('/timeline', 'showProjectTimeline')->name('.timeline');
        Route::get('/forum', 'showProjectForum')->name('.forum');

        // This breaks the HTTP standard since a GET request is changing server state (a project's members). However this should only be changed if this application scales
        Route::get('/join', 'joinProject')->name('.join')->middleware('signed');

        Route::post('/delete', 'destroy')->name('.delete');

        Route::prefix('/task')->name('.task')->controller(TaskController::class)->group(function () {
            Route::prefix('/{task}')->where(['task' => '[0-9]+'])->scopeBindings()->group(function () {
                Route::get('', 'show')->name('.info');
            });
        });

        Route::prefix('/thread')->name('.thread')->controller(ThreadController::class)->group(function () {
            Route::prefix('/{thread}')->where(['thread' => '[0-9]+'])->scopeBindings()->group(function () {
                Route::get('', 'show')->name('');
            });
        });
    });
});

// Admin
Route::prefix('/admin')->middleware(['auth', 'verified'])->name('admin')->controller(AdminController::class)->group(function () {
    Route::redirect('', '/admin/users')->name('');

    Route::get('/users', 'listUsers')->name('.users');

    Route::get('/projects', 'listProjects')->name('.projects');

    Route::prefix('/create')->name('.create')->group(function () {
        Route::get('/user', 'showCreateUser')->name('.user');
        Route::post('/user', 'createUser')->name('.user-action');
    });

    Route::prefix('/reports')->name('.reports')->group(function () {
        Route::get('/user/{user}', 'showUserReports')->name('.user');
        Route::get('/project/{project}', 'showProjectReports')->name('.project');
    });
});

// Authentication (Fortify controllers, hand-registered to keep the
// original URLs and route names after dropping laravel/ui)
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('/login', [AuthenticatedSessionController::class, 'store']);
    // GET instead of Fortify's POST: the navbar links logout via a plain anchor.
    Route::get('/logout', [AuthenticatedSessionController::class, 'destroy'])->withoutMiddleware('guest')->middleware('auth')->name('logout');

    Route::get('/register', [RegisteredUserController::class, 'create'])->name('register');
    Route::post('/register', [RegisteredUserController::class, 'store']);

    Route::name('password')->group(function () {
        Route::get('/recover-password', [PasswordResetLinkController::class, 'create'])->name('.request');
        Route::post('/recover-password', [PasswordResetLinkController::class, 'store'])->name('.request-action');
        Route::get('/reset-password/{token}', [NewPasswordController::class, 'create'])->name('.reset');
        Route::post('/reset-password', [NewPasswordController::class, 'store'])->name('.reset-action');
    });

    Route::controller(OAuthController::class)->prefix('/oauth/{provider}')->whereIn('provider', ProviderType::values())->name('oauth')->group(function () {
        Route::get('/redirect', 'redirectOAuth')->name('.redirect');
        Route::get('/callback', 'handleOAuthCallback')->name('.callback');
    });

    Route::prefix('/email')->withoutMiddleware('guest')->middleware('auth')->name('verification')->group(function () {
        Route::get('/verify', EmailVerificationPromptController::class)->name('.notice');
        Route::get('/verify/{id}/{hash}', VerifyEmailController::class)->middleware('signed')->name('.verify');
        Route::post('/verification-notice', [EmailVerificationNotificationController::class, 'store'])->middleware('throttle:6,1')->name('.send');
    });
});

Route::prefix('/api')->name('api.')->middleware(['auth', 'verified', 'throttle'])->group(function () {

    Route::apiResource('project', ProjectController::class)->only(['store', 'show', 'update', 'destroy']);

    Route::prefix('/project/{project}')->whereNumber('project')->controller(ProjectController::class)->group(function () {
        Route::put('/archive', 'archive')->name('project.archive');
        Route::delete('/archive', 'unarchive')->name('project.unarchive');

        Route::post('/leave', 'leaveProject')->name('project.leave');

        Route::get('/members', 'getProjectMembers')->name('project.members');
        Route::delete('/members/{user}', 'removeUser')->name('project.members.remove');

        Route::get('/tags', 'getProjectTags')->name('project.tags');

        Route::post('/favorite/toggle', 'toggleFavorite')->name('project.favorite.toggle');

        Route::post('/invite', 'inviteUser')->name('project.invite-user');

        Route::put('/coordinator', 'setCoordinator')->name('project.coordinator');
    });

    Route::apiResource('user', UserController::class)->only(['store', 'show', 'update', 'destroy']);

    Route::prefix('/user/{user}')->whereNumber('user')->controller(UserController::class)->group(function () {
        Route::post('/block', 'block')->name('user.block');
        Route::post('/unblock', 'unblock')->name('user.unblock');
    });

    Route::apiResource('task', TaskController::class)->only(['store', 'show', 'update', 'destroy']);

    Route::prefix('/task/{task}')->whereNumber('task')->controller(TaskController::class)->group(function () {
        Route::put('/complete', 'complete')->name('task.complete');
        Route::delete('/complete', 'incomplete')->name('task.incomplete');
        Route::post('/reposition', 'update')->name('task.reposition');
    });

    Route::apiResource('task-comment', TaskCommentController::class)
        ->only(['index', 'store', 'show', 'update', 'destroy'])
        ->parameters(['task-comment' => 'taskComment']);

    Route::apiResource('task-group', TaskGroupController::class)
        ->only(['store', 'show', 'update', 'destroy'])
        ->parameters(['task-group' => 'taskGroup']);

    Route::prefix('/task-group/{taskGroup}')->whereNumber('taskGroup')->group(function () {
        Route::post('/reposition', [TaskGroupController::class, 'update'])->name('task-group.reposition');
    });

    Route::apiResource('thread', ThreadController::class)->only(['store', 'show', 'update', 'destroy']);

    Route::apiResource('thread-comment', ThreadCommentController::class)
        ->only(['index', 'store', 'show', 'update', 'destroy'])
        ->parameters(['thread-comment' => 'threadComment']);

    Route::apiResource('tag', TagController::class)->only(['store', 'show', 'update', 'destroy']);

    Route::prefix('/notifications/{notification}')->controller(NotificationController::class)->group(function () {
        Route::get('', 'show')->name('notification.show');
        Route::put('/read', 'markAsRead')->name('notification.mark-read');
    });
});
