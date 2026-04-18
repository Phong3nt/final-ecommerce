<?php

namespace Tests\Feature;

use Tests\TestCase;
use ReflectionClass;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * NF-010 — Unit & Feature Tests (PHPUnit) for Critical Flows
 *
 * Acceptance Criteria:
 *  - Auth flows covered: registration, login, logout, password reset, RBAC
 *  - Checkout flows covered: address, shipping, review, success/failure
 *  - Payment webhook covered: succeeded (marks order paid), failed, confirmation email
 *  - Tests use PHPUnit + Laravel's TestCase (not Pest)
 *  - Critical test classes use RefreshDatabase for clean DB isolation
 *  - Each critical flow has both happy path and unhappy path coverage
 */
class CriticalFlowTestCoverageAuditTest extends TestCase
{
    // ─── TC-01: Auth – registration flow tests exist ─────────────────────────

    /** @test */
    public function nf010_tc01_auth_registration_tests_exist_with_sufficient_coverage(): void
    {
        $path = base_path('tests/Feature/Auth/RegisterTest.php');
        $this->assertFileExists($path, 'Auth/RegisterTest.php must exist');

        $count = $this->countTestMethods(\Tests\Feature\Auth\RegisterTest::class);
        $this->assertGreaterThanOrEqual(
            10,
            $count,
            'Auth registration must have at least 10 test methods (happy + unhappy paths)'
        );
    }

    // ─── TC-02: Auth – login flow tests exist ────────────────────────────────

    /** @test */
    public function nf010_tc02_auth_login_tests_exist_with_sufficient_coverage(): void
    {
        $path = base_path('tests/Feature/Auth/LoginTest.php');
        $this->assertFileExists($path, 'Auth/LoginTest.php must exist');

        $count = $this->countTestMethods(\Tests\Feature\Auth\LoginTest::class);
        $this->assertGreaterThanOrEqual(
            10,
            $count,
            'Auth login must have at least 10 test methods (happy + unhappy paths)'
        );
    }

    // ─── TC-03: Auth – logout and session termination tests exist ────────────

    /** @test */
    public function nf010_tc03_auth_logout_tests_exist(): void
    {
        $path = base_path('tests/Feature/Auth/LogoutTest.php');
        $this->assertFileExists($path, 'Auth/LogoutTest.php must exist');

        $count = $this->countTestMethods(\Tests\Feature\Auth\LogoutTest::class);
        $this->assertGreaterThanOrEqual(
            8,
            $count,
            'Logout tests must cover session destruction, CSRF regeneration, redirect'
        );
    }

    // ─── TC-04: Auth – password reset flow tests exist ───────────────────────

    /** @test */
    public function nf010_tc04_auth_password_reset_tests_exist(): void
    {
        $path = base_path('tests/Feature/Auth/PasswordResetTest.php');
        $this->assertFileExists($path, 'Auth/PasswordResetTest.php must exist');

        $count = $this->countTestMethods(\Tests\Feature\Auth\PasswordResetTest::class);
        $this->assertGreaterThanOrEqual(
            10,
            $count,
            'Password reset must cover email sending, token validation, hash, expiry'
        );
    }

    // ─── TC-05: Auth – role-based access control tests exist ─────────────────

    /** @test */
    public function nf010_tc05_auth_rbac_tests_exist(): void
    {
        $path = base_path('tests/Feature/Auth/RoleAccessControlTest.php');
        $this->assertFileExists($path, 'Auth/RoleAccessControlTest.php must exist');

        $count = $this->countTestMethods(\Tests\Feature\Auth\RoleAccessControlTest::class);
        $this->assertGreaterThanOrEqual(
            10,
            $count,
            'RBAC tests must cover admin access, non-admin 403, guest redirect'
        );
    }

    // ─── TC-06: Checkout – address + shipping + review flow tests exist ───────

    /** @test */
    public function nf010_tc06_checkout_flow_tests_exist(): void
    {
        $this->assertFileExists(
            base_path('tests/Feature/CheckoutAddressTest.php'),
            'CheckoutAddressTest.php must exist'
        );
        $this->assertFileExists(
            base_path('tests/Feature/CheckoutShippingTest.php'),
            'CheckoutShippingTest.php must exist'
        );
        $this->assertFileExists(
            base_path('tests/Feature/CheckoutReviewTest.php'),
            'CheckoutReviewTest.php must exist'
        );
        $this->assertFileExists(
            base_path('tests/Feature/CheckoutSuccessTest.php'),
            'CheckoutSuccessTest.php must exist'
        );
    }

    // ─── TC-07: Checkout – place order creates DB records ────────────────────

