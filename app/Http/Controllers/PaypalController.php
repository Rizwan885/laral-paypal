<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use PHPUnit\Event\TestData\NoDataSetFromDataProviderException;
use PHPUnit\Util\PHP\WindowsPhpProcess;
use Srmklive\PayPal\Services\PayPal as PayPalClient;

class PaypalController extends Controller
{
    public function payment(Request $request)
    {
        $provider = new PayPalClient;
        $provider->setApiCredentials(config('paypal'));
        $paypalToken = $provider->getAccessToken();

        $response = $provider->createOrder([
            "intent" => "CAPTURE",
            "application_context" => [
                "return_url" => route('paypal_success'),
                "cancel_url" => route('paypal_cancel')
            ],
            "purchase_units" => [
                [
                    "amount" => [
                        "currency_code" => "USD",
                        "value" => $request->price
                    ]
                ]
            ]
        ]);

        if (isset($response['id']) && $response['id'] != null) {
            foreach ($response['links'] as $link) {
                if ($link['rel'] === 'approve') {
                    return redirect()->away($link['href']);
                }
            }
        }

        // If 'approve' link is not found, handle cancellation
        return redirect()->route('paypal_cancel');
    }


    public function success(Request $request)
    {
        $provider = new PayPalClient;
        $provider->setApiCredentials(config('paypal'));
        $paypalToken = $provider->getAccessToken();

        // Get the order ID dynamically from the successful payment response or your database
        $orderId = $request->input('token'); // Update this line based on the actual parameter name

        $response = $provider->capturePaymentOrder($orderId);

        // dd($response);

        if (isset($response['status']) && $response['status'] == 'COMPLETED') {
            return "Payment Done Successfully!";
        } else {
            return redirect()->route("paypal_cancel");
        }
    }
    public function cancel()
    {
        // Handle the cancellation logic here, if needed
        return view('paypal_cancel'); // You can create a specific view for cancellation
    }
}
