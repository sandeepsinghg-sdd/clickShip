<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Redirect;
use App\Models\ShopifyApp;
use DB;
use Illuminate\Support\Facades\Http;


class ShopifyController extends Controller
{
    public function install(Request $request)

    {
      
      // Step 1: Redirect user to Shopify OAuth authorization
        $shop = $request->get('shop');
        $shopExists = DB::table('shopify_apps')->where('shop', $shop)->exists();

        if ($shopExists) {
            // Shop is already installed, redirect to home
            return redirect('/shopify/shipping/settings');
        } 
        $apiKey = env('SHOPIFY_API_KEY');
        $redirectUri = env('SHOPIFY_REDIRECT_URL'); // https://your-app-url/auth/callback
        $scopes = 'read_products,write_orders,write_draft_orders,read_shipping,write_shipping,write_checkouts,read_customers'; // Correct scope for draft orders

        $installUrl = "https://{$shop}/admin/oauth/authorize?client_id={$apiKey}&scope={$scopes}&redirect_uri={$redirectUri}&state=" . Str::random(20);

        return redirect($installUrl);

    }

    public function callback(Request $request)
    {
        // Validate the request using HMAC to ensure it comes from Shopify
        $hmac = $request->get('hmac');
        $shop = $request->get('shop');
        $code = $request->get('code');
        $timestamp = $request->get('timestamp');
    
        // Remove HMAC from the parameters for validation
        $params = $request->except('hmac'); 
        $calculatedHmac = hash_hmac('sha256', http_build_query($params), env('SHOPIFY_API_SECRET'));
    
        // Check if HMAC matches
        if ($calculatedHmac !== $hmac) {
            return response()->json(['error' => 'HMAC validation failed'], 400);
        }
    
        // Request access token from Shopify
        $accessTokenRequest = $this->getAccessToken($shop, $code);
        
      
        // Check if the access token is successfully received
        if (!$accessTokenRequest) {
            return response()->json(['error' => 'Failed to get access token'], 400);
        }
    
        // Store the access token in the database for later use
        ShopifyApp::updateOrCreate(
            ['shop' => $shop], 
            ['access_token' => $accessTokenRequest]
        );
    
        // Redirect the user to the Shopify Admin Apps page
        $adminUrl = "https://{$shop}/admin/apps"; // Correct URL to redirect to after installation
        return redirect($adminUrl);  // Redirect to Shopify Admin Apps page
    }
    
    
    

    private function getAccessToken($shop, $code)
    {
        $apiKey = env('SHOPIFY_API_KEY');
        $apiSecret = env('SHOPIFY_API_SECRET');
        $redirectUri = env('SHOPIFY_REDIRECT_URL'); // e.g. https://your-app-url/auth/callback

        $url = "https://{$shop}/admin/oauth/access_token";
        $response = \Http::post($url, [
            'client_id' => $apiKey,
            'client_secret' => $apiSecret,
            'code' => $code,
        ]);

        if ($response->successful()) {
            return $response->json()['access_token'];
        }

        return null;
    }

    public function injectScript(Request $request)
    {
        // Retrieve your Shopify app's API key, password, and store name from your Laravel app
        $shopExists = DB::table('shopify_apps')->first();
    
        if (!empty($shopExists->shop)) {
            $shop = $shopExists->shop; // Get the shop name dynamically
            $apiKey ='951cfa173c9f8061d8d1e67741a10821'; // Get the API key dynamically (stored in your db)
            $password = '916d92dd348a0262408c0b882bc26a7c'; // Get the API password dynamically (stored in your db)
            
            // Your script URL to inject
            $scriptUrl = 'https://your-laravel-app.com/js/your-script.js'; // URL to your hosted script
    
            // Prepare the Shopify API endpoint URL for Script Tag creation
            $apiUrl = "https://{$shop}/admin/api/2024-01/script_tags.json";
    
            // Make the request to Shopify's ScriptTag API to inject the script using Basic Auth
            $response = Http::withBasicAuth($apiKey, $password)->post($apiUrl, [
                'script_tag' => [
                    'event' => 'onload',
                    'src' => $scriptUrl,
                ]
            ]);
    
            $data = $response->json();
    
            // Debugging to print the API response
            echo "<pre>";
            print_r($data);
            die();
    
            // Return the success response
            return response()->json(['success' => $data['script_tag'] ?? false]);
        } else {
            return response()->json(['error' => 'Shop not found or token is missing']);
        }
    }
    
    
   public function removeScript(Request $request){
    
   }


