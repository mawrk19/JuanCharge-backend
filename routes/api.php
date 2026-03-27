<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\LguUserController;
use App\Http\Controllers\KioskController;
use App\Http\Controllers\KioskUserController;
use App\Http\Controllers\ChargingController;
use App\Http\Controllers\MobileAuthController;
use App\Http\Controllers\DashboardController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PointVoucherController;
use App\Http\Controllers\PortActivationController;
use App\Http\Controllers\RecyclingLogController;
use App\Http\Controllers\Auth\PasswordSetupController;
use App\Http\Controllers\LguSystemSettingController;
use App\Http\Controllers\CollectionScheduleController;
use App\Http\Controllers\CollectionNotificationController;
use App\Http\Controllers\Lgu\FieldReportController;
use App\Http\Controllers\Lgu\MaintenanceTicketController;
use App\Http\Controllers\Lgu\FieldReportInsightsController;
use App\Http\Controllers\Lgu\AuditTrailController;
use App\Http\Controllers\PatronLeaderboardController;
use App\Http\Controllers\Admin\LeaderboardAdminController;
use Illuminate\Http\Request;

// Public routes
Route::get('/', function () {
    return response()->json(['message' => 'API is working']);
});
Route::post('/auth/login', [AuthController::class, 'login']);
Route::post('/auth/register', [KioskUserController::class, 'register']);


// Kiosk Machine Routes (Public/Machine Auth)
Route::get('/kiosk/status/{kiosk_code}', [KioskController::class, 'status']);
Route::get('/kiosks/status/{kiosk_code}', [KioskController::class, 'status']);
Route::post('/kiosk/vouchers', [PointVoucherController::class, 'store']);
Route::post('/kiosk/vouchers/sync', [PointVoucherController::class, 'sync']); // Offline Sync Job
Route::post('/kiosk/recycling-logs/sync', [RecyclingLogController::class, 'sync']); // Kiosk Recycling Sync
Route::post('/kiosk/redeem', [ChargingController::class, 'redeemFromKiosk']);
Route::post('/kiosk/heartbeat', [KioskController::class, 'heartbeat']);

// Mobile-specific auth routes (for patron/kiosk users)
Route::middleware(['mobile-api'])->group(function () {
    // OTP Routes (Matches user's request: /api/auth/otp/start)
    Route::post('/auth/otp/start', [MobileAuthController::class, 'startOtp'])->middleware('throttle:3,1');
    Route::post('/auth/otp/verify', [MobileAuthController::class, 'verifyOtp'])->middleware('throttle:6,1');

    // Legacy/Direct Login Routes
    Route::post('/auth/mobile/login', [MobileAuthController::class, 'mobileLogin']);
    Route::post('/auth/mobile/auto-login', [MobileAuthController::class, 'autoLogin']);
    Route::post('/auth/mobile/refresh-token', [MobileAuthController::class, 'refreshDeviceToken']);
    Route::get('/auth/mobile/debug-check', [MobileAuthController::class, 'debugCheck']);

    // Protected mobile routes (Bearer token required)
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/auth/mobile/logout', [MobileAuthController::class, 'mobileLogout']);
    });
});

// Password reset routes (public)
Route::post('/auth/forgot-password', [AuthController::class, 'forgotPassword']);
Route::post('/auth/reset-password', [AuthController::class, 'resetPassword']);
Route::get('/auth/email/verify-change/{token}', [AuthController::class, 'verifyEmailChange'])
    ->name('auth.email.change.verify')
    ->middleware('signed');

