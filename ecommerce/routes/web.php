<?php

use App\Http\Controllers\ReviewController;
use App\Http\Controllers\CouponController;
use App\Http\Controllers\UserAddressController;
use App\Http\Controllers\Admin\OrderStatusController;
use App\Http\Controllers\Admin\OrderController as AdminOrderController;
use App\Http\Controllers\Admin\AdminController;
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
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
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

});

// AU-006: Admin routes — auth + role:admin required
Route::middleware(['auth', 'role:admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/dashboard', [AdminController::class, 'dashboard'])->name('dashboard');

    // AD-002: Revenue chart data endpoint
    Route::get('/chart-data', [AdminController::class, 'chartData'])->name('chart-data');

    // OM-001: Admin order list with filters, sort, pagination
    Route::get('/orders', [AdminOrderController::class, 'index'])->name('orders.index');

    // OM-002: Admin order detail
    Route::get('/orders/{order}', [AdminOrderController::class, 'show'])->name('orders.show');

    // OH-003: Admin order status update
    Route::patch('/orders/{order}/status', [OrderStatusController::class, 'update'])->name('orders.status');

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

    // PM-004: Admin category CRUD
    Route::get('/categories', [AdminCategoryController::class, 'index'])->name('categories.index');
    Route::get('/categories/create', [AdminCategoryController::class, 'create'])->name('categories.create');
    Route::post('/categories', [AdminCategoryController::class, 'store'])->name('categories.store');
    Route::get('/categories/{category}/edit', [AdminCategoryController::class, 'edit'])->name('categories.edit');
    Route::patch('/categories/{category}', [AdminCategoryController::class, 'update'])->name('categories.update');
    Route::delete('/categories/{category}', [AdminCategoryController::class, 'destroy'])->name('categories.destroy');
});

// CP-003: Stripe webhook — public, no CSRF, no auth (Stripe signs the payload)
Route::post('/webhook/stripe', [CheckoutController::class, 'handleWebhook'])
    ->withoutMiddleware([\App\Http\Middleware\VerifyCsrfToken::class])
    ->name('webhook.stripe');

