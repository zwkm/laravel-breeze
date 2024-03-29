<?php

namespace App\Http\Controllers;

use App\Models\Plan;
use App\Models\Order;
use Illuminate\Http\Request;
use App\Models\StripeHistory;
use App\Http\Requests\PlanRequest;

class ElementController extends Controller
{
    public function plan()
    {
        $plans = Plan::all();
        return view('element-plan', compact('plans'));
    }

    public function payment(PlanRequest $request)
    {
        $plan = Plan::find($request->type);
        $data = StripeHistory::where('user_id', auth()->id())->where('plan_id', $plan->id)->first();
        if (!$data) {
            $stripe = new \Stripe\StripeClient(env('STRIPE_SECRET_KEY'));
            $token = hash("sha256", rand());
            $data = $stripe->paymentIntents->create([
                'amount' => $plan->price,
                'currency' => 'sgd',
                'automatic_payment_methods' => [
                    'enabled' => true,
                ],
            ]);
            StripeHistory::create([
                'user_id' => auth()->id(),
                'plan_id' => $plan->id,
                'client_secret' => $data->client_secret,
                'token' => $token,
                'plink' => $data->id,
            ]);
        }
        $payment = json_encode([
            'clientSecret' => $data->client_secret,
            'clientPublic' => env('STRIPE_PUBLIC_KEY'),
            'url' => route('element.verify', ['token' => $data->token ?? $token]),
        ]);
        return view('checkout', compact('payment'));
    }

    public function verify(Request $request)
    {
        $data = StripeHistory::where('user_id', auth()->id())
            ->where('token', $request->token)
            ->where('plink', $request->payment_intent)
            ->first();
        if (!$data) {
            session()->flash('element_fail', 'Fail Stripe Elements method!');
            return to_route('element.fail');
        }
        $payment = [
            'user_id' => $data->user_id,
            'plan_id' => $data->plan_id,
            'started_at' => now()->startOfMonth(),
            'ended_at' => now()->endOfMonth(),
        ];
        Order::create($payment);
        StripeHistory::destroy($data->id);
        session()->flash('element_success', 'Success with Stripe Elements method!');
        return to_route('element.success');
    }

    public function success()
    {
        if ($elementSuccess = session()->get('element_success')) {
            return view('message', ['message' => $elementSuccess]);
        }
        return to_route('dashboard');
    }

    public function fail()
    {
        if ($elementFail = session()->get('element_fail')) {
            return view('message', ['message' => $elementFail]);
        }
        return to_route('dashboard');
    }
}
