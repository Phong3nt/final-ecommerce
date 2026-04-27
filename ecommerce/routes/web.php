<?php

use App\Http\Controllers\ReviewController;
use App\Http\Controllers\CouponController;
use App\Http\Controllers\UserAddressController;
use App\Http\Controllers\Admin\AuditLogController;
use App\Http\Controllers\Admin\AdminNotificationController;
use App\Http\Controllers\Admin\OrderStatusController;
use App\Http\Controllers\Admin\RefundController;
use App\Http\Controllers\Admin\UserController as AdminUserController;
use App\Http\Controllers\Admin\OrderController as AdminOrderController;
use App\Http\Controllers\Admin\AdminController;
use App\Http\Controllers\Admin\RevenueController;
use App\Http\Controllers\Admin\CouponController as AdminCouponController;
use App\Http\Controllers\Admin\CategoryController as AdminCategoryController;
use App\Http\Controllers\Admin\ProductController as AdminProductController;
use App\Http\Controllers\Auth\ForgotPasswordController;
use App\Http\Controllers\Auth\GoogleController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\EmailVerificationController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Auth\ResetPasswordController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\PaymentMethodController;
use App\Http\Controllers\ProfileController;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    $categories = Category::whereNull('parent_id')->orderBy('name')->get();
    $featuredProducts = Product::with('category')
        ->where('status', 'active')
        ->where('stock', '>', 0)
        ->whereNotNull('image')
        ->latest()
        ->take(8)
        ->get();
    return view('welcome', compact('categories', 'featuredProducts'));
});

// PC-001: Public product browsing — no auth required
Route::get('/products', [ProductController::class, 'index'])->name('products.index');

// PC-002: Product search — no auth required
Route::get('/products/search', [ProductController::class, 'search'])->name('products.search');

// PC-005: Product detail page — SEO-friendly slug, no auth required
Route::get('/products/{product:slug}', [ProductController::class, 'show'])->name('products.show');

// RV-001: Submit a product review — auth required
Route::post('/products/{product:slug}/reviews', [ReviewController::class, 'store'])
    ->middleware('auth')
    ->name('reviews.store');

// SC-001: Add to cart — session-based, guest + auth allowed
Route::post('/cart', [CartController::class, 'store'])->name('cart.store');

// SC-002: View cart — session-based, guest + auth allowed
Route::get('/cart', [CartController::class, 'index'])->name('cart.index');

// SC-005: Coupon — apply (POST) and remove (DELETE); must precede /cart/{productId} to avoid ambiguity
Route::post('/cart/coupon', [CouponController::class, 'apply'])->name('cart.coupon.apply');
Route::delete('/cart/coupon', [CouponController::class, 'remove'])->name('cart.coupon.remove');

// SC-003: Update cart item quantity — PATCH + form method spoofing
Route::patch('/cart/{productId}', [CartController::class, 'update'])->name('cart.update');

// SC-004: Remove cart item — DELETE + form method spoofing
Route::delete('/cart/{productId}', [CartController::class, 'destroy'])->name('cart.destroy');

// Guest-only auth routes
Route::middleware('guest')->group(function () {
    Route::get('/register', [RegisterController::class, 'show'])->name('register');
    Route::post('/register', [RegisterController::class, 'store'])->middleware('throttle:10,1')->name('register.store');

    Route::get('/login', [LoginController::class, 'show'])->name('login');
    Route::post('/login', [LoginController::class, 'store'])->middleware('throttle:10,1')->name('login.store');

    // AU-005: Password Reset
    Route::get('/forgot-password', [ForgotPasswordController::class, 'show'])->name('password.request');
    Route::post('/forgot-password', [ForgotPasswordController::class, 'store'])->middleware('throttle:10,1')->name('password.email');
    Route::get('/reset-password/{token}', [ResetPasswordController::class, 'show'])->name('password.reset');
    Route::post('/reset-password', [ResetPasswordController::class, 'store'])->name('password.update');

    // AU-003: Google OAuth
    Route::get('/auth/google/redirect', [GoogleController::class, 'redirect'])->name('auth.google.redirect');
    Route::get('/auth/google/callback', [GoogleController::class, 'callback'])->name('auth.google.callback');
});

// Logout (auth required)
Route::post('/logout', [LoginController::class, 'destroy'])
    ->middleware('auth')
    ->name('logout');

// AU-002: Email verification routes
Route::middleware('auth')->group(function () {
    Route::get('/email/verify', [EmailVerificationController::class, 'notice'])
        ->name('verification.notice');
    Route::get('/email/verify/{id}/{hash}', [EmailVerificationController::class, 'verify'])
        ->middleware('signed')
        ->name('verification.verify');
    Route::post('/email/verification-notification', [EmailVerificationController::class, 'resend'])
        ->middleware('throttle:6,1')
        ->name('verification.send');
});

