<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Inertia\Inertia;

class paymentController extends Controller
{


    // Pages
    public function paymentListPage()
    {
        $paymentList = $this->get();
        return Inertia::render('Client/Payment/PaymentList', [
            'paymentList' => $paymentList
        ]);
    }

    public function paymentPage($id)
    {
        $paymentData = $this->get($id);

        return Inertia::render('Client/Payment/PaymentPage', [
            'paymentData' => $paymentData
        ]);
    }

    public function get($id_payment = null)
    {
        if (!$id_payment) {
            return Payment::where('id_user', auth()->id())->get();
        }

        return Payment::findOrFail($id_payment);
    }

    public function create(Request $request)
    {
        $request->validate([
            'total' => 'required|integer'
        ]);

        $user = User::findOrFail(auth()->id());

        $token = $this->midtrans($request->total);

        $payment = Payment::create([
            'id_user' => auth()->id(),
            'token' => $token,
            'customer_details' => [
                'customer_name' => $user->name,
                'customer_email' => $user->email,
                'customer_address' => $request->customer_details['address'],
            ],
            'total' =>  $request->total
        ]);

        $orderController = new orderController();

        $orderID = $orderController->create($payment->id_payment, $request->products);

        return redirect()->route('order-page', ['id_order' => $orderID]);
    }

    public function update($id_payment)
    {
        $paymentData = Payment::where('id_payment', $id_payment)->first();

        $paymentData->update([
            'token' => ''
        ]);
    }

    public function midtrans($gross_amount)
    {
        $authString = base64_encode(env("MIDTRANS_SERVER_KEY"));
        $ID = uniqid();

        $response = Http::withHeaders([
            'Authorization' => "Basic $authString"
        ])->post('https://app.sandbox.midtrans.com/snap/v1/transactions', [
            "transaction_details" => [
                "order_id" => "ORDER-$ID",
                "gross_amount" => $gross_amount
            ],
            "enabled_payments" =>
            [
                "credit_card", "cimb_clicks",
                "bca_klikbca", "bca_klikpay", "bri_epay", "echannel", "permata_va",
                "bca_va", "bni_va", "bri_va", "cimb_va",
            ]
        ]);

        return $response['token'];
    }
}
