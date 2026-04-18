<?php

namespace Tests\Feature;

use App\Jobs\NotifyAdminLowStock;
use App\Models\AdminNotification;
use App\Models\Product;
use App\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * NT-003 — Admin alerted when product stock falls below configurable threshold.
 *
 * Acceptance criteria:
 *   - Configurable threshold per product
 *   - Notification sent once per threshold breach until restocked
 */
class LowStockNotificationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'user',  'guard_name' => 'web']);
    }

    private function makeAdmin(): User
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        return $admin;
    }

    private function makeProduct(array $attrs = []): Product
    {
        return Product::factory()->create(array_merge(['status' => 'published'], $attrs));
    }

    private function updatePayload(Product $product, array $overrides = []): array
    {
        return array_merge([
            'name'   => $product->name,
            'price'  => $product->price,
            'stock'  => $product->stock,
            'status' => $product->status,
        ], $overrides);
    }

    // -------------------------------------------------------------------------
    // TC-01: Job implements ShouldQueue
    // -------------------------------------------------------------------------

    public function test_nt003_tc01_notify_admin_low_stock_job_implements_should_queue(): void
    {
        $this->assertInstanceOf(
            ShouldQueue::class,
            new NotifyAdminLowStock($this->makeProduct())
        );
    }

    // -------------------------------------------------------------------------
    // TC-02: Product model has low_stock_threshold in fillable
    // -------------------------------------------------------------------------

    public function test_nt003_tc02_product_has_low_stock_threshold_fillable(): void
    {
        $product = new Product();
        $this->assertContains('low_stock_threshold', $product->getFillable());
    }

    // -------------------------------------------------------------------------
    // TC-03: Product model has low_stock_notified in fillable
    // -------------------------------------------------------------------------

    public function test_nt003_tc03_product_has_low_stock_notified_fillable(): void
    {
        $product = new Product();
        $this->assertContains('low_stock_notified', $product->getFillable());
    }

    // -------------------------------------------------------------------------
    // TC-04: Admin can set low_stock_threshold via product update
    // -------------------------------------------------------------------------

    public function test_nt003_tc04_admin_can_set_low_stock_threshold(): void
    {
        Queue::fake();

        $admin   = $this->makeAdmin();
        $product = $this->makeProduct(['stock' => 100]);

        $this->actingAs($admin)
            ->patch(route('admin.products.update', $product), $this->updatePayload($product, [
                'low_stock_threshold' => 10,
                'stock' => 100,
            ]));

        $this->assertEquals(10, $product->fresh()->low_stock_threshold);
    }

    // -------------------------------------------------------------------------
    // TC-05: Stock update below threshold dispatches NotifyAdminLowStock job
    // -------------------------------------------------------------------------

    public function test_nt003_tc05_stock_below_threshold_dispatches_job(): void
    {
        Queue::fake();

        $admin   = $this->makeAdmin();
        $product = $this->makeProduct(['stock' => 10]);

        $this->actingAs($admin)
            ->patch(route('admin.products.update', $product), $this->updatePayload($product, [
                'stock' => 3,
                'low_stock_threshold' => 5,
            ]));

        Queue::assertPushed(NotifyAdminLowStock::class);
    }

    // -------------------------------------------------------------------------
    // TC-06: Stock at exactly threshold level also triggers notification
    // -------------------------------------------------------------------------

    public function test_nt003_tc06_stock_at_threshold_dispatches_job(): void
    {
        Queue::fake();

        $admin   = $this->makeAdmin();
        $product = $this->makeProduct(['stock' => 10]);

        $this->actingAs($admin)
            ->patch(route('admin.products.update', $product), $this->updatePayload($product, [
                'stock' => 5,
                'low_stock_threshold' => 5,
            ]));

        Queue::assertPushed(NotifyAdminLowStock::class);
    }

    // -------------------------------------------------------------------------
    // TC-07: Stock above threshold does NOT dispatch job
    // -------------------------------------------------------------------------

    public function test_nt003_tc07_stock_above_threshold_does_not_dispatch_job(): void
    {
        Queue::fake();

        $admin   = $this->makeAdmin();
        $product = $this->makeProduct(['stock' => 10]);

        $this->actingAs($admin)
            ->patch(route('admin.products.update', $product), $this->updatePayload($product, [
                'stock' => 20,
                'low_stock_threshold' => 5,
            ]));

        Queue::assertNotPushed(NotifyAdminLowStock::class);
    }

    // -------------------------------------------------------------------------
    // TC-08: Already-notified product does NOT dispatch job again for same breach
    // -------------------------------------------------------------------------

    public function test_nt003_tc08_already_notified_does_not_dispatch_again(): void
    {
        Queue::fake();

        $admin   = $this->makeAdmin();
        $product = $this->makeProduct([
            'stock'              => 3,
            'low_stock_threshold' => 5,
            'low_stock_notified' => true,
        ]);

        // Stock is still below threshold — should NOT dispatch again
        $this->actingAs($admin)
            ->patch(route('admin.products.update', $product), $this->updatePayload($product, [
                'stock' => 2,
                'low_stock_threshold' => 5,
            ]));

        Queue::assertNotPushed(NotifyAdminLowStock::class);
    }

    // -------------------------------------------------------------------------
    // TC-09: Updating stock above threshold resets low_stock_notified to false
    // -------------------------------------------------------------------------

    public function test_nt003_tc09_stock_above_threshold_resets_notified_flag(): void
    {
        Queue::fake();

        $admin   = $this->makeAdmin();
        $product = $this->makeProduct([
            'stock'              => 2,
            'low_stock_threshold' => 5,
            'low_stock_notified' => true,
        ]);

        $this->actingAs($admin)
            ->patch(route('admin.products.update', $product), $this->updatePayload($product, [
                'stock' => 20,
                'low_stock_threshold' => 5,
            ]));

        $this->assertFalse((bool) $product->fresh()->low_stock_notified);
    }

    // -------------------------------------------------------------------------
    // TC-10: After flag reset, next below-threshold update dispatches again
    // -------------------------------------------------------------------------

    public function test_nt003_tc10_after_reset_next_breach_dispatches_again(): void
    {
        Queue::fake();

        $admin   = $this->makeAdmin();
        // Start above threshold, not notified
        $product = $this->makeProduct([
            'stock'              => 20,
            'low_stock_threshold' => 5,
            'low_stock_notified' => false,
        ]);

        // Drop below threshold
        $this->actingAs($admin)
            ->patch(route('admin.products.update', $product), $this->updatePayload($product, [
                'stock' => 3,
                'low_stock_threshold' => 5,
            ]));

        Queue::assertPushed(NotifyAdminLowStock::class);
    }

    // -------------------------------------------------------------------------
    // TC-11: Product with null threshold never dispatches job
    // -------------------------------------------------------------------------

    public function test_nt003_tc11_null_threshold_never_dispatches_job(): void
    {
        Queue::fake();

        $admin   = $this->makeAdmin();
        $product = $this->makeProduct(['stock' => 10]);

        $this->actingAs($admin)
            ->patch(route('admin.products.update', $product), $this->updatePayload($product, [
                'stock'              => 2,
                'low_stock_threshold' => null,
            ]));

        Queue::assertNotPushed(NotifyAdminLowStock::class);
    }

    // -------------------------------------------------------------------------
    // TC-12: Job handle() creates an AdminNotification record
    // -------------------------------------------------------------------------

    public function test_nt003_tc12_job_creates_admin_notification(): void
    {
        $product = $this->makeProduct([
            'stock'              => 3,
            'low_stock_threshold' => 5,
        ]);

        (new NotifyAdminLowStock($product))->handle();

        $this->assertDatabaseCount('admin_notifications', 1);
    }

    // -------------------------------------------------------------------------
    // TC-13: AdminNotification message contains product name
    // -------------------------------------------------------------------------

    public function test_nt003_tc13_notification_message_contains_product_name(): void
    {
        $product = $this->makeProduct([
            'name'               => 'Test Widget',
            'stock'              => 3,
            'low_stock_threshold' => 5,
        ]);

        (new NotifyAdminLowStock($product))->handle();

        $notification = AdminNotification::first();
        $this->assertStringContainsString('Test Widget', $notification->message);
    }

    // -------------------------------------------------------------------------
    // TC-14: low_stock_notified is true after breach
    // -------------------------------------------------------------------------

    public function test_nt003_tc14_low_stock_notified_is_true_after_breach(): void
    {
        Queue::fake();

        $admin   = $this->makeAdmin();
        $product = $this->makeProduct([
            'stock'              => 10,
            'low_stock_threshold' => 5,
            'low_stock_notified' => false,
        ]);

        $this->actingAs($admin)
            ->patch(route('admin.products.update', $product), $this->updatePayload($product, [
                'stock' => 3,
                'low_stock_threshold' => 5,
            ]));

        $this->assertTrue((bool) $product->fresh()->low_stock_notified);
    }

    // -------------------------------------------------------------------------
    // TC-15: Admin product edit form shows low_stock_threshold input field
    // -------------------------------------------------------------------------

    public function test_nt003_tc15_edit_form_shows_low_stock_threshold_field(): void
    {
        $admin   = $this->makeAdmin();
        $product = $this->makeProduct(['low_stock_threshold' => 10]);

        $response = $this->actingAs($admin)
            ->get(route('admin.products.edit', $product));

        $response->assertOk();
        $response->assertSee('low_stock_threshold');
    }
}
