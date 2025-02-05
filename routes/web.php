<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ShopifyController;
use App\Http\Controllers\ShippingController;

use Illuminate\Support\Facades\Http;
use Illuminate\Http\Request;

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

Route::get('/', function () {
    return view('welcome');
});




Route::get('/install', [ShopifyController::class, 'install']);
Route::get('/auth/callback', [ShopifyController::class, 'callback']);



// Route for displaying shipping settings form
Route::get('/shopify/shipping/settings', [ShippingController::class, 'showSettingsForm'])->name('shipping.settings');

// Route for saving shipping settings
Route::post('/shopify/shipping/settings', [ShippingController::class, 'saveSettings'])->name('shipping.save-settings');

Route::get('/shopify/shipping/table', [ShippingController::class, 'showSettingsTable'])->name('shipping.showSettingsTable');


Route::post('/shopify/shipping-rate', [ShippingController::class, 'getShippingRate']);


Route::post('/shopify/all-shipping-rate',[ShippingController::class, 'allShippingRate']);


Route::post('/create-draft-order', [ShopifyController::class,'createOrder']);




Route::get('/shipping/inject-script', [ShopifyController::class, 'injectScript'])->name('shipping.inject-script');;

Route::get('/shipping/remove-script', function () {
    $shop = 'your-shop-name.myshopify.com'; // Get the shop name dynamically
    $accessToken = 'your-shopify-access-token'; // Get the access token dynamically

    // Fetch the script tags
    $response = Http::withToken($accessToken)->get("https://{$shop}/admin/api/2024-01/script_tags.json");

    $scriptTags = $response->json()['script_tags'] ?? [];

    foreach ($scriptTags as $scriptTag) {
        // Remove script tag with matching src
        if ($scriptTag['src'] === 'https://your-laravel-app.com/js/your-script.js') {
            $scriptTagId = $scriptTag['id'];
            Http::withToken($accessToken)->delete("https://{$shop}/admin/api/2024-01/script_tags/{$scriptTagId}.json");
        }
    }

    return response()->json(['success' => true]);
})->name('shipping.remove-script');

