<?php

namespace App\Jobs;

use App\Models\AdminNotification;
use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * IMP-048: Shipping Simulator [DEMO]
 *
 * State machine (each state is one dispatch of this job):
 *   payment_confirmed  → preparing      (delay: 5–10 s)
 *   preparing          → picked_up      (delay: 5–15 s)
 *   picked_up          → in_transit     (delay: 10–20 s)
 *   in_transit         → arrived        (delay: 5–10 s)
 *   arrived            → delivered (90%) or incident (10%)
 *
 * Notifications (toast only — no email):
 *   payment_confirmed  → Admin + User
 *   arrived            → User only
 *   incident           → Admin + User
 */
class ShipmentSimulatorJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /** State transitions: current → next */
    private const TRANSITIONS = [
        'payment_confirmed' => 'preparing',
        'preparing'         => 'picked_up',
        'picked_up'         => 'in_transit',
        'in_transit'        => 'arrived',
    ];

    /** Delay range (seconds) for each incoming state */
    private const DELAYS = [
        'payment_confirmed' => [5, 10],
        'preparing'         => [5, 15],
        'picked_up'         => [10, 20],
        'in_transit'        => [5, 10],
    ];

    public function __construct(
        public readonly int $orderId,
        public readonly string $currentState,
    ) {}

    public function handle(): void
    {
        $order = Order::find($this->orderId);

        // Guard: only process live demo orders
        if (! $order || ! $order->is_demo) {
            return;
        }

        // Guard: do not advance if the order is already terminal
        if (in_array($order->ship_sim_status, ['delivered', 'incident'], true)) {
            return;
        }

        // ── 1. Notify for the CURRENT state (payment_confirmed, arrived only) ─
        $this->notify($order, $this->currentState);

        // ── 2. Determine next state ──────────────────────────────────────────
        $nextState = self::TRANSITIONS[$this->currentState] ?? null;

        if ($nextState === null) {
            // currentState is 'arrived' → 10 % chance of incident
            if ($this->currentState === 'arrived') {
                $terminal = (random_int(1, 10) === 1) ? 'incident' : 'delivered';
                $order->update([
                    'ship_sim_status'     => $terminal,
                    'ship_sim_updated_at' => now(),
                    'status'              => $terminal === 'delivered' ? 'delivered' : $order->status,
                ]);
                $this->notify($order, $terminal);
            }
            return;
        }

        $order->update([
            'ship_sim_status'     => $nextState,
            'ship_sim_updated_at' => now(),
        ]);

        // ── 3. Schedule the next hop ─────────────────────────────────────────
        [$min, $max] = self::DELAYS[$nextState] ?? [5, 10];
        $delaySecs = random_int($min, $max);
        self::dispatch($this->orderId, $nextState)
            ->delay(now()->addSeconds($delaySecs));
    }

    // ── Notification helper ──────────────────────────────────────────────────

    private function notify(Order $order, string $state): void
    {
        $label = '[DEMO] ';

        $adminMsg = null;
        $userMsg  = null;

        switch ($state) {
            case 'payment_confirmed':
                $adminMsg = $label . "Order #{$order->id}: Payment confirmed";
                $userMsg  = $label . "Your order #{$order->id} payment has been confirmed";
                break;
            case 'arrived':
                $userMsg = $label . "Your order #{$order->id} has arrived at the delivery point";
                break;
            case 'incident':
                $adminMsg = $label . "Order #{$order->id}: Incident — refund triggered";
                $userMsg  = $label . "Order #{$order->id}: An incident occurred, a refund will be processed";
                break;
            // 'preparing', 'picked_up', 'in_transit', 'delivered' — silent progress only
            default:
                return;
        }

        if ($adminMsg) {
            AdminNotification::create([
                'order_id' => $order->id,
                'message'  => $adminMsg,
            ]);
        }

        if ($userMsg && $order->user_id) {
            // Store user-facing notification in session via cache so the
            // order detail page Alpine poller can surface it as a toast.
            // Key: demo_notify_{userId}_{orderId}
            \Illuminate\Support\Facades\Cache::put(
                "demo_notify_{$order->user_id}_{$order->id}",
                $userMsg,
                now()->addMinutes(10)
            );
        }
    }
}