    /** @test */
    public function nf010_tc07_checkout_place_order_is_covered(): void
    {
        // CheckoutReviewTest must cover placing an order (creates order + items)
        $source = file_get_contents(
            base_path('tests/Feature/CheckoutReviewTest.php')
        );

        $this->assertStringContainsString(
            'place_order_creates_order_in_database',
            $source,
            'CheckoutReviewTest must verify that placing an order persists the Order record'
        );
        $this->assertStringContainsString(
            'place_order_creates_order_items_in_database',
            $source,
            'CheckoutReviewTest must verify that placing an order persists the OrderItem records'
        );
    }

    // ─── TC-08: Payment webhook – payment_intent.succeeded marks order paid ──

    /** @test */
    public function nf010_tc08_webhook_payment_succeeded_marks_order_paid(): void
    {
        $source = file_get_contents(
            base_path('tests/Feature/CheckoutReviewTest.php')
        );

        $this->assertStringContainsString(
            'webhook_marks_order_paid',
            $source,
            'CheckoutReviewTest must verify that the Stripe webhook marks the order as paid'
        );
    }

    // ─── TC-09: Payment webhook – payment_intent.payment_failed path tested ──

    /** @test */
    public function nf010_tc09_webhook_payment_failed_path_is_covered(): void
    {
        // CheckoutSuccessTest covers the failed-payment page + OrderConfirmationEmailTest
        // covers the webhook not dispatching confirmation on failure.
        $confirmationSource = file_get_contents(
            base_path('tests/Feature/OrderConfirmationEmailTest.php')
        );

        $this->assertStringContainsString(
            'payment_failed_does_not_dispatch',
            $confirmationSource,
            'OrderConfirmationEmailTest must verify that payment_failed webhook does NOT dispatch confirmation'
        );
    }

    // ─── TC-10: Payment webhook – order confirmation email dispatched ─────────

    /** @test */
    public function nf010_tc10_webhook_dispatches_order_confirmation_email(): void
    {
        $source = file_get_contents(
            base_path('tests/Feature/OrderConfirmationEmailTest.php')
        );

        $this->assertFileExists(
            base_path('tests/Feature/OrderConfirmationEmailTest.php'),
            'OrderConfirmationEmailTest.php must exist'
        );

        $this->assertStringContainsString(
            'payment_succeeded_dispatches_confirmation',
            $source,
            'Confirmation email test must verify it is dispatched on payment_intent.succeeded'
        );
    }

    // ─── TC-11: Critical test classes extend PHPUnit TestCase (not Pest) ─────

    /** @test */
    public function nf010_tc11_critical_tests_use_phpunit_not_pest(): void
    {
        $criticalClasses = [
            \Tests\Feature\Auth\RegisterTest::class,
            \Tests\Feature\Auth\LoginTest::class,
            \Tests\Feature\CheckoutReviewTest::class,
            \Tests\Feature\OrderConfirmationEmailTest::class,
        ];

        foreach ($criticalClasses as $class) {
            $reflection = new ReflectionClass($class);
            $this->assertTrue(
                $reflection->isSubclassOf(\Tests\TestCase::class),
                "{$class} must extend Tests\TestCase (PHPUnit), not use Pest"
            );
        }
    }

    // ─── TC-12: Critical test classes use RefreshDatabase trait ──────────────

    /** @test */
    public function nf010_tc12_critical_tests_use_refresh_database_trait(): void
    {
        $criticalClasses = [
            \Tests\Feature\Auth\RegisterTest::class,
            \Tests\Feature\Auth\LoginTest::class,
            \Tests\Feature\CheckoutReviewTest::class,
            \Tests\Feature\OrderConfirmationEmailTest::class,
        ];

        foreach ($criticalClasses as $class) {
            $reflection = new ReflectionClass($class);
            $traitNames = array_keys($reflection->getTraits());
            $this->assertContains(
                \Illuminate\Foundation\Testing\RefreshDatabase::class,
                $traitNames,
                "{$class} must use RefreshDatabase trait for clean DB isolation per test"
            );
        }
    }

    // ─── Helpers ─────────────────────────────────────────────────────────────

    /**
     * Count test methods in a class (methods prefixed "test" or annotated @test).
     */
    private function countTestMethods(string $class): int
    {
        $reflection = new ReflectionClass($class);
        $count = 0;
        foreach ($reflection->getMethods(\ReflectionMethod::IS_PUBLIC) as $method) {
            $name = $method->getName();
            $docComment = $method->getDocComment() ?: '';
            if (str_starts_with($name, 'test') || str_contains($docComment, '@test')) {
                $count++;
            }
        }
        return $count;
    }
}