// Authenticated user dashboard
Route::middleware(['auth'])->group(function () {
    Route::get('/dashboard', function () {
        return view('dashboard');
    })->name('dashboard');

    // UP-001: User profile
    Route::get('/profile', [ProfileController::class, 'show'])->name('profile.show');
    Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');

    // IMP-035: Card Vault — saved payment methods
    Route::get('/profile/payment-methods/setup-intent', [PaymentMethodController::class, 'setupIntent'])->name('payment-methods.setup-intent');
    Route::post('/profile/payment-methods', [PaymentMethodController::class, 'store'])->name('payment-methods.store');
    Route::patch('/profile/payment-methods/{pm}/default', [PaymentMethodController::class, 'setDefault'])->name('payment-methods.setDefault');
    Route::delete('/profile/payment-methods/{pm}', [PaymentMethodController::class, 'destroy'])->name('payment-methods.destroy');

    // UP-002: Saved addresses CRUD
    Route::get('/addresses', [UserAddressController::class, 'index'])->name('addresses.index');
    Route::post('/addresses', [UserAddressController::class, 'store'])->name('addresses.store');
    Route::put('/addresses/{address}', [UserAddressController::class, 'update'])->name('addresses.update');
    Route::delete('/addresses/{address}', [UserAddressController::class, 'destroy'])->name('addresses.destroy');
    Route::patch('/addresses/{address}/default', [UserAddressController::class, 'setDefault'])->name('addresses.setDefault');

    // OH-001: Order history
    Route::get('/orders', [OrderController::class, 'index'])->name('orders.index');

    // OH-002: Order detail
    Route::get('/orders/{order}', [OrderController::class, 'show'])->name('orders.show');

    // OH-004: Cancel order
    Route::post('/orders/{order}/cancel', [OrderController::class, 'cancel'])->name('orders.cancel');

    // IMP-003: One-Page Checkout — single view + session-save endpoint
    Route::get('/checkout', [CheckoutController::class, 'showCheckout'])->name('checkout.index');
    Route::post('/checkout/session', [CheckoutController::class, 'storeSession'])->name('checkout.session.store');

    // CP-001: Checkout — shipping address
    Route::get('/checkout/address', [CheckoutController::class, 'showAddress'])->name('checkout.address');
    Route::post('/checkout/address', [CheckoutController::class, 'storeAddress'])->name('checkout.address.store');

    // CP-002: Checkout — shipping method
    Route::get('/checkout/shipping', [CheckoutController::class, 'showShipping'])->name('checkout.shipping');
    Route::post('/checkout/shipping', [CheckoutController::class, 'storeShipping'])->name('checkout.shipping.store');

    // CP-003: Checkout — order review & payment
    Route::get('/checkout/review', [CheckoutController::class, 'showReview'])->name('checkout.review');
    Route::post('/checkout/review', [CheckoutController::class, 'placeOrder'])->name('checkout.place-order');

    // CP-005: Checkout — success / failure page (Stripe redirects here after confirmPayment)
    Route::get('/checkout/success', [CheckoutController::class, 'showSuccess'])->name('checkout.success');

    // IMP-035: Store "save card" preference in session before Stripe confirmPayment redirect
    Route::post('/checkout/save-card-flag', [CheckoutController::class, 'flagSaveCard'])->name('checkout.save-card-flag');

});

// IMP-004: Guest Checkout — no auth required; guests supply email for order tracking
Route::get('/checkout/guest', [CheckoutController::class, 'showGuestCheckout'])->name('checkout.guest.index');
Route::post('/checkout/guest/session', [CheckoutController::class, 'storeGuestSession'])->name('checkout.guest.session.store');
Route::post('/checkout/guest/order', [CheckoutController::class, 'placeGuestOrder'])->name('checkout.guest.place-order');
Route::get('/checkout/guest/success', [CheckoutController::class, 'showGuestSuccess'])->name('checkout.guest.success');