// Protected routes (require Sanctum authentication)
Route::middleware('auth:sanctum')->group(function () {
    // Auth routes
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::post('/auth/refresh', [AuthController::class, 'refresh']);
    Route::get('/auth/me', [AuthController::class, 'me']);
    Route::get('/auth/validpate', [AuthController::class, 'validateToken']);
    Route::get('/auth/validate', [AuthController::class, 'validateToken']);
    Route::put('/auth/profile', [AuthController::class, 'updateProfile']);
    Route::post('/auth/change-password', [AuthController::class, 'changePassword']);
    Route::post('/auth/email/change-request', [AuthController::class, 'requestEmailChangeVerification']);
    Route::post('/auth/phone/verification/send-otp', [AuthController::class, 'sendPhoneVerificationOtp'])->middleware('throttle:3,1');
    Route::post('/auth/phone/verification/verify-otp', [AuthController::class, 'verifyPhoneOtp'])->middleware('throttle:10,1');
    
    // Leaderboard routes (Global access within auth:sanctum)
    Route::get('/patron/leaderboard', [ChargingController::class, 'getLeaderboard']);
    Route::get('/patrons/leaderboards/current', [PatronLeaderboardController::class, 'current']);
    Route::get('/patrons/leaderboards/seasons', [PatronLeaderboardController::class, 'seasons']);

    // Activation routes are used by kiosk/mobile redemption flows and admin tools.
    Route::middleware ('auth:sanctum')->group(function (){
        Route::post('/charging/activate', [PortActivationController::class, 'activate']);
       // alias
    });

    Route::middleware('role:super_admin|lgu_admin|lgu_staff|lgu_technician')->group(function () {
        // LGU Field Reports (staff submit + admin/super_admin review)
        Route::post('/lgu/field-reports', [FieldReportController::class, 'store']);
        Route::post('/kiosk-field-reports', [FieldReportController::class, 'store']); // temporary alias

        // LGU Collection Notifications
        Route::get('/collection-notifications', [CollectionNotificationController::class, 'index']);
        Route::patch('/collection-notifications/{id}/read', [CollectionNotificationController::class, 'markAsRead']);
        Route::post('/collection-notifications/{id}/read', [CollectionNotificationController::class, 'markAsRead']);

        // LGU System Configuration (exchange rates)
        Route::get('/system-config', [LguSystemSettingController::class, 'show']);

        // LGU Users CRUD
        Route::get('/lgu-users', [LguUserController::class, 'index']);
        Route::post('/lgu-users', [LguUserController::class, 'store']);
        Route::get('/lgu-users/{id}', [LguUserController::class, 'show']);
        Route::put('/lgu-users/{id}', [LguUserController::class, 'update']);
        Route::delete('/lgu-users/{id}', [LguUserController::class, 'destroy']);
        Route::patch('/lgu-users/{id}/disable', [LguUserController::class, 'disableUser']);

        // Kiosk write actions
        Route::post('/kiosks', [KioskController::class, 'store']);
        Route::put('/kiosks/{id}', [KioskController::class, 'update']);
        Route::delete('/kiosks/{id}', [KioskController::class, 'destroy']);

        // Collection schedules per LGU
        Route::get('/collection-schedules', [CollectionScheduleController::class, 'index']);
        Route::post('/collection-schedules', [CollectionScheduleController::class, 'store']);
        Route::get('/collection-schedules/{id}', [CollectionScheduleController::class, 'show']);
        Route::put('/collection-schedules/{id}', [CollectionScheduleController::class, 'update']);
        Route::delete('/collection-schedules/{id}', [CollectionScheduleController::class, 'destroy']);

        // Kiosk Users CRUD
        Route::get('/kiosk-users', [KioskUserController::class, 'index']);
        Route::post('/kiosk-users', [KioskUserController::class, 'store']);
        Route::get('/kiosk-users/{id}', [KioskUserController::class, 'show']);
        Route::put('/kiosk-users/{id}', [KioskUserController::class, 'update']);
        Route::delete('/kiosk-users/{id}', [KioskUserController::class, 'destroy']);

        // Alias routes for backward compatibility (plural form)
        Route::get('/kiosks-users', [KioskUserController::class, 'index']);
        Route::post('/kiosks-users', [KioskUserController::class, 'store']);
        Route::get('/kiosks-users/{id}', [KioskUserController::class, 'show']);
        Route::put('/kiosks-users/{id}', [KioskUserController::class, 'update']);
        Route::delete('/kiosks-users/{id}', [KioskUserController::class, 'destroy']);

        // Admin Dashboard Routes

        Route::prefix('points')->group(function () {
            Route::get('/overview', [DashboardController::class, 'getOverview']);
            Route::get('/sessions', [DashboardController::class, 'getRecentSessions']);
            Route::get('/recycling', [DashboardController::class, 'getRecentRecycling']);
            Route::get('/chart', [DashboardController::class, 'getChartData']);
        });
        Route::prefix('dashboard')->group(function () {
            Route::get('/overview', [DashboardController::class, 'getOverview']);
            Route::get('/sessions', [DashboardController::class, 'getRecentSessions']);
            Route::get('/recycling', [DashboardController::class, 'getRecentRecycling']);
        });

        // New Recycling Analytics for Hardware Stats
        Route::get('/admin/analytics/recycling', [\App\Http\Controllers\Admin\RecyclingAnalyticsController::class, 'index']);
    });

    Route::middleware('role:super_admin|lgu_admin|lgu_staff|lgu_technician|kiosk_user')->group(function () {
        // Kiosk read actions for maps and status views.
        Route::get('/kiosks', [KioskController::class, 'index']);
        Route::get('/kiosks/id/{id}', [KioskController::class, 'show']);
        Route::get('/kiosks/{id}', [KioskController::class, 'show']);
    });

    Route::middleware('role:super_admin|kiosk_user|lgu_admin|lgu_staff|lgu_technician')->group(function () {
        // Charging Session Routes
        Route::post('/charging/redeem', [ChargingController::class, 'redeem']);
        Route::get('/charging/active', [ChargingController::class, 'getActive']);
        Route::post('/charging/cancel', [ChargingController::class, 'cancel']);
        Route::get('/charging/history', [ChargingController::class, 'history']);

        // Points Routes
        Route::get('/patron/points/balance', [ChargingController::class, 'getBalance']);
        Route::get('/patron/points/transactions', [ChargingController::class, 'transactions']);
        Route::post('/mobile/vouchers/claim', [PointVoucherController::class, 'claim']);
        Route::post('/mobile/vouchers/claim-signed', [PointVoucherController::class, 'claimSigned']); // Offline Code Claim
        Route::post('/patron/points/claim-signed', [PointVoucherController::class, 'claimSigned']); // Alias for Mobile App compatibility
        Route::post('/ports/activate', [PortActivationController::class, 'activate']); 
        // Recycling Routes
        Route::post('/patron/recycling/deposit', [ChargingController::class, 'depositRecyclables']);

        // Dashboard Stats & Social
        Route::get('/patron/dashboard/stats', [ChargingController::class, 'getDashboardStats']);

        Route::get('/patron/achievements', [ChargingController::class, 'getAchievements']);


    });

    // LGUs - Read access for all LGU roles, Write access for super_admin only
    Route::middleware('role:super_admin|lgu_admin|lgu_staff|lgu_technician')->group(function () {
        Route::get('/lgus', [\App\Http\Controllers\LguController::class, 'index']);
        Route::get('/lgus/{id}', [\App\Http\Controllers\LguController::class, 'show']);
    });

    Route::middleware('role:super_admin')->group(function () {
        Route::post('/lgus', [\App\Http\Controllers\LguController::class, 'store']);
        Route::put('/lgus/{id}', [\App\Http\Controllers\LguController::class, 'update']);
        Route::delete('/lgus/{id}', [\App\Http\Controllers\LguController::class, 'destroy']);
    });

    Route::middleware('role:super_admin|lgu_admin|lgu_technician')->group(function () {
        // LGU Field Reports read actions
        Route::get('/lgu/field-reports', [FieldReportController::class, 'index']);
        Route::get('/kiosk-field-reports', [FieldReportController::class, 'index']); // temporary alias

        // Maintenance tickets read actions
        Route::get('/lgu/tickets', [MaintenanceTicketController::class, 'index']);
    });

    Route::middleware('role:super_admin|lgu_admin|lgu_staff|lgu_technician')->group(function () {
        // KPI and missed-collection alerts (read-only)
        Route::get('/lgu/reports/kpi', [FieldReportInsightsController::class, 'kpi']);
        Route::get('/lgu/kiosks/alerts/missed-collections', [FieldReportInsightsController::class, 'missedCollections']);
    });

    Route::middleware('role:super_admin|lgu_admin')->group(function () {
        // LGU Field Reports review/admin actions
        Route::post('/lgu/field-reports/{id}/verify', [FieldReportController::class, 'verify']);
        Route::post('/lgu/field-reports/{id}/force-maintenance', [FieldReportController::class, 'forceMaintenance']);
        Route::post('/lgu/field-reports/{id}/ticket', [FieldReportController::class, 'createTicket']);

        // Maintenance tickets admin write actions
        Route::patch('/lgu/tickets/{id}', [MaintenanceTicketController::class, 'update']);

        // Audit trail (state-changing API activity)
        Route::get('/lgu/audit-trails', [AuditTrailController::class, 'index']);

        // Super admin may update any LGU settings; LGU admin may update own LGU.
        Route::put('/system-config', [LguSystemSettingController::class, 'upsert']);

        // Manual trigger for collection reminders.
        Route::post('/collection-schedules/{id}/notify', [CollectionScheduleController::class, 'notify']);

        // Admin reset password for a target user by ID.
        Route::post('/users/{id}/reset-password', [AuthController::class, 'adminResetPassword']);

        // Manual leaderboard season refresh/rollover (idempotent with season_id targeting).
        Route::post('/admin/leaderboards/refresh', [LeaderboardAdminController::class, 'refresh']);
    });

    Route::middleware('role:super_admin|lgu_admin|lgu_technician')->group(function () {
        // Technicians may close assigned tickets; admins may close any within scope.
        Route::post('/lgu/tickets/{id}/close', [MaintenanceTicketController::class, 'close']);
    });
});

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::post('/lgu/set-password', [PasswordSetupController::class, 'setPassword']);

Route::post('/lgu-users/register', [LguUserController::class, 'register']);
Route::post('/kiosk-users/register', [KioskUserController::class, 'register']);

Route::fallback(function () {
    return response()->json([
        'success' => false,
        'message' => 'Route not found: ' . request()->path(),
        'url' => request()->fullUrl(),
        'method' => request()->method()
    ], 404);
});
