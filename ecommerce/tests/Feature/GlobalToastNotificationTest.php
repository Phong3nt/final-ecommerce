<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * IMP-009 — Global toast notification system (replace bare flash).
 *
 * Scope: [UIUX_MODE]
 * Verifies that flash-driven pages render the shared toast partial and
 * no longer keep page-specific success/error/status session blocks.
 */
class GlobalToastNotificationTest extends TestCase
{
    private function viewSource(string $relativePath): string
    {
        $fullPath = resource_path('views/' . $relativePath);
        $this->assertFileExists($fullPath, "Expected view to exist: {$relativePath}");

        $contents = file_get_contents($fullPath);
        $this->assertIsString($contents, "Expected readable content for: {$relativePath}");

        return $contents;
    }

    public function test_imp009_tc01_toast_partial_exists_with_core_hooks(): void
    {
        $toast = $this->viewSource('partials/toast.blade.php');

        $this->assertStringContainsString('imp009-toast-area', $toast);
        $this->assertStringContainsString('window.imp009EnqueueToast', $toast);
        $this->assertStringContainsString("session('success')", $toast);
        $this->assertStringContainsString("session('error')", $toast);
        $this->assertStringContainsString("session('status')", $toast);
    }

    public function test_imp009_tc02_forgot_password_page_uses_shared_toast_partial(): void
    {
        $view = $this->viewSource('auth/forgot-password.blade.php');

        $this->assertStringContainsString("@include('partials.toast')", $view);
        $this->assertStringNotContainsString("session('status')", $view);
    }

    public function test_imp009_tc03_login_page_uses_shared_toast_partial_for_status_messages(): void
    {
        $view = $this->viewSource('auth/login.blade.php');

        $this->assertStringContainsString("@include('partials.toast')", $view);
    }

    public function test_imp009_tc04_checkout_shipping_uses_toast_partial_instead_of_inline_error_flash(): void
    {
        $view = $this->viewSource('checkout/shipping.blade.php');

        $this->assertStringContainsString("@include('partials.toast')", $view);
        $this->assertStringNotContainsString("session('error')", $view);
    }

    public function test_imp009_tc05_checkout_review_uses_toast_partial_instead_of_inline_error_flash(): void
    {
        $view = $this->viewSource('checkout/review.blade.php');

        $this->assertStringContainsString("@include('partials.toast')", $view);
        $this->assertStringNotContainsString("session('error')", $view);
    }

    public function test_imp009_tc06_checkout_address_uses_toast_partial_for_prerequisite_flash_errors(): void
    {
        $view = $this->viewSource('checkout/address.blade.php');

        $this->assertStringContainsString("@include('partials.toast')", $view);
    }

    public function test_imp009_tc07_cart_index_uses_toast_partial_and_removes_inline_success_flash(): void
    {
        $view = $this->viewSource('cart/index.blade.php');

        $this->assertStringContainsString("@include('partials.toast')", $view);
        $this->assertStringNotContainsString("session('success')", $view);
    }

    public function test_imp009_tc08_product_show_uses_toast_partial_and_removes_inline_success_flash(): void
    {
        $view = $this->viewSource('products/show.blade.php');

        $this->assertStringContainsString("@include('partials.toast')", $view);
        $this->assertStringNotContainsString("session('success')", $view);
    }

    public function test_imp009_tc09_orders_index_uses_toast_partial_for_cancel_success_message(): void
    {
        $view = $this->viewSource('orders/index.blade.php');

        $this->assertStringContainsString("@include('partials.toast')", $view);
    }

    public function test_imp009_tc10_admin_flash_pages_use_toast_partial_and_no_inline_session_flash(): void
    {
        $adminViews = [
            'admin/orders/index.blade.php',
            'admin/orders/show.blade.php',
            'admin/products/index.blade.php',
            'admin/products/images.blade.php',
            'admin/coupons/index.blade.php',
            'admin/categories/index.blade.php',
            'admin/users/show.blade.php',
        ];

        foreach ($adminViews as $relativePath) {
            $view = $this->viewSource($relativePath);
            $this->assertStringContainsString("@include('partials.toast')", $view, "Missing shared toast include in {$relativePath}");
            $this->assertStringNotContainsString("session('success')", $view, "Legacy success flash block still present in {$relativePath}");
            $this->assertStringNotContainsString("session('error')", $view, "Legacy error flash block still present in {$relativePath}");
        }
    }
}
