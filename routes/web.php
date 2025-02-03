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



Route::post('/shopify/shipping-rate', [ShippingController::class, 'getShippingRate']);






Route::post('/create-draft-order', function (Request $request) {

    $shopData = DB::table('shopify_apps')->first();
    $shop = $shopData->shop;
    $accessToken = $shopData->access_token;

    // Prepare order data
    $orderData = [
        "order" => [
            "email" => "customer@example.com",
            "line_items" => [
                [
                    "variant_id" => 49976155767131,
                    "quantity" => 1,
                ]
            ],
            "shipping_address" => [
                "first_name" => "John",
                "last_name" => "Doe",
                "address1" => "123 Main St",
                "city" => "Anytown",
                "province" => "State",
                "zip" => "12345",
                "country" => "US",
            ],
            "shipping_lines" => [
                [
                    "title" => "Express Shipping",
                    "price" => 9.99,
                    "code" => "CUSTOM_SHIPPING"
                ]
            ],
            "transactions" => [
                [
                    "kind" => "sale",
                    "status" => "success",
                    "amount" => 19.99,
                    "currency" => "USD"
                ]
            ]
        ]
    ];

    // Create order in Shopify
    $response = Http::withHeaders([
        "X-Shopify-Access-Token" => $accessToken,
    ])->post("https://$shop/admin/api/2024-01/orders.json", $orderData);

    $data = $response->json();

    if ($response->successful()) {
        $orderId = $data["order"]["id"];

        // ✅ Fetch the order details to get `order_status_url`
        $orderResponse = Http::withHeaders([
            "X-Shopify-Access-Token" => $accessToken,
        ])->get("https://$shop/admin/api/2024-01/orders/$orderId.json");

        $orderData = $orderResponse->json();

        if (isset($orderData["order"]["order_status_url"])) {
            $thankYouUrl = $orderData["order"]["order_status_url"]; // ✅ Correct thank you page URL

            return response()->json([
                'success' => true,
                'redirect_url' => $thankYouUrl
            ]);
        }
    }

    return response()->json(["error" => "Order creation failed"], 400);
});




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

