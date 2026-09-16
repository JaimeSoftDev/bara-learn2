<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Enrollment;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Stripe\Exception\SignatureVerificationException;
use Stripe\Webhook;
use Symfony\Component\HttpFoundation\Response;

class StripeWebhookController extends Controller
{
    public function handle(Request $request)
    {
        $secret = config('services.stripe.webhook_secret');

        try {
            $event = Webhook::constructEvent(
                $request->getContent(),
                $request->header('Stripe-Signature'),
                $secret,
            );
        } catch (SignatureVerificationException|\UnexpectedValueException $e) {
            Log::warning('Stripe webhook signature verification failed', ['error' => $e->getMessage()]);

            return response()->json(['message' => 'Invalid signature'], Response::HTTP_BAD_REQUEST);
        }

        if ($event->type === 'checkout.session.completed') {
            $session = $event->data->object;
            $orderId = $session->metadata->order_id ?? null;

            $order = $orderId ? Order::find($orderId) : null;

            if ($order && $order->status !== 'paid') {
                $order->update([
                    'status' => 'paid',
                    'stripe_payment_intent_id' => $session->payment_intent,
                    'paid_at' => now(),
                ]);

                Enrollment::firstOrCreate(
                    ['user_id' => $order->user_id, 'course_id' => $order->course_id],
                    [
                        'source' => 'stripe',
                        'price_paid_cents' => $order->amount_cents,
                        'enrolled_at' => now(),
                    ]
                );
            }
        }

        if ($event->type === 'checkout.session.expired') {
            $session = $event->data->object;
            $orderId = $session->metadata->order_id ?? null;

            if ($orderId) {
                Order::where('id', $orderId)->where('status', 'pending')->update(['status' => 'failed']);
            }
        }

        return response()->json(['received' => true]);
    }
}
