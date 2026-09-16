<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Order;
use Illuminate\Http\Request;
use Stripe\Checkout\Session;
use Stripe\StripeClient;

class CheckoutController extends Controller
{
    /** Create a Stripe Checkout session for a paid course. */
    public function store(Request $request, Course $course)
    {
        abort_unless($course->status === 'published', 404);
        abort_if($course->is_free, 422, 'Este curso es gratuito, inscríbete directamente.');
        abort_if($course->isEnrolled($request->user()), 422, 'Ya estás inscrito en este curso.');

        $stripe = new StripeClient(config('services.stripe.secret'));
        $frontendUrl = rtrim(config('services.frontend.url'), '/');

        $order = Order::create([
            'user_id' => $request->user()->id,
            'course_id' => $course->id,
            'amount_cents' => $course->price_cents,
            'currency' => config('services.stripe.currency', 'eur'),
            'payment_method' => 'stripe',
            'status' => 'pending',
        ]);

        $session = $stripe->checkout->sessions->create([
            'mode' => 'payment',
            'customer_email' => $request->user()->email,
            'line_items' => [[
                'price_data' => [
                    'currency' => $order->currency,
                    'product_data' => [
                        'name' => $course->title,
                        'description' => $course->subtitle,
                    ],
                    'unit_amount' => $order->amount_cents,
                ],
                'quantity' => 1,
            ]],
            'metadata' => [
                'order_id' => $order->id,
                'course_id' => $course->id,
                'user_id' => $request->user()->id,
            ],
            'success_url' => "{$frontendUrl}/courses/{$course->slug}?checkout=success",
            'cancel_url' => "{$frontendUrl}/courses/{$course->slug}?checkout=cancelled",
        ]);

        $order->update(['stripe_checkout_session_id' => $session->id]);

        return response()->json(['checkout_url' => $session->url]);
    }
}
