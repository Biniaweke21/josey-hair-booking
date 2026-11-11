<?php

use App\Http\Controllers\Api\AddOnController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\AvailabilityController;
use App\Http\Controllers\Api\BookingController;
use App\Http\Controllers\Api\BusinessHourController;
use App\Http\Controllers\Api\BusinessHourExceptionController;
use App\Http\Controllers\Api\CapacityDashboardController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\CustomerController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\EmployeeController;
use App\Http\Controllers\Api\EmployeeLeaveController;
use App\Http\Controllers\Api\EmployeeWeeklyScheduleController;
use App\Http\Controllers\Api\GalleryItemController;
use App\Http\Controllers\Api\NotificationLogController;
use App\Http\Controllers\Api\NotificationTemplateController;
use App\Http\Controllers\Api\PermissionController;
use App\Http\Controllers\Api\ProfileController;
use App\Http\Controllers\Api\ReportController;
use App\Http\Controllers\Api\RoleController;
use App\Http\Controllers\Api\ServiceAddOnController;
use App\Http\Controllers\Api\ServiceController;
use App\Http\Controllers\Api\ServiceVariationOptionController;
use App\Http\Controllers\Api\ServiceVariationTypeController;
use App\Http\Controllers\Api\SettingController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\VariationOptionController;
use App\Http\Controllers\Api\VariationTypeController;
use Illuminate\Support\Facades\Route;

Route::prefix('auth')->group(function () {
    Route::middleware('throttle:login')->post('/login', [AuthController::class, 'login']);
    Route::middleware('throttle:password-reset')->post('/forgot-password', [AuthController::class, 'forgotPassword']);
    Route::middleware('throttle:password-reset-submit')->post('/reset-password', [AuthController::class, 'resetPassword']);

    Route::middleware(['auth:sanctum', 'active'])->post('/logout', [AuthController::class, 'logout']);
});

// Public booking flow - no account required, rate-limited against abuse.
// Availability checks are read-only and probed a lot while a customer picks
// a slot, so they get a looser cap than actually creating a booking.
Route::middleware('throttle:60,1')->post('/booking/availability', [AvailabilityController::class, 'check']);
Route::middleware('throttle:10,1')->post('/booking', [BookingController::class, 'store']);

// Public catalog + salon info - the marketing site and booking flow read
// these before a customer has any account. Nothing here is sensitive;
// only mutations require auth (see the admin group below).
Route::middleware('throttle:120,1')->group(function () {
    Route::get('/categories', [CategoryController::class, 'index']);
    Route::get('/categories/{category}', [CategoryController::class, 'show']);

    Route::get('/services', [ServiceController::class, 'index']);
    Route::get('/services/{service}', [ServiceController::class, 'show']);
    Route::get('/services/{service}/variation-types', [ServiceVariationTypeController::class, 'index']);
    Route::get('/services/{service}/variation-options', [ServiceVariationOptionController::class, 'index']);
    Route::get('/services/{service}/add-ons', [ServiceAddOnController::class, 'index']);

    Route::get('/variation-types', [VariationTypeController::class, 'index']);
    Route::get('/variation-types/{variationType}', [VariationTypeController::class, 'show']);
    Route::get('/variation-types/{variationType}/options', [VariationOptionController::class, 'index']);
    Route::get('/variation-options/{variationOption}', [VariationOptionController::class, 'show']);

    Route::get('/add-ons', [AddOnController::class, 'index']);
    Route::get('/add-ons/{addOn}', [AddOnController::class, 'show']);

    Route::get('/business-hours', [BusinessHourController::class, 'index']);
    Route::get('/settings', [SettingController::class, 'index']);

    Route::get('/gallery-items', [GalleryItemController::class, 'index']);
});

