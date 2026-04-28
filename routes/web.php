<?php

use App\Http\Controllers\Admin\ApiKeyController;
use App\Http\Controllers\Admin\CompanyController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\ClientMapController;
use App\Http\Controllers\MapController;
use App\Http\Controllers\PricingController;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Route;

Route::get('/waresix', function () {
    return view('welcome');
});
Route::get('/transjakarta', function () {
    return view('vendor.tj');
});
Route::get('/transjakarta-test', function () {
    return view('vendor.tj-test');
});
Route::get('/otto', function () {
    return view('otto');
});

Route::get('/doc-web', function () {
    return view('documents.web');
});
Route::get('/docs/aws-api', function () {
    return view('documents.aws-api');
})->name('docs.aws-api');
Route::get('/maps/google', function () {
    return view('documents.google-maps');
})->name('maps.google');

Route::get('/address', function () {
    return view('old.address');
})->name('pageAddress');

Route::get('/scrap-test', function () {
    return view('scraping/scrap-test');
})->name('pageScrapTest');
Route::get('/scrap', function () {
    return view('scraping/index');
})->name('pageScrap');

Route::get('/tests', function () {
    return view('scraping/test');
})->name('pageScrap');

Route::get('/map', [MapController::class, 'showMap']);
Route::get('/api/map-style', [MapController::class, 'getMapStyle']);
Route::get('/api/map-style-simple', [MapController::class, 'getMapStyleSimple']);
Route::get('/api/map-style-clean', [MapController::class, 'getMapStyleClean']);

// Proxy endpoints — keeps API keys server-side
Route::post('/api/places/suggestions', [MapController::class, 'searchSuggestions']);
Route::post('/api/places/search', [MapController::class, 'searchText']);
Route::get('/api/places/{placeId}', [MapController::class, 'getPlace']);
Route::post('/api/places/reverse', [MapController::class, 'reverseGeocode']);
Route::post('/api/routes/calculate', [MapController::class, 'calculateRoute']);
Route::post('/api/routes/matrix', [MapController::class, 'calculateRouteMatrix']);
Route::post('/api/v2/routes/calculate', [MapController::class, 'calculateRouteV2']);

//route API
Route::get('/map-style', function () {
    $region = env('AWS_REGION'); // Ambil dari .env
    $mapName = env('AWS_MAP_NAME'); // Ambil dari .env
    $apiKey = env('AWS_API_KEY'); // Ambil API key dari .env

    // Buat URL untuk style map
    $url = "https://maps.geo.{$region}.amazonaws.com/maps/v0/maps/{$mapName}/style-descriptor?key={$apiKey}";

    // Ambil data dari API
    $response = Http::get($url);

    // Kembalikan data JSON ke frontend
    return response()->json($response->json());
});

Route::get('/map-config', function () {
    return response()->json([
        'region' => env('AWS_REGION'),
        'apiKey' => env('AWS_API_KEY'),
        'mapName' => env('AWS_MAP_NAME')
    ]);
});

// Pricing Comparison
Route::get('/pricing', [PricingController::class, 'index'])->name('pricing');
Route::post('/api/pricing/calculate', [PricingController::class, 'calculate']);

// Pricing Admin
Route::get('/pricing/admin', [PricingController::class, 'adminIndex'])->name('pricing.admin');
Route::post('/pricing/admin/items', [PricingController::class, 'store'])->name('pricing.store');
Route::put('/pricing/admin/items/{item}', [PricingController::class, 'update'])->name('pricing.update');
Route::delete('/pricing/admin/items/{item}', [PricingController::class, 'destroy'])->name('pricing.destroy');
Route::get('/', function () {
    return view('home.index');
})->name('pageHome');
Route::get('/tester-api', function () {
    return view('testing.index');
})->name('pageRouteTester');

