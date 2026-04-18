<?php

namespace Tests\Feature;

use App\Jobs\ImportProductsCsvJob;
use App\Jobs\NotifyAdminLowStock;
use App\Jobs\NotifyAdminOfNewOrder;
use App\Jobs\SendOrderConfirmationEmail;
use App\Jobs\SendOrderStatusChangedEmail;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use ReflectionClass;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * NF-008 — Heavy operations (email dispatch, CSV import) run via Laravel Queues.
 *
 * Strategy:
 *   1. Contract audit  — every heavy-operation job implements ShouldQueue.
 *   2. Trait audit     — every job uses the required queue traits.
 *   3. Source audit    — no controller sends mail directly; all go via dispatched jobs.
 *   4. Runtime audit   — queue dispatch verified for each integration point.
 *   5. Config audit    — QUEUE_CONNECTION is configurable via env.
 */
class QueuedHeavyOperationsTest extends TestCase
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

    private function assertJobUsesQueueTraits(string $jobClass): void
    {
        $traits = (new ReflectionClass($jobClass))->getTraitNames();
        $this->assertContains(Queueable::class, $traits,           "{$jobClass} must use Queueable");
        $this->assertContains(Dispatchable::class, $traits,        "{$jobClass} must use Dispatchable");
        $this->assertContains(InteractsWithQueue::class, $traits,  "{$jobClass} must use InteractsWithQueue");
        $this->assertContains(SerializesModels::class, $traits,    "{$jobClass} must use SerializesModels");
    }

    // -------------------------------------------------------------------------
    // TC-01: SendOrderStatusChangedEmail implements ShouldQueue
    // -------------------------------------------------------------------------

    public function test_nf008_tc01_send_order_status_changed_email_implements_should_queue(): void
    {
        $order = Order::factory()->create();
        $this->assertInstanceOf(ShouldQueue::class, new SendOrderStatusChangedEmail($order));
    }

    // -------------------------------------------------------------------------
    // TC-02: SendOrderConfirmationEmail implements ShouldQueue
    // -------------------------------------------------------------------------

    public function test_nf008_tc02_send_order_confirmation_email_implements_should_queue(): void
    {
        $order = Order::factory()->create();
        $this->assertInstanceOf(ShouldQueue::class, new SendOrderConfirmationEmail($order));
    }

    // -------------------------------------------------------------------------
    // TC-03: NotifyAdminOfNewOrder implements ShouldQueue
    // -------------------------------------------------------------------------

    public function test_nf008_tc03_notify_admin_of_new_order_implements_should_queue(): void
    {
        $order = Order::factory()->create();
        $this->assertInstanceOf(ShouldQueue::class, new NotifyAdminOfNewOrder($order));
    }

    // -------------------------------------------------------------------------
    // TC-04: NotifyAdminLowStock implements ShouldQueue
    // -------------------------------------------------------------------------

    public function test_nf008_tc04_notify_admin_low_stock_implements_should_queue(): void
    {
        $product = Product::factory()->create();
        $this->assertInstanceOf(ShouldQueue::class, new NotifyAdminLowStock($product));
    }

    // -------------------------------------------------------------------------
    // TC-05: ImportProductsCsvJob implements ShouldQueue
    // -------------------------------------------------------------------------

    public function test_nf008_tc05_import_products_csv_job_implements_should_queue(): void
    {
        $this->assertInstanceOf(ShouldQueue::class, new ImportProductsCsvJob(1));
    }

    // -------------------------------------------------------------------------
    // TC-06: All heavy-operation jobs use the required queue traits
    // -------------------------------------------------------------------------

    public function test_nf008_tc06_all_jobs_use_required_queue_traits(): void
    {
        $this->assertJobUsesQueueTraits(SendOrderStatusChangedEmail::class);
        $this->assertJobUsesQueueTraits(SendOrderConfirmationEmail::class);
        $this->assertJobUsesQueueTraits(NotifyAdminOfNewOrder::class);
        $this->assertJobUsesQueueTraits(NotifyAdminLowStock::class);
        $this->assertJobUsesQueueTraits(ImportProductsCsvJob::class);
    }

    // -------------------------------------------------------------------------
    // TC-07: Order status update dispatches SendOrderStatusChangedEmail to queue
    // -------------------------------------------------------------------------

    public function test_nf008_tc07_order_status_update_dispatches_status_changed_email(): void
    {
        Queue::fake();

        $admin = $this->makeAdmin();
        $order = Order::factory()->create(['status' => 'paid']);

        $this->actingAs($admin)
            ->patch(route('admin.orders.status', $order), ['status' => 'processing']);

        Queue::assertPushed(SendOrderStatusChangedEmail::class, fn($job) => $job->order->is($order));
    }

    // -------------------------------------------------------------------------
    // TC-08: Stripe webhook dispatches SendOrderConfirmationEmail to queue
    // -------------------------------------------------------------------------

    public function test_nf008_tc08_webhook_dispatches_order_confirmation_email(): void
    {
        Queue::fake();

        $order = Order::factory()->create([
            'status'                    => 'pending',
            'stripe_payment_intent_id'  => 'pi_nf008_test',
        ]);

        $payload  = json_encode(['type' => 'payment_intent.succeeded', 'data' => ['object' => ['id' => 'pi_nf008_test']]]);
        $secret   = config('services.stripe.webhook_secret');
        $ts       = time();
        $sig      = 't=' . $ts . ',v1=' . hash_hmac('sha256', $ts . '.' . $payload, $secret);

        $this->postJson(route('webhook.stripe'), json_decode($payload, true), ['Stripe-Signature' => $sig]);

        Queue::assertPushed(SendOrderConfirmationEmail::class, fn($job) => $job->order->is($order));
    }

    // -------------------------------------------------------------------------
    // TC-09: Stripe webhook dispatches NotifyAdminOfNewOrder to queue
    // -------------------------------------------------------------------------

    public function test_nf008_tc09_webhook_dispatches_notify_admin_of_new_order(): void
    {
        Queue::fake();

        $order = Order::factory()->create([
            'status'                   => 'pending',
            'stripe_payment_intent_id' => 'pi_nf008_admin',
        ]);

        $payload = json_encode(['type' => 'payment_intent.succeeded', 'data' => ['object' => ['id' => 'pi_nf008_admin']]]);
        $secret  = config('services.stripe.webhook_secret');
        $ts      = time();
        $sig     = 't=' . $ts . ',v1=' . hash_hmac('sha256', $ts . '.' . $payload, $secret);

        $this->postJson(route('webhook.stripe'), json_decode($payload, true), ['Stripe-Signature' => $sig]);

        Queue::assertPushed(NotifyAdminOfNewOrder::class, fn($job) => $job->order->is($order));
    }

    // -------------------------------------------------------------------------
    // TC-10: CSV import dispatches ImportProductsCsvJob to queue
    // -------------------------------------------------------------------------

    public function test_nf008_tc10_csv_import_dispatches_import_products_csv_job(): void
    {
        Queue::fake();
        Storage::fake('local');

        $admin = $this->makeAdmin();

        $csv = UploadedFile::fake()->createWithContent(
            'products.csv',
            "name,description,price,stock,status,category\nTest,Desc,9.99,10,published,\n"
        );

        $this->actingAs($admin)
            ->post(route('admin.products.import'), ['csv_file' => $csv]);

        Queue::assertPushed(ImportProductsCsvJob::class);
    }

    // -------------------------------------------------------------------------
    // TC-11: No controller sends mail directly (source audit)
    // -------------------------------------------------------------------------

    public function test_nf008_tc11_controllers_do_not_send_mail_directly(): void
    {
        $controllerFiles = glob(app_path('Http/Controllers/**/*.php'));
        $controllerFiles = array_merge($controllerFiles, glob(app_path('Http/Controllers/*.php')));

        foreach ($controllerFiles as $file) {
            $source = file_get_contents($file);
            $this->assertStringNotContainsString(
                'Mail::to(',
                $source,
                basename($file) . ' must not call Mail::to() directly; use a queued job instead.'
            );
        }
    }

    // -------------------------------------------------------------------------
    // TC-12: QUEUE_CONNECTION is configurable via env
    // -------------------------------------------------------------------------

    public function test_nf008_tc12_queue_connection_is_configurable_via_env(): void
    {
        $queueConfig = file_get_contents(config_path('queue.php'));

        $this->assertStringContainsString(
            "env('QUEUE_CONNECTION'",
            $queueConfig,
            "config/queue.php must read QUEUE_CONNECTION from env."
        );
    }
}
