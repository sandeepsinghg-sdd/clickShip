<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ShippingSetting; // Assuming you have a model for shipping settings

class ShippingController extends Controller
{
    // Show the shipping settings form
    public function showSettingsForm()
    {

        // Retrieve existing shipping settings if any
        $shippingSettings = ShippingSetting::first();

        return view('shopify.shipping-setting', compact('shippingSettings'));
    }

    // Save the shipping settings
    public function saveSettings(Request $request)
    {

        
        // Validate the form inputs
        $request->validate([
            'shipping_name' => 'required|string|max:255',
            'shipping_price' => 'required|numeric',
        ]);

        // Check if the shipping settings already exist, if so, update them, else create a new one
        $shippingSettings = ShippingSetting::first();

        if ($shippingSettings) {
            // Update existing settings
            $shippingSettings->shipping_name = $request->shipping_name;
            $shippingSettings->shipping_price = $request->shipping_price;
            $shippingSettings->save();
        } else {
            // Create new settings
            ShippingSetting::create([
                'shipping_name' => $request->shipping_name,
                'shipping_price' => $request->shipping_price,
            ]);
        }

        return response()->json(['status'=>true,'message'=>'Shipping settings saved successfully']);

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
}
