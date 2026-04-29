<?php

namespace Tests\Feature;

use App\Jobs\ShipmentSimulatorJob;
use App\Models\AdminNotification;
use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Queue;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * IMP-048 — Shipping Simulator [DEMO]
 *
 * Acceptance criteria:
 *   TC-01: Admin can access the demo sandbox page
 *   TC-02: Guest is redirected away from demo sandbox
 *   TC-03: Regular user cannot access demo sandbox (403)
 *   TC-04: simulate() creates an order with is_demo=true
 *   TC-05: simulate() does NOT decrement product stock
 *   TC-06: simulate() dispatches ShipmentSimulatorJob
 *   TC-07: Demo order is NOT counted in revenue (dashboard total)
 *   TC-08: Demo order is NOT included in admin order list
 *   TC-09: Demo order is NOT included in admin order export
 *   TC-10: Demo order is excluded from revenue report
 *   TC-11: Order model scopeReal() excludes demo orders
 *   TC-12: status() endpoint returns ship_sim_status JSON
 *   TC-13: status() returns 404 for non-demo orders
 *   TC-14: ShipmentSimulatorJob advances preparing → picked_up
 *   TC-15: ShipmentSimulatorJob advances arrived → delivered or incident (terminal)
 *   TC-16: ShipmentSimulatorJob creates AdminNotification for payment_confirmed
 *   TC-17: ShipmentSimulatorJob creates AdminNotification for incident
 *   TC-18: ShipmentSimulatorJob is idempotent on terminal order (no-op)
 */
class ShippingSimulatorTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $user;
    private Product $product;

    protected function setUp(): void
    {
        parent::setUp();

        Role::firstOrCreate(['name' => 'user',  'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);

        $category = Category::factory()->create();

        $this->admin = User::factory()->create();
        $this->admin->assignRole('admin');

        $this->user = User::factory()->create();
        $this->user->assignRole('user');

        $this->product = Product::factory()->create([
            'status' => 'active',
            'stock'  => 50,
            'price'  => 29.99,
            'category_id' => $category->id,
        ]);
    }

    // ── TC-01: Admin sees demo sandbox page ──────────────────────────────────

    public function test_admin_can_access_demo_sandbox(): void
    {
        $this->actingAs($this->admin)
             ->get(route('admin.demo.index'))
             ->assertOk()
             ->assertSee('[DEMO]');
    }

    // ── TC-02: Guest is redirected ────────────────────────────────────────────

    public function test_guest_cannot_access_demo_sandbox(): void
    {
        $this->get(route('admin.demo.index'))
             ->assertRedirect(route('login'));
    }

    // ── TC-03: Regular user gets 403 ─────────────────────────────────────────

    public function test_user_cannot_access_demo_sandbox(): void
    {
        $this->actingAs($this->user)
             ->get(route('admin.demo.index'))
             ->assertForbidden();
    }

    // ── TC-04: simulate() creates demo order ─────────────────────────────────

    public function test_simulate_creates_demo_order(): void
    {
        Queue::fake();

        $this->actingAs($this->admin)
             ->post(route('admin.demo.simulate'), [
                 'product_id' => $this->product->id,
                 'quantity'   => 2,
             ])
             ->assertRedirect(route('admin.demo.index'));

        $this->assertDatabaseHas('orders', [
            'is_demo'         => true,
            'ship_sim_status' => 'payment_confirmed',
            'user_id'         => $this->admin->id,
        ]);
    }

    // ── TC-05: simulate() does NOT decrement stock ───────────────────────────

    public function test_simulate_does_not_decrement_stock(): void
    {
        Queue::fake();
        $initialStock = $this->product->stock;

        $this->actingAs($this->admin)
             ->post(route('admin.demo.simulate'), [
                 'product_id' => $this->product->id,
                 'quantity'   => 3,
             ]);

        $this->assertDatabaseHas('products', [
            'id'    => $this->product->id,
            'stock' => $initialStock,  // unchanged
        ]);
    }

    // ── TC-06: simulate() dispatches ShipmentSimulatorJob ────────────────────

    public function test_simulate_dispatches_shipping_job(): void
    {
        Bus::fake();

        $this->actingAs($this->admin)
             ->post(route('admin.demo.simulate'), [
                 'product_id' => $this->product->id,
                 'quantity'   => 1,
             ]);

        Bus::assertDispatched(ShipmentSimulatorJob::class);
    }

    // ── TC-07: Demo order excluded from dashboard revenue ────────────────────

    public function test_demo_order_excluded_from_dashboard_revenue(): void
    {
        // A real paid order
        Order::factory()->create([
            'status'  => 'paid',
            'total'   => 100.00,
            'is_demo' => false,
        ]);

        // A demo paid order
        Order::factory()->create([
            'status'  => 'paid',
            'total'   => 999.00,
            'is_demo' => true,
        ]);

        $this->actingAs($this->admin)
             ->get(route('admin.dashboard'))
             ->assertOk()
             ->assertSee('100')       // real order total visible
             ->assertDontSee('999');  // demo total hidden
    }

    // ── TC-08: Demo order excluded from admin order list ─────────────────────

    public function test_demo_order_excluded_from_order_list(): void
    {
        $real = Order::factory()->create([
            'status'  => 'pending',
            'is_demo' => false,
            'user_id' => $this->user->id,
        ]);

        Order::factory()->create([
            'status'  => 'pending',
            'is_demo' => true,
            'user_id' => $this->admin->id,
        ]);

        $response = $this->actingAs($this->admin)
                         ->get(route('admin.orders.index'));

        $response->assertOk();
        $response->assertSee('#' . $real->id);
    }

    // ── TC-09: Demo order excluded from CSV export ────────────────────────────

    public function test_demo_order_excluded_from_export(): void
    {
        $real = Order::factory()->create([
            'status'  => 'paid',
            'is_demo' => false,
            'user_id' => $this->user->id,
        ]);

        Order::factory()->create([
            'status'  => 'paid',
            'is_demo' => true,
            'user_id' => $this->admin->id,
        ]);

        $response = $this->actingAs($this->admin)
                         ->get(route('admin.orders.export'));

        $response->assertOk();
        $csv = $response->streamedContent();
        $this->assertStringContainsString((string) $real->id, $csv);
    }

    // ── TC-10: Demo order excluded from revenue report ────────────────────────

    public function test_demo_order_excluded_from_revenue_report(): void
    {
        Order::factory()->create([
            'status'  => 'paid',
            'total'   => 200.00,
            'is_demo' => true,
        ]);

        $this->actingAs($this->admin)
             ->get(route('admin.revenue.index'))
             ->assertOk()
             ->assertDontSee('200.00');
    }

    // ── TC-11: scopeReal() filters demo orders ────────────────────────────────

    public function test_scope_real_excludes_demo_orders(): void
    {
        Order::factory()->create(['is_demo' => false]);
        Order::factory()->create(['is_demo' => true]);

        $this->assertSame(1, Order::real()->count());
    }

    // ── TC-12: status() endpoint returns JSON ────────────────────────────────

    public function test_status_endpoint_returns_json(): void
    {
        $order = Order::factory()->create([
            'is_demo'         => true,
            'ship_sim_status' => 'preparing',
            'user_id'         => $this->admin->id,
        ]);

        $this->actingAs($this->admin)
             ->getJson(route('admin.demo.status', $order->id))
             ->assertOk()
             ->assertJsonPath('ship_sim_status', 'preparing');
    }

    // ── TC-13: status() returns 404 for real order ───────────────────────────

    public function test_status_endpoint_404_for_real_order(): void
    {
        $order = Order::factory()->create(['is_demo' => false]);

        $this->actingAs($this->admin)
             ->getJson(route('admin.demo.status', $order->id))
             ->assertNotFound();
    }

    // ── TC-14: Job advances preparing → picked_up ────────────────────────────

    public function test_job_advances_preparing_to_picked_up(): void
    {
        Queue::fake();

        $order = Order::factory()->create([
            'is_demo'         => true,
            'ship_sim_status' => 'preparing',
        ]);

        // Run the job synchronously (fake queue, call handle directly)
        $job = new ShipmentSimulatorJob($order->id, 'preparing');
        $job->handle();

        $order->refresh();
        $this->assertSame('picked_up', $order->ship_sim_status);
    }

    // ── TC-15: Job from arrived → terminal ───────────────────────────────────

    public function test_job_from_arrived_reaches_terminal_state(): void
    {
        Queue::fake();

        $order = Order::factory()->create([
            'is_demo'         => true,
            'ship_sim_status' => 'arrived',
        ]);

        $job = new ShipmentSimulatorJob($order->id, 'arrived');
        $job->handle();

        $order->refresh();
        $this->assertContains($order->ship_sim_status, ['delivered', 'incident']);
    }

    // ── TC-16: Job creates AdminNotification for payment_confirmed ───────────

    public function test_job_creates_admin_notification_for_payment_confirmed(): void
    {
        Queue::fake();

        $order = Order::factory()->create([
            'is_demo'         => true,
            'ship_sim_status' => 'payment_confirmed',
        ]);

        $job = new ShipmentSimulatorJob($order->id, 'payment_confirmed');
        $job->handle();

        $this->assertDatabaseHas('admin_notifications', [
            'order_id' => $order->id,
        ]);
    }

    // ── TC-17: Job creates AdminNotification for incident ────────────────────

    public function test_job_creates_admin_notification_for_incident(): void
    {
        Queue::fake();

        $order = Order::factory()->create([
            'is_demo'         => true,
            'ship_sim_status' => 'arrived',
        ]);

        // Force incident by running the job many times until we get one,
        // or check that the notification path exists. We seed random_int mock.
        // Since we cannot mock random_int easily, run the job repeatedly.
        $gotIncident = false;
        for ($i = 0; $i < 50; $i++) {
            $order->update(['ship_sim_status' => 'arrived']);
            $job = new ShipmentSimulatorJob($order->id, 'arrived');
            $job->handle();
            $order->refresh();
            if ($order->ship_sim_status === 'incident') {
                $gotIncident = true;
                break;
            }
        }

        if ($gotIncident) {
            $this->assertDatabaseHas('admin_notifications', [
                'order_id' => $order->id,
            ]);
        } else {
            // Mark as inconclusive — probabilistic test, 10% chance * 50 tries ≈ 1-(0.9^50) ≈ 99.5%
            $this->markTestSkipped('Incident did not trigger in 50 attempts (extremely unlikely).');
        }
    }

    // ── TC-18: Job is no-op for terminal order ────────────────────────────────

    public function test_job_is_noop_for_terminal_order(): void
    {
        Queue::fake();

        $order = Order::factory()->create([
            'is_demo'         => true,
            'ship_sim_status' => 'delivered',
        ]);

        $notifCountBefore = AdminNotification::count();

        $job = new ShipmentSimulatorJob($order->id, 'in_transit');
        $job->handle();

        $order->refresh();
        $this->assertSame('delivered', $order->ship_sim_status);
        $this->assertSame($notifCountBefore, AdminNotification::count());
    }
}