Route::middleware(['auth:sanctum', 'active', 'throttle:admin-api'])->group(function () {
    Route::prefix('profile')->group(function () {
        Route::get('/', [ProfileController::class, 'show']);
        Route::put('/', [ProfileController::class, 'update']);
        Route::put('/password', [ProfileController::class, 'updatePassword']);
    });

    Route::apiResource('roles', RoleController::class);
    Route::get('/permissions', [PermissionController::class, 'index']);

    Route::get('/users', [UserController::class, 'index']);
    Route::get('/users/{user}', [UserController::class, 'show']);
    Route::patch('/users/{user}/role', [UserController::class, 'assignRole']);

    // Salon configuration (reads are public - see the public group above)
    Route::put('/settings', [SettingController::class, 'update']);
    Route::post('/settings/logo', [SettingController::class, 'uploadLogo']);

    Route::put('/business-hours', [BusinessHourController::class, 'update']);

    Route::apiResource('business-hour-exceptions', BusinessHourExceptionController::class)
        ->only(['index', 'store', 'update', 'destroy']);

    Route::apiResource('notification-templates', NotificationTemplateController::class)
        ->only(['index', 'show', 'update']);

    // Notification queue (read-only visibility + manual retry)
    Route::get('/notification-logs', [NotificationLogController::class, 'index']);
    Route::get('/notification-logs/unread-count', [NotificationLogController::class, 'unreadCount']);
    Route::post('/notification-logs/mark-read', [NotificationLogController::class, 'markAllRead']);
    Route::get('/notification-logs/{notificationLog}', [NotificationLogController::class, 'show']);
    Route::post('/notification-logs/{notificationLog}/read', [NotificationLogController::class, 'markRead']);
    Route::post('/notification-logs/{notificationLog}/resend', [NotificationLogController::class, 'resend']);

    // Catalog (index/show are public - see the public group above)
    Route::post('/categories/{category}/image', [CategoryController::class, 'uploadImage']);
    Route::apiResource('categories', CategoryController::class)->except(['index', 'show']);

    Route::post('/services/{service}/image', [ServiceController::class, 'uploadImage']);
    Route::apiResource('services', ServiceController::class)->except(['index', 'show']);

    // Variation types & values (global, reusable vocabulary)
    Route::post('/variation-types/{variationType}/options', [VariationOptionController::class, 'store']);
    Route::apiResource('variation-types', VariationTypeController::class)->except(['index', 'show']);
    Route::apiResource('variation-options', VariationOptionController::class)->only(['update', 'destroy']);

    // Which variation types/values a service uses, and their price/duration adjustment
    Route::post('/services/{service}/variation-types', [ServiceVariationTypeController::class, 'store']);
    Route::delete('/services/{service}/variation-types/{variationType}', [ServiceVariationTypeController::class, 'destroy']);

    Route::put('/services/{service}/variation-options/{variationOption}', [ServiceVariationOptionController::class, 'update']);
    Route::delete('/services/{service}/variation-options/{variationOption}', [ServiceVariationOptionController::class, 'destroy']);

    // Add-ons (global, reusable) and which services offer them
    Route::apiResource('add-ons', AddOnController::class)->except(['index', 'show']);

    Route::post('/services/{service}/add-ons', [ServiceAddOnController::class, 'store']);
    Route::delete('/services/{service}/add-ons/{addOn}', [ServiceAddOnController::class, 'destroy']);

    // Gallery (index is public - see the public group above)
    Route::apiResource('gallery-items', GalleryItemController::class)->except(['index']);

    // Customers (read-only - customer records are created implicitly via booking)
    Route::get('/customers', [CustomerController::class, 'index']);
    Route::get('/customers/{customer}', [CustomerController::class, 'show']);

    // Employees
    Route::apiResource('employees', EmployeeController::class);

    // Weekly roster: which employees work which day_of_week. No shift times,
    // no breaks - the booking engine only needs a headcount per day.
    Route::get('/employees/{employee}/schedules', [EmployeeWeeklyScheduleController::class, 'index']);
    Route::post('/employees/{employee}/schedules', [EmployeeWeeklyScheduleController::class, 'store']);
    Route::put('/employees/{employee}/schedules/{schedule}', [EmployeeWeeklyScheduleController::class, 'update']);
    Route::delete('/employees/{employee}/schedules/{schedule}', [EmployeeWeeklyScheduleController::class, 'destroy']);

    Route::get('/employees/{employee}/leaves', [EmployeeLeaveController::class, 'index']);
    Route::post('/employees/{employee}/leaves', [EmployeeLeaveController::class, 'store']);
    Route::put('/employees/{employee}/leaves/{leave}', [EmployeeLeaveController::class, 'update']);
    Route::delete('/employees/{employee}/leaves/{leave}', [EmployeeLeaveController::class, 'destroy']);

    // Bookings (admin management - creation is public, see above)
    Route::get('/bookings', [BookingController::class, 'index']);
    Route::get('/bookings/{booking}', [BookingController::class, 'show']);
    Route::get('/bookings/{booking}/timeline', [BookingController::class, 'timeline']);
    Route::get('/bookings/{booking}/available-employees', [BookingController::class, 'availableEmployees']);
    Route::patch('/bookings/{booking}/services/{bookingItem}/employee', [BookingController::class, 'assignItemEmployee']);
    Route::patch('/bookings/{booking}/reschedule', [BookingController::class, 'reschedule']);
    Route::patch('/bookings/{booking}/check-in', [BookingController::class, 'checkIn']);
    Route::patch('/bookings/{booking}/check-out', [BookingController::class, 'checkOut']);
    Route::patch('/bookings/{booking}/status', [BookingController::class, 'updateStatus']);

    // Dashboard & reports - everything computed live, nothing cached/hardcoded
    Route::get('/dashboard', [DashboardController::class, 'index']);
    Route::get('/reports/bookings-timeseries', [ReportController::class, 'bookingsTimeSeries']);
    Route::get('/reports/revenue-timeseries', [ReportController::class, 'revenueTimeSeries']);
    Route::get('/reports/popular-services', [ReportController::class, 'popularServices']);
    Route::get('/reports/employee-workload', [ReportController::class, 'employeeWorkload']);
    Route::get('/reports/top-customers', [ReportController::class, 'topCustomers']);

    // Capacity dashboard - exposes the booking engine's own capacity numbers
    // (never recomputes them) for a given day.
    Route::prefix('capacity/{date}')->where(['date' => '\d{4}-\d{2}-\d{2}'])->group(function () {
        Route::get('/summary', [CapacityDashboardController::class, 'summary']);
        Route::get('/timeline', [CapacityDashboardController::class, 'timeline']);
        Route::get('/bookings', [CapacityDashboardController::class, 'bookings']);
        Route::get('/analytics', [CapacityDashboardController::class, 'analytics']);
    });
});
