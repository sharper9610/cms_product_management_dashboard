<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\pages\ActivityController;
use App\Http\Controllers\pages\ApiUserController;
use App\Http\Controllers\pages\HomeController;
use App\Http\Controllers\pages\ProductImportController;
use App\Http\Controllers\pages\R2JsonUploadController;
use App\Http\Controllers\pages\OrderController;
use App\Http\Controllers\pages\PermissionController;
use App\Http\Controllers\pages\ProductController;
use App\Http\Controllers\pages\RoleController;
use App\Http\Controllers\pages\CustomerController;
use App\Http\Controllers\pages\TwoFactorController;
use App\Http\Controllers\pages\settings\DlcProductIDPromptController;
use App\Http\Controllers\pages\settings\DrmTypeController;
use App\Http\Controllers\pages\settings\ProductPublisherController;
use App\Http\Controllers\pages\settings\PromptController;
use App\Http\Controllers\pages\settings\SupportedLanguagePromptController;
use App\Http\Controllers\pages\UserController;
use App\Http\Controllers\pages\NoticeController;
use App\Http\Controllers\PromptController as ApiPromptController;
use App\Http\Controllers\authentications\TwoFactorChallengeController;
use App\Http\Controllers\PsStoreController;
use Illuminate\Support\Facades\Route;

if (env('ENABLE_WEB_ROUTES', true)) {
    Route::group(['middleware' => 'guest'], function () {
        // login route
        Route::get('/login', [AuthController::class, 'login'])->name('login');
        Route::post('/login', [AuthController::class, 'loginPost'])->name('login');

        Route::get('/2fa/challenge', [TwoFactorChallengeController::class, 'show'])->name('2fa.challenge');
        Route::post('/2fa/challenge', [TwoFactorChallengeController::class, 'verify'])->name('2fa.verify');
    });


    Route::group(['middleware' => 'auth'], function () {
        // 2FA setup routes
        Route::get('/2fa/setup',   [TwoFactorController::class, 'setup'])->name('2fa.setup');
        Route::post('/2fa/enable', [TwoFactorController::class, 'enable'])->name('2fa.enable');
        Route::post('/2fa/disable',[TwoFactorController::class, 'disable'])->name('2fa.disable');

        // logout route
        Route::delete('/logout', [AuthController::class, 'logout'])->name('logout');
    });

    Route::group(['middleware' => ['auth', 'enforce2fa']], function () {

        Route::get('users', [UserController::class, 'list'])->name('users');
        Route::resource('/user-list', UserController::class);

        Route::get('notices', [NoticeController::class, 'list'])->name('notices');
        Route::resource('/notice-list', NoticeController::class);
        // access roles page
        Route::get('roles', [RoleController::class, 'list'])->name('roles');
        Route::resource('access-roles', RoleController::class);

        Route::get('user-activity', [ActivityController::class, 'list'])->name('user-activity');
        Route::resource('/activity-list', ActivityController::class);

        Route::get('products', [ProductController::class, 'list'])->name('products');
        Route::resource('/product-list', ProductController::class);
        Route::get('product/{sku}/edit', [ProductController::class, 'productEdit'])
            ->name('product.edit');

        Route::get('/product-management/{id}/edit-ajax', [ProductController::class, 'editAjax']);

        Route::put('product-management/{id}', [ProductController::class, 'productUpdate'])
            ->name('product-management.update');
        Route::post('/product-management/translate', [ProductController::class, 'translateAllLang'])
            ->name('product.translate');

        Route::get('api-users', [ApiUserController::class, 'list'])->name('api-users');
        Route::resource('/api-user-list', ApiUserController::class);

        Route::delete('/localizations/{id}', [ProductController::class, 'localizationDestroy'])
            ->name('localizations.destroy');

        Route::get('orders', [OrderController::class, 'list'])->name('orders');
        Route::resource('/order-list', OrderController::class);
        Route::get('/orders/redeem-key/{item_id}', [OrderController::class, 'redeemKey']);


        Route::post('/translate', [ProductController::class, 'translate'])->name('translate');

        // home page route
        Route::get('/', [HomeController::class, 'index'])->name('pages-home');
        Route::get('/home-product-list', [HomeController::class, 'productList'])->name('home-product-list');

        Route::get('/countries-with-products', [HomeController::class, 'countryWiseProductCount'])->name('countries-with-products');
        Route::get('/country-with-products/{code}', [HomeController::class, 'countryWiseProduct'])
            ->name('country-with-products');

        Route::post('/import-products', [ProductImportController::class, 'importProducts'])
        ->name('import.products');

      Route::post('/import-products', [ProductImportController::class, 'importProducts'])
        ->name('import.products');

      Route::get('/product/import', [ProductImportController::class, 'index'])
        ->name('product.import');

      Route::get('/product/r2-json-upload', [R2JsonUploadController::class, 'index'])
        ->name('product.r2-json-upload');
      Route::post('/product/r2-json-upload', [R2JsonUploadController::class, 'upload'])
        ->name('product.r2-json-upload.submit');
      Route::post('/product/r2-json-upload/sync-parent-sku', [R2JsonUploadController::class, 'syncParentSku'])
        ->name('product.r2-json-upload.sync-parent-sku');

        Route::get('customers', [CustomerController::class, 'list'])->name('customers');
        Route::resource('customer-list', CustomerController::class);

        // Settings routes with prefix
        Route::prefix('settings')->group(function () {

            Route::get('drm-type', [DrmTypeController::class, 'list'])->name('settings-drm-type');
            Route::resource('drm-type-management', DrmTypeController::class);

            Route::get('prompt', [PromptController::class, 'list'])->name('settings-prompt');
            Route::resource('prompt-management', PromptController::class);

            Route::get('supported-lang-prompt', [SupportedLanguagePromptController::class, 'list'])
                ->name('settings-supported-lang-prompt');
            Route::resource('supported-lang-prompt-management', SupportedLanguagePromptController::class);

            Route::get('dlc-product-id-prompt', [DlcProductIDPromptController::class, 'list'])
                ->name('settings-dlc-product-id-prompt');
            Route::resource('dlc-product-id-prompt-management', DlcProductIDPromptController::class);

            Route::get('publishers', [ProductPublisherController::class, 'list'])
                ->name('settings-publishers');
            Route::resource('publisher-list', ProductPublisherController::class);

        });

        Route::get('permissions', [PermissionController::class, 'permissionManagement'])->name('permissions');
        Route::resource('/permission-list', PermissionController::class);
    });

    Route::prefix('prompts')->group(function () {
        Route::get('/', [ApiPromptController::class, 'index']);
        Route::get('{id}', [ApiPromptController::class, 'show']);
        Route::post('/', [ApiPromptController::class, 'store']);
        Route::put('{id}', [ApiPromptController::class, 'update']);
        Route::delete('{id}', [ApiPromptController::class, 'destroy']);
        Route::patch('{id}/deactivate', [ApiPromptController::class, 'deactivate']);
    });
}


Route::get('/ps-scrape', [PsStoreController::class, 'scrape']);