// AU-006: Admin routes — auth + role:admin required
Route::middleware(['auth', 'role:admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/dashboard', [AdminController::class, 'dashboard'])->name('dashboard');

    // AD-002: Revenue chart data endpoint
    Route::get('/chart-data', [AdminController::class, 'chartData'])->name('chart-data');

    // OM-001: Admin order list with filters, sort, pagination
    Route::get('/orders', [AdminOrderController::class, 'index'])->name('orders.index');

    // OM-004: Export orders to CSV
    Route::get('/orders/export', [AdminOrderController::class, 'export'])->name('orders.export');

    // OM-002: Admin order detail
    Route::get('/orders/{order}', [AdminOrderController::class, 'show'])->name('orders.show');

    // OH-003: Admin order status update
    Route::patch('/orders/{order}/status', [OrderStatusController::class, 'update'])->name('orders.status');

    // OM-005: Admin process refund on cancelled order
    Route::post('/orders/{order}/refund', [RefundController::class, 'store'])->name('orders.refund');

    // PM-001: Admin product management
    Route::get('/products', [AdminProductController::class, 'index'])->name('products.index');
    Route::get('/products/create', [AdminProductController::class, 'create'])->name('products.create');
    Route::post('/products', [AdminProductController::class, 'store'])->name('products.store');
    Route::post('/products/import', [AdminProductController::class, 'import'])->name('products.import');

    // PM-002: Admin product edit
    Route::get('/products/{product}/edit', [AdminProductController::class, 'edit'])->name('products.edit');
    Route::patch('/products/{product}', [AdminProductController::class, 'update'])->name('products.update');

    // PM-003: Admin product delete (soft delete)
    Route::delete('/products/{product}', [AdminProductController::class, 'destroy'])->name('products.destroy');

    // PM-006: Admin product image management
    Route::get('/products/{product}/images', [AdminProductController::class, 'images'])->name('products.images');
    Route::post('/products/{product}/images/reorder', [AdminProductController::class, 'reorderImages'])->name('products.images.reorder');
    Route::post('/products/{product}/images/thumbnail', [AdminProductController::class, 'setThumbnail'])->name('products.images.thumbnail');
    Route::delete('/products/{product}/images/{index}', [AdminProductController::class, 'destroyImage'])->name('products.images.destroy')->where('index', '[0-9]+');

    // UM-001: Admin user list
    Route::get('/users', [AdminUserController::class, 'index'])->name('users.index');

    // UM-002: Admin user profile and order history
    Route::get('/users/{user}', [AdminUserController::class, 'show'])->name('users.show');

    // UM-003: Admin toggle user active/suspended status
    Route::patch('/users/{user}/toggle-status', [AdminUserController::class, 'toggleStatus'])->name('users.toggle-status');

    // UM-004: Admin assign or change user role
    Route::patch('/users/{user}/assign-role', [AdminUserController::class, 'assignRole'])->name('users.assign-role');

    // RM-001: Admin revenue report by period
    Route::get('/revenue', [RevenueController::class, 'index'])->name('revenue.index');

    // RM-002: Admin revenue by product/category — sortable table + CSV export
    Route::get('/revenue/products', [RevenueController::class, 'products'])->name('revenue.products');
    Route::get('/revenue/products/export', [RevenueController::class, 'exportProducts'])->name('revenue.products.export');

    // PM-004: Admin category CRUD
    Route::get('/categories', [AdminCategoryController::class, 'index'])->name('categories.index');
    Route::get('/categories/create', [AdminCategoryController::class, 'create'])->name('categories.create');
    Route::post('/categories', [AdminCategoryController::class, 'store'])->name('categories.store');
    Route::get('/categories/{category}/edit', [AdminCategoryController::class, 'edit'])->name('categories.edit');
    Route::patch('/categories/{category}', [AdminCategoryController::class, 'update'])->name('categories.update');
    Route::delete('/categories/{category}', [AdminCategoryController::class, 'destroy'])->name('categories.destroy');

    // RM-003: Admin coupon management — CRUD + active/inactive toggle
    Route::get('/coupons', [AdminCouponController::class, 'index'])->name('coupons.index');
    Route::get('/coupons/create', [AdminCouponController::class, 'create'])->name('coupons.create');
    Route::post('/coupons', [AdminCouponController::class, 'store'])->name('coupons.store');
    Route::get('/coupons/{coupon}/edit', [AdminCouponController::class, 'edit'])->name('coupons.edit');
    Route::patch('/coupons/{coupon}', [AdminCouponController::class, 'update'])->name('coupons.update');
    Route::delete('/coupons/{coupon}', [AdminCouponController::class, 'destroy'])->name('coupons.destroy');
    Route::patch('/coupons/{coupon}/toggle', [AdminCouponController::class, 'toggle'])->name('coupons.toggle');

    // NT-002: Admin in-app notifications for new orders
    Route::get('/notifications', [AdminNotificationController::class, 'index'])->name('notifications.index');
    Route::patch('/notifications/read-all', [AdminNotificationController::class, 'markAllRead'])->name('notifications.read-all');
    Route::patch('/notifications/{notification}/read', [AdminNotificationController::class, 'markRead'])->name('notifications.read');

    // IMP-016: Consolidated audit log
    Route::get('/audit-log', [AuditLogController::class, 'index'])->name('audit-log.index');
});

// CP-003: Stripe webhook — public, no CSRF, no auth (Stripe signs the payload)
Route::post('/webhook/stripe', [CheckoutController::class, 'handleWebhook'])
    ->withoutMiddleware([\App\Http\Middleware\VerifyCsrfToken::class])
    ->name('webhook.stripe');