   public function createOrder(Request $request){
   

   
    $shopData = DB::table('shopify_apps')->first();
    $shop = $shopData->shop;
    $accessToken = $shopData->access_token;
    
    // Prepare order data
     // ✅ Validate incoming request
     $validated = $request->validate([
        'customer_info.first_name' => 'required|string',
        'customer_info.last_name' => 'required|string',
        'customer_info.address' => 'required|string',
        'customer_info.apartment' => 'nullable|string',
        'customer_info.city' => 'required|string',
        'customer_info.postal_code' => 'required|string',
        'customer_info.country' => 'required|string',
        'customer_info.region' => 'required|string',
        'shipping_method' => 'required|string',
        'shipping_cost' => 'required|numeric',
        'products' => 'required|array|min:1',
        'products.*.variant_id' => 'required|integer',
        'products.*.quantity' => 'required|integer|min:1',
    ]);

    // ✅ Extract customer info
    $customerInfo = $validated['customer_info'];

    // ✅ Extract shipping details
    $shippingMethod = $validated['shipping_method'];
    $shippingCost = $validated['shipping_cost'];

    // ✅ Extract and format product line items dynamically
    $lineItems = array_map(function ($item) {
        return [
            "variant_id" => $item['variant_id'],
            "quantity" => $item['quantity'],
        ];
    }, $validated['products']);

    // ✅ Prepare Shopify order data
    $orderData = [
        "order" => [
            "email" => "customer@example.com", // Replace with actual email if available
            "line_items" => $lineItems,
            "shipping_address" => [
                "first_name" => $customerInfo['first_name'],
                "last_name" => $customerInfo['last_name'],
                "address1" => $customerInfo['address'],
                "address2" => $customerInfo['apartment'] ?? "", // Optional
                "city" => $customerInfo['city'],
                "province" => $customerInfo['region'],
                "zip" => $customerInfo['postal_code'],
                "country" => $customerInfo['country'],
            ],
            "shipping_lines" => [
                [
                    "title" => $shippingMethod,
                    "price" => $shippingCost,
                    "code" => strtoupper(str_replace(' ', '_', $shippingMethod)), // Convert method to a valid code
                ]
            ],
            "financial_status" => "paid", // Shopify requires this to mark as paid
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
            ])->header('Access-Control-Allow-Origin', '*') // Allow all origins
            ->header('Access-Control-Allow-Methods', 'GET, POST, OPTIONS, DELETE, PUT')
            ->header('Access-Control-Allow-Headers', 'Origin, Content-Type, Accept, Authorization');

            // return response()->json($data, 200)
            // ->header('Access-Control-Allow-Origin', '*') // Allow all origins
            // ->header('Access-Control-Allow-Methods', 'GET, POST, OPTIONS, DELETE, PUT')
            // ->header('Access-Control-Allow-Headers', 'Origin, Content-Type, Accept, Authorization');
        }
    }

    return response()->json(["error" => "Order creation failed"], 400) ->header('Access-Control-Allow-Origin', '*') // Allow all origins
    ->header('Access-Control-Allow-Methods', 'GET, POST, OPTIONS, DELETE, PUT')
    ->header('Access-Control-Allow-Headers', 'Origin, Content-Type, Accept, Authorization');;
   }
}
