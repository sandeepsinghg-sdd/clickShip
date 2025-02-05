<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ShippingSetting; // Assuming you have a model for shipping settings
use Yajra\DataTables\Facades\DataTables;
use App\Models\ShopifyApp;
use DB;
use Illuminate\Support\Facades\Http;

class ShippingController extends Controller
{
    // Show the shipping settings form
    public function showSettingsForm(Request $request)
    {

        // Retrieve existing shipping settings if any
        $shippingSettings = ShippingSetting::first();
        

        return view('shopify.shipping-setting', compact('shippingSettings'));
    }

    // Save the shipping settings
    public function saveSettings(Request $request)
    {
            // Validate the incoming request
            $validatedData = $request->validate([
                'shipping_name' => 'required|string|max:255',
                'shipping_price' => 'required|numeric|min:0',
            ]);

            // Shopify store credentials (Replace with actual values)
            $shopData = DB::table('shopify_apps')->first();
            $shop = $shopData->shop;
            $accessToken = $shopData->access_token;
           
            // Shipping method data from the form
            $shippingMethod = [
                'shipping_name' => $validatedData['shipping_name'],
                'shipping_price' => $validatedData['shipping_price'],
            ];

            // Create or update the Metafield for the shipping method
            $response = Http::withHeaders([
                'X-Shopify-Access-Token' => $accessToken,
            ])->post("https://{$shop}/admin/api/2023-10/metafields.json", [
                'metafield' => [
                    'namespace'      => 'shipping_methods', // Define the namespace for shipping methods
                    'key'            => strtolower(str_replace(' ', '_', $shippingMethod['shipping_name'])), // Unique key for each shipping method
                    'value'          => json_encode($shippingMethod), // Store shipping data as JSON
                    'type'           => 'json', // ✅ Use 'json' instead of 'value_type'
                    'owner_resource' => 'shop', // ✅ Attach to the shop level
                ],
            ]);
            
       
            
            // Handle the response from Shopify
            if ($response->successful()) {
                // Optionally return a success message or redirect
                return response()->json(['status'=>true,'message'=>'Shipping settings saved successfully']);
            } else {
                // Handle the error if the request fails
                return response()->json(['status'=>true,'message'=>'Someting went wrong']);
            }
    
        // // Validate the form inputs
        // $request->validate([
        //     'shipping_name' => 'required|string|max:255',
        //     'shipping_price' => 'required|numeric',
        // ]);

        // // Check if the shipping settings already exist, if so, update them, else create a new one
        // $exists = ShippingSetting::where('shipping_name', $request->shipping_name)->exists();
     

        // if ($exists) {
        //     return response()->json(['status'=>false,'message'=>'Shipping method already exists']);
        // }
        
        // // Create new shipping setting
        // ShippingSetting::create([
        //     'shipping_name' => $request->shipping_name,
        //     'shipping_price' => $request->shipping_price,
        // ]);

        // return response()->json(['status'=>true,'message'=>'Shipping settings saved successfully']);

        // Redirect back with a success message
       // return redirect()->route('shipping.settings')->with('success', 'Shipping settings saved successfully.');
    }


    public function createCarrierService()
    {
        $shopDomain = 'your-shop.myshopify.com';  // Replace with your shop's domain
        $accessToken = 'your-shopify-access-token';  // Replace with your shop's access token

        $carrierService = [
            'carrier_service' => [
                'name' => 'Custom Shipping Method',
                'callback_url' => url('/shopify/shipping-rate'),  // Your endpoint to calculate the rate
                'service_discovery' => true,  // Enable the service to be discovered
            ]
        ];

        $response = Http::withHeaders([
            'X-Shopify-Access-Token' => $accessToken
        ])->post("https://$shopDomain/admin/api/2023-01/carrier_services.json", $carrierService);

        return $response->json();
    }

    public function allShippingRate(){
        $shopData = DB::table('shopify_apps')->first();
        $shop = $shopData->shop;
        $accessToken = $shopData->access_token;
    
        // Fetch shipping metafields from Shopify
        $response = Http::withHeaders([
            'X-Shopify-Access-Token' => $accessToken,
        ])->get("https://{$shop}/admin/api/2023-10/metafields.json", [
            'namespace' => 'shipping_methods',
        ]);
    
        if ($response->failed()) {
            return response()->json(['status' => false, 'message' => 'Failed to fetch shipping methods'], 400);
        }
    
        $metafields = json_decode($response->body(), true)['metafields'] ?? [];
    
        // Transform response to return only required fields
        $shippingMethods = array_map(function ($metafield) {
            $value = json_decode($metafield['value'], true);
            return [
                'id' => $metafield['id'],
                'shipping_name' => $value['shipping_name'],
                'shipping_price' => $value['shipping_price'],
            ];
        }, $metafields);
    
        return response()->json(['status' => true, 'data' => $shippingMethods]);
        // $shippingrates = ShippingSetting::get();
        // return response()->json(['status'=>true,'data'=>$shippingrates]);
    }

    public function showSettingsTable(Request $request){
       
            $shopData = DB::table('shopify_apps')->first();
            $shop = $shopData->shop;
            $accessToken = $shopData->access_token;

            // Send GET request to Shopify API to get all metafields under 'shipping_methods' namespace
            $response = Http::withHeaders([
                'X-Shopify-Access-Token' => $accessToken,
            ])->get("https://{$shop}/admin/api/2023-10/metafields.json", [
                'namespace' => 'shipping_methods',
            ]);

            if ($response->successful()) {
                $metafields = $response->json()['metafields'] ?? [];

                // Transform the data for Yajra DataTables
                $shippingMethods = array_map(function ($metafield) {
                    $shippingMethod = json_decode($metafield['value'], true);
            
                    return [
                        'id'             => $metafield['id'],
                        'shipping_name'  => $shippingMethod['shipping_name'],
                        'shipping_price' => $shippingMethod['shipping_price'],
                        'created_at'     => date('Y-m-d H:i:s', strtotime($metafield['created_at'])), // Format date
                    ];
                }, $metafields);
            
                return DataTables::of($shippingMethods)
                    ->addColumn('action', function ($row) {
                        return '<button class="btn btn-sm btn-success view-shipping" data-id="' . $row['id'] . '">View</button>';
                    })
                    ->rawColumns(['action'])
                    ->make(true);
            }
            //   die;
                 
            // $users = ShippingSetting::select(['id', 'shipping_name', 'shipping_price', 'created_at']);
            
            // return DataTables::of($users)
            //     ->addColumn('action', function ($row) {
            //         return '<a href="#" class="btn btn-primary btn-sm">View</a>';
            //     })
            //     ->rawColumns(['action'])
            //     ->make(true);
    
    }
}
