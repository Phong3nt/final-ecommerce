<?php

namespace Tests\Feature;

use App\Jobs\NotifyAdminOfNewOrder;
use App\Models\AdminNotification;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\User;
use App\Services\FirebaseService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * IMP-017 — Real-time admin notifications via Firebase
 *
 * Upgrades the 30-second polling bell to Firebase RTDB on('value') push.
 * Backend writes latest_notification node; frontend listens in real-time.
 */
class FirebaseNotificationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Role::firstOrCreate(['name' => 'user', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
    }

    private function makeAdmin(): User
    {
        $admin = User::factory()->create(['is_active' => true]);
        $admin->assignRole('admin');
        return $admin;
    }

    private function makeUser(): User
    {
        $user = User::factory()->create(['is_active' => true]);
        $user->assignRole('user');
        return $user;
    }

    private function makeOrder(User $user): Order
    {
        $order = Order::factory()->for($user)->create([
            'status'          => 'paid',
            'subtotal'        => 50.00,
            'shipping_cost'   => 5.00,
            'total'           => 55.00,
            'shipping_method' => 'standard',
            'shipping_label'  => 'Standard Shipping',
            'address'         => ['name' => 'Test', 'city' => 'NY'],
        ]);

        OrderItem::factory()->for($order)->create([
            'product_name' => 'Widget',
            'quantity'     => 1,
            'unit_price'   => 50.00,
            'subtotal'     => 50.00,
        ]);

        return $order;
    }

    // -------------------------------------------------------------------------
    // TC-01  FirebaseService sends HTTP PUT when FIREBASE_DB_URL is configured
    // -------------------------------------------------------------------------
    public function test_imp017_tc01_firebase_service_sends_put_when_configured(): void
    {
        Http::fake(['*' => Http::response(['name' => 'latest_notification'], 200)]);

        $this->app['config']->set('services.firebase.db_url', 'https://test-project-default-rtdb.firebaseio.com');
        $this->app['config']->set('services.firebase.secret', 'test-secret');

        $service = new FirebaseService();
        $service->pushAdminNotification(42, 3);

        Http::assertSent(function ($request) {
            return str_contains($request->url(), 'test-project-default-rtdb.firebaseio.com')
                && str_contains($request->url(), 'admin/latest_notification.json');
        });
    }

    // -------------------------------------------------------------------------
    // TC-02  FirebaseService skips HTTP call when FIREBASE_DB_URL is empty
    // -------------------------------------------------------------------------
    public function test_imp017_tc02_firebase_service_skips_when_not_configured(): void
    {
        Http::fake();

        $this->app['config']->set('services.firebase.db_url', '');

        $service = new FirebaseService();
        $service->pushAdminNotification(1, 1);

        Http::assertNothingSent();
    }

    // -------------------------------------------------------------------------
    // TC-03  Job still creates AdminNotification DB record (backward compat)
    // -------------------------------------------------------------------------
    public function test_imp017_tc03_job_still_creates_db_notification(): void
    {
        Http::fake(['*' => Http::response([], 200)]);
        Mail::fake();

        $this->app['config']->set('services.firebase.db_url', 'https://test-project-default-rtdb.firebaseio.com');

        $user  = $this->makeUser();
        $order = $this->makeOrder($user);

        (new NotifyAdminOfNewOrder($order))->handle();

        $this->assertDatabaseCount('admin_notifications', 1);
    }

    // -------------------------------------------------------------------------
    // TC-04  Firebase exception does NOT prevent AdminNotification DB creation
    // -------------------------------------------------------------------------
    public function test_imp017_tc04_firebase_failure_does_not_block_db_write(): void
    {
        Http::fake(['*' => fn () => throw new \Exception('Firebase unreachable')]);
        Mail::fake();

        $this->app['config']->set('services.firebase.db_url', 'https://test-project-default-rtdb.firebaseio.com');

        $user  = $this->makeUser();
        $order = $this->makeOrder($user);

        (new NotifyAdminOfNewOrder($order))->handle();

        $this->assertDatabaseCount('admin_notifications', 1);
    }

    // -------------------------------------------------------------------------
    // TC-05  Bell partial renders data-imp017="bell-firebase" attribute
    // -------------------------------------------------------------------------
    public function test_imp017_tc05_bell_partial_has_imp017_attribute(): void
    {
        $html = view('admin.partials.notification-bell')->render();

        $this->assertStringContainsString('data-imp017="bell-firebase"', $html);
    }

    // -------------------------------------------------------------------------
    // TC-06  Bell partial script contains Firebase real-time on('value') listener
    // -------------------------------------------------------------------------
    public function test_imp017_tc06_bell_partial_has_firebase_listener(): void
    {
        $html = view('admin.partials.notification-bell')->render();

        $this->assertStringContainsString(".on('value'", $html);
    }

    // -------------------------------------------------------------------------
    // TC-07  Admin dashboard body renders data-imp017="realtime-enabled"
    // -------------------------------------------------------------------------
    public function test_imp017_tc07_dashboard_has_realtime_enabled_attr(): void
    {
        $admin = $this->makeAdmin();

        $this->actingAs($admin)
            ->get(route('admin.dashboard'))
            ->assertStatus(200)
            ->assertSee('data-imp017="realtime-enabled"', false);
    }

    // -------------------------------------------------------------------------
    // TC-08  Bell partial HTML does NOT expose FIREBASE_SECRET to the browser
    // -------------------------------------------------------------------------
    public function test_imp017_tc08_firebase_secret_not_in_bell_html(): void
    {
        $this->app['config']->set('services.firebase.secret', 'super-secret-rtdb-token-12345');

        $html = view('admin.partials.notification-bell')->render();

        $this->assertStringNotContainsString('super-secret-rtdb-token-12345', $html);
    }

    // -------------------------------------------------------------------------
    // TC-09  Firebase push payload includes correct order_id
    // -------------------------------------------------------------------------
    public function test_imp017_tc09_firebase_push_includes_correct_order_id(): void
    {
        Http::fake(['*' => Http::response([], 200)]);
        Mail::fake();

        $this->app['config']->set('services.firebase.db_url', 'https://test-project-default-rtdb.firebaseio.com');

        $user  = $this->makeUser();
        $order = $this->makeOrder($user);

        (new NotifyAdminOfNewOrder($order))->handle();

        Http::assertSent(function ($request) use ($order) {
            $body = $request->data();
            return isset($body['order_id']) && $body['order_id'] === $order->id;
        });
    }

    // -------------------------------------------------------------------------
    // TC-10  Admin notification list endpoint still returns correct JSON (regression)
    // -------------------------------------------------------------------------
    public function test_imp017_tc10_notifications_endpoint_still_returns_json(): void
    {
        $admin = $this->makeAdmin();

        $this->actingAs($admin)
            ->getJson(route('admin.notifications.index'))
            ->assertStatus(200)
            ->assertJsonStructure(['unread_count', 'notifications']);
    }

    // -------------------------------------------------------------------------
    // TC-11  Bell partial uses 120 s polling interval (reduced from 30 s)
    // -------------------------------------------------------------------------
    public function test_imp017_tc11_bell_uses_reduced_polling_interval(): void
    {
        $html = view('admin.partials.notification-bell')->render();

        $this->assertStringNotContainsString('30000', $html);
        $this->assertStringContainsString('120000', $html);
    }

    // -------------------------------------------------------------------------
    // TC-12  Dashboard data-imp017 requires admin auth (non-admin gets 403)
    // -------------------------------------------------------------------------
    public function test_imp017_tc12_dashboard_realtime_requires_admin(): void
    {
        $user = $this->makeUser();

        $this->actingAs($user)
            ->get(route('admin.dashboard'))
            ->assertStatus(403);
    }
}