// === Auth Routes ===
Route::middleware('guest')->group(function () {
    Route::get('/login', [\App\Http\Controllers\Auth\AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [\App\Http\Controllers\Auth\AuthController::class, 'login']);
    Route::get('/register', [\App\Http\Controllers\Auth\AuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [\App\Http\Controllers\Auth\AuthController::class, 'register']);
});
Route::post('/logout', [\App\Http\Controllers\Auth\AuthController::class, 'logout'])
    ->middleware('auth')->name('logout');

// === Email Verification Routes ===
Route::middleware('auth')->group(function () {
    Route::get('/email/verify', [\App\Http\Controllers\Auth\AuthController::class, 'showVerifyNotice'])
        ->name('verification.notice');
    Route::get('/email/verify/{id}/{hash}', [\App\Http\Controllers\Auth\AuthController::class, 'verifyEmail'])
        ->middleware(['signed', 'throttle:6,1'])
        ->name('verification.verify');
    Route::post('/email/verification-notification', [\App\Http\Controllers\Auth\AuthController::class, 'resendVerification'])
        ->middleware('throttle:6,1')
        ->name('verification.send');
});

// Admin Language Switcher (no middleware needed — just sets session)
Route::get('/admin/language/{lang}', function (string $lang) {
    if (in_array($lang, ['en', 'id'])) {
        session(['admin_locale' => $lang]);
    }
    return back();
})->name('admin.language');

// All admin routes — auth + verified email + locale middleware
Route::middleware(['auth', 'verified', 'admin.locale'])->group(function () {
    // Dashboard
    Route::get('/admin', [DashboardController::class, 'index'])->name('admin.dashboard');

    // API Key Management
    Route::prefix('admin/api-keys')->group(function () {
        Route::get('/', [ApiKeyController::class, 'index'])->name('admin.api-keys.index');
        Route::post('/assign', [ApiKeyController::class, 'assign'])->name('admin.api-keys.assign');
        Route::post('/unassign', [ApiKeyController::class, 'unassign'])->name('admin.api-keys.unassign');
        Route::get('/{keyName}/edit', [ApiKeyController::class, 'edit'])->name('admin.api-keys.edit');
        Route::put('/{keyName}', [ApiKeyController::class, 'update'])->name('admin.api-keys.update');
        Route::get('/{keyName}/usage', [ApiKeyController::class, 'usage'])->name('admin.api-keys.usage');
    });

    // User Management (admin only)
    Route::middleware('admin.only')->prefix('admin/users')->group(function () {
        Route::get('/', [\App\Http\Controllers\Admin\UserController::class, 'index'])->name('admin.users.index');
        Route::post('/', [\App\Http\Controllers\Admin\UserController::class, 'store'])->name('admin.users.store');
        Route::get('/{user}/edit', [\App\Http\Controllers\Admin\UserController::class, 'edit'])->name('admin.users.edit');
        Route::put('/{user}', [\App\Http\Controllers\Admin\UserController::class, 'update'])->name('admin.users.update');
        Route::patch('/{user}/toggle-status', [\App\Http\Controllers\Admin\UserController::class, 'toggleStatus'])->name('admin.users.toggle-status');
        Route::post('/{user}/resend-verification', [\App\Http\Controllers\Admin\UserController::class, 'resendVerification'])->name('admin.users.resend-verification');
        Route::post('/{user}/reset-send', [\App\Http\Controllers\Admin\UserController::class, 'resetAndSend'])->name('admin.users.reset-send');
        Route::post('/{user}/send-credentials', [\App\Http\Controllers\Admin\UserController::class, 'sendCredentials'])->name('admin.users.send-credentials');
        Route::delete('/{user}', [\App\Http\Controllers\Admin\UserController::class, 'destroy'])->name('admin.users.destroy');
    });

    // Company Management
    Route::prefix('admin/companies')->group(function () {
        Route::get('/', [CompanyController::class, 'index'])->name('admin.companies.index');
        Route::get('/create', [CompanyController::class, 'create'])->name('admin.companies.create');
        Route::post('/', [CompanyController::class, 'store'])->name('admin.companies.store');
        Route::get('/{company}/edit', [CompanyController::class, 'edit'])->name('admin.companies.edit');
        Route::put('/{company}', [CompanyController::class, 'update'])->name('admin.companies.update');
        Route::patch('/{company}/toggle-status', [CompanyController::class, 'toggleStatus'])->name('admin.companies.toggle-status');
        Route::get('/{company}/usage', [CompanyController::class, 'usage'])->name('admin.companies.usage');
    });
});

// Dynamic company map — must be last (catch-all)
Route::get('/{slug}', [ClientMapController::class, 'show'])->name('company.map');
