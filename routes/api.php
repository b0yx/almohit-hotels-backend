<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\BookingController;
use App\Http\Controllers\Api\CrudController;
use App\Http\Controllers\Api\HotelController;
use App\Http\Controllers\Api\ReviewController;
use App\Models\AuditLog;
use App\Models\AvailabilityBlock;
use App\Models\ChannelManagerConnection;
use App\Models\ContactMessage;
use App\Models\HotelAmenity;
use App\Models\HotelImage;
use App\Models\HotelService;
use App\Models\RoomPrice;
use App\Models\RoomType;
use App\Models\RoomTypeImage;
use App\Models\ServiceCategory;
use App\Models\ServiceImage;
use App\Models\User;
use Illuminate\Support\Facades\Route;

Route::middleware(['tenant.context', 'api.token'])->group(function () {
    Route::get('/health/', fn () => response()->json(['status' => 'ok']));
    Route::get('/public/hotel-context/', [HotelController::class, 'publicContext']);

    Route::prefix('auth')->group(function () {
        Route::post('/signup/', [AuthController::class, 'signup']);
        Route::post('/verify-otp/', [AuthController::class, 'verifyOtp']);
        Route::post('/resend-otp/', [AuthController::class, 'resendOtp']);
        Route::post('/login/', [AuthController::class, 'login']);
        Route::post('/admin/login/', [AuthController::class, 'adminLogin']);
        Route::post('/customer/login/', [AuthController::class, 'customerLogin']);
        Route::get('/me/', [AuthController::class, 'me']);
        Route::patch('/me/', [AuthController::class, 'updateMe']);
        Route::post('/logout/', [AuthController::class, 'logout']);
        Route::apiResource('users', CrudController::class)->parameters(['users' => 'id'])->middleware('role:admin')->only(['index', 'store', 'show', 'update', 'destroy']);
        Route::apiResource('admins', CrudController::class)->parameters(['admins' => 'id'])->middleware('role:admin')->only(['index', 'store', 'show', 'update', 'destroy']);
    });

    Route::get('/properties/available/', [HotelController::class, 'index']);
    Route::apiResource('properties', HotelController::class)->parameters(['properties' => 'id']);
    Route::post('/properties/{id}/publish/', [HotelController::class, 'publish']);
    Route::post('/properties/{id}/unpublish/', [HotelController::class, 'unpublish']);
    Route::post('/properties/{id}/archive/', [HotelController::class, 'archive']);
    Route::post('/properties/{id}/unarchive/', [HotelController::class, 'unarchive']);
    Route::get('/properties/{id}/readiness/', [HotelController::class, 'readiness']);
    Route::get('/properties/{id}/setup-status/', [HotelController::class, 'setupStatus']);
    Route::patch('/properties/{id}/autosave/', [HotelController::class, 'autosave']);
    Route::get('/properties/{id}/workspace/', [HotelController::class, 'workspace']);
    Route::get('/properties/{id}/rooms/search/', [HotelController::class, 'roomsSearch']);
    Route::get('/properties/{id}/availability/', [HotelController::class, 'availability']);
    Route::get('/properties/{id}/rates/', [HotelController::class, 'rates']);
    Route::match(['get', 'post'], '/properties/{property}/reviews/', [ReviewController::class, 'propertyReviews']);
    Route::get('/properties/{property}/reviews/summary/', [ReviewController::class, 'summary']);

    Route::apiResource('bookings', BookingController::class)->parameters(['bookings' => 'id']);
    Route::get('/bookings/calendar/', [BookingController::class, 'calendar']);
    Route::post('/bookings/inquiry/', [BookingController::class, 'inquiry']);
    Route::post('/bookings/confirm/', [BookingController::class, 'confirm']);
    Route::post('/bookings/{id}/cancel/', [BookingController::class, 'cancel']);

    Route::apiResource('reviews', ReviewController::class)->parameters(['reviews' => 'id'])->only(['index', 'show', 'update', 'destroy']);

    Route::apiResource('property-amenities', CrudController::class)->parameters(['property-amenities' => 'id']);
    Route::apiResource('property-images', CrudController::class)->parameters(['property-images' => 'id']);
    Route::apiResource('channel-manager-connections', CrudController::class)->parameters(['channel-manager-connections' => 'id']);
    Route::apiResource('room-types', CrudController::class)->parameters(['room-types' => 'id']);
    Route::get('/room-types/{id}/rates/', fn (int $id) => response()->json(['room_type' => $id, 'seasonal_prices' => []]));
    Route::apiResource('room-type-images', CrudController::class)->parameters(['room-type-images' => 'id']);
    Route::apiResource('room-prices', CrudController::class)->parameters(['room-prices' => 'id']);
    Route::apiResource('room-amenities', CrudController::class)->parameters(['room-amenities' => 'id']);
    Route::apiResource('availability-blocks', CrudController::class)->parameters(['availability-blocks' => 'id']);
    Route::apiResource('service-categories', CrudController::class)->parameters(['service-categories' => 'id']);
    Route::apiResource('property-services', CrudController::class)->parameters(['property-services' => 'id']);
    Route::apiResource('service-images', CrudController::class)->parameters(['service-images' => 'id']);
    Route::apiResource('audit-logs', CrudController::class)->parameters(['audit-logs' => 'id'])->only(['index', 'show']);
    Route::apiResource('contact-messages', CrudController::class)->parameters(['contact-messages' => 'id']);
});

app()->bind(CrudController::class, function ($app, array $params = []) {
    $routeName = request()->route()?->getName() ?? '';
    $map = [
        'users' => User::class,
        'admins' => User::class,
        'auth.users' => User::class,
        'auth.admins' => User::class,
        'property-amenities' => HotelAmenity::class,
        'property-images' => HotelImage::class,
        'channel-manager-connections' => ChannelManagerConnection::class,
        'room-types' => RoomType::class,
        'room-type-images' => RoomTypeImage::class,
        'room-prices' => RoomPrice::class,
        'room-amenities' => HotelAmenity::class,
        'availability-blocks' => AvailabilityBlock::class,
        'service-categories' => ServiceCategory::class,
        'property-services' => HotelService::class,
        'service-images' => ServiceImage::class,
        'audit-logs' => AuditLog::class,
        'contact-messages' => ContactMessage::class,
    ];

    foreach ($map as $prefix => $model) {
        if (str_starts_with($routeName, $prefix.'.')) {
            return new CrudController($model);
        }
    }

    return new CrudController(HotelAmenity::class);
});
