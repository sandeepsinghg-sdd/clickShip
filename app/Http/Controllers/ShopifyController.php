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
}
