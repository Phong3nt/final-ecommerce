@extends('layouts.app')

@section('title', 'Your Cart — ShopName')

@push('styles')
    <style>
        /* IMP-007: keep — Cart page micro-interaction styles */
        .imp007-spinner {
            display: inline-block;
            width: 12px;
            height: 12px;
            border: 2px solid currentColor;
            border-top-color: transparent;
            border-radius: 50%;
            animation: imp007spin 0.55s linear infinite;
            vertical-align: middle;
            opacity: 0.75;
        }

        @keyframes imp007spin {
            to {
                transform: rotate(360deg);
            }
        }

        .cart-item {
            transition: opacity 0.3s ease, transform 0.3s ease;
        }

        .cart-item.imp007-removing {
            opacity: 0;
            transform: translateX(14px);
            pointer-events: none;
        }

        .qty-saved {
            color: #198754;
            font-size: .8rem;
        }

        .imp007-toast-area {
            position: fixed;
            top: 1rem;
            right: 1rem;
            z-index: 9999;
            display: flex;
            flex-direction: column;
            gap: .4rem;
            max-width: 260px;
        }

        .imp007-toast {
            padding: .55rem .9rem;
            border-radius: .4rem;
            font-size: .875rem;
            font-family: sans-serif;
            box-shadow: 0 4px 14px rgba(0, 0, 0, .12);
            background: #212529;
            color: #fff;
        }

        .imp007-toast--success {
            background: #198754;
        }

        .imp007-toast--error {
            background: #dc3545;
        }

        /* IMP-029: Cart redesign */
        .imp029-thumb-placeholder {
            width: 64px;
            height: 64px;
            min-width: 64px;
            background: #f0f2f5;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #adb5bd;
            font-size: 1.5rem;
        }

        .imp029-qty-input {
            width: 64px;
            text-align: center;
        }

        .imp029-summary-panel {
            position: sticky;
            top: 80px;
        }
    </style>
@endpush

@section('content')@include('partials.toast')    <div x-data x-init="$el.classList.add('fade-in')">

        {{-- IMP-007: Toast notification area --}}
        <div class="imp007-toast-area" x-data="imp007ToastManager()" x-on:imp007-toast.window="show($event.detail)">
            <template x-for="t in toasts" :key="t.id">
                <div class="imp007-toast" :class="'imp007-toast--' + t.type" x-text="t.msg" x-transition></div>
            </template>
        </div>

        {{-- Page header --}}
        <div class="d-flex align-items-center justify-content-between mb-4">
            <h1 class="h3 fw-bold mb-0">
                <i class="bi bi-cart3 me-2 text-primary"></i>Your Cart
                @if (!empty($cart))
                    <span class="badge bg-primary ms-2 align-middle" style="font-size:.7rem;">{{ count($cart) }}</span>
                @endif
            </h1>
            <a href="{{ route('products.index') }}" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-arrow-left me-1"></i>Continue Shopping
            </a>
        </div>

        @if ($errors->has('coupon'))
            <div class="alert alert-danger alert-dismissible fade show mb-3" role="alert">
                {{ $errors->first('coupon') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        @if (empty($cart))
            {{-- Empty state --}}
            <div class="card shadow-sm border-0 rounded-3 text-center py-5">
                <div class="card-body">
                    <i class="bi bi-cart-x text-muted" style="font-size:4rem;"></i>
                    <h4 class="mt-3 fw-semibold">Your cart is empty</h4>
                    <p class="text-muted mb-4">Looks like you haven't added anything yet.</p>
                    <a href="{{ route('products.index') }}" class="btn btn-primary px-4">
                        <i class="bi bi-shop me-1"></i>Browse Products
                    </a>
                </div>
            </div>
        @else
            <div class="row g-4">

                {{-- ── Cart items (left column) ──────────────────────────────── --}}
                <div class="col-lg-8">
                    <div class="card shadow-sm border-0 rounded-3">
                        <div class="card-body p-0">
                            @foreach ($cart as $item)
                                <div class="cart-item d-flex align-items-start gap-3 p-3 border-bottom"
                                    data-product-id="{{ $item['product_id'] }}"
                                    x-data="imp007CartRow({{ $item['product_id'] }}, {{ $item['quantity'] }})"
                                    :class="{ 'imp007-removing': removing }">

                                    {{-- Product thumbnail placeholder --}}
                                    <div class="imp029-thumb-placeholder flex-shrink-0">
                                        <i class="bi bi-image"></i>
                                    </div>

                                    {{-- Product details --}}
                                    <div class="flex-grow-1 min-w-0">
                                        <div class="d-flex align-items-start justify-content-between gap-2">
                                            <div>
                                                @if ($item['slug'])
                                                    <a href="{{ route('products.show', $item['slug']) }}"
                                                        class="fw-semibold text-dark text-decoration-none">{{ $item['name'] }}</a>
                                                @else
                                                    <span class="fw-semibold">{{ $item['name'] }}</span>
                                                @endif
                                                <div class="text-muted small mt-1">
                                                    Unit Price: ${{ number_format($item['price'], 2) }}
                                                </div>
                                            </div>

                                            {{-- SC-004: Remove item --}}
                                            <form class="remove-form flex-shrink-0"
                                                action="{{ route('cart.destroy', $item['product_id']) }}" method="POST"
                                                x-on:submit.prevent="removeItem($el.closest('form'))">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-outline-danger btn-sm" :disabled="removing"
                                                    data-bs-toggle="tooltip" data-bs-title="Remove item">
                                                    <span x-show="!removing"><i class="bi bi-trash"></i></span>
                                                    <span x-show="removing" style="display:none"><span
                                                            class="imp007-spinner"></span></span>
                                                </button>
                                            </form>
                                        </div>

                                        {{-- SC-003: Qty stepper + line subtotal --}}
                                        <div class="d-flex align-items-center gap-3 mt-2 flex-wrap">
                                            <form class="qty-update-form d-flex align-items-center gap-2"
                                                action="{{ route('cart.update', $item['product_id']) }}" method="POST"
                                                x-on:submit.prevent="updateQty($el.closest('form'))">
                                                @csrf
                                                @method('PATCH')
                                                <div class="input-group input-group-sm" style="width:auto;">
                                                    <input type="number" name="quantity" value="{{ $item['quantity'] }}" min="1"
                                                        class="form-control imp029-qty-input qty-input"
                                                        data-product-id="{{ $item['product_id'] }}">
                                                    <button type="submit" class="btn btn-outline-secondary btn-sm"
                                                        :disabled="saving">
                                                        <span x-show="!saving && !saved">Update</span>
                                                        <span x-show="saving" style="display:none"><span
                                                                class="imp007-spinner"></span></span>
                                                        <span x-show="saved" style="display:none" class="qty-saved">✓</span>
                                                    </button>
                                                </div>
                                            </form>
                                            <span class="fw-semibold text-primary item-subtotal"
                                                id="subtotal-{{ $item['product_id'] }}">
                                                ${{ number_format($item['price'] * $item['quantity'], 2) }}
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>

                {{-- ── Order Summary (sticky right column) ──────────────────── --}}
                <div class="col-lg-4">
                    <div class="card shadow-sm border-0 rounded-3 imp029-summary-panel">
                        <div class="card-body p-4">
                            <h5 class="fw-bold mb-3">Order Summary</h5>

                            {{-- Subtotal row --}}
                            <div class="d-flex justify-content-between mb-2">
                                <span class="text-muted">Order Total</span>
                                <span class="fw-semibold">$<span
                                        id="order-total">{{ number_format($subtotal, 2) }}</span></span>
                            </div>

                            {{-- Discount / Grand Total --}}
                            @if ($discount > 0)
                                <div class="d-flex justify-content-between mb-2 text-success small">
                                    <span>Discount ({{ $coupon['code'] }})</span>
                                    <span>-$<span id="discount-amount">{{ number_format($discount, 2) }}</span></span>
                                </div>
                                <hr class="my-2">
                                <div class="d-flex justify-content-between fw-bold fs-5 mb-1">
                                    <span>Grand Total</span>
                                    <span class="text-primary">$<span id="grand-total">{{ number_format($total, 2) }}</span></span>
                                </div>
                            @else
                                <div id="grand-total-line" style="display:none;">
                                    <div class="d-flex justify-content-between mb-2 text-success small">
                                        <span>Discount (<span id="coupon-code-label"></span>)</span>
                                        <span>-$<span id="discount-amount">0.00</span></span>
                                    </div>
                                    <hr class="my-2">
                                    <div class="d-flex justify-content-between fw-bold fs-5 mb-1">
                                        <span>Grand Total</span>
                                        <span class="text-primary">$<span
                                                id="grand-total">{{ number_format($total, 2) }}</span></span>
                                    </div>
                                </div>
                            @endif

                            <hr class="my-3">

                            {{-- SC-005: Coupon section --}}
                            @if ($coupon)
                                <div class="d-flex align-items-center gap-2 mb-3">
                                    <i class="bi bi-tag-fill text-success"></i>
                                    <span class="small">Coupon <strong>{{ $coupon['code'] }}</strong> applied.</span>
                                </div>
                                <form class="coupon-remove-form" action="{{ route('cart.coupon.remove') }}" method="POST">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-outline-danger btn-sm w-100">Remove Coupon</button>
                                </form>
                            @else
                                <form class="coupon-apply-form d-flex gap-2 mb-3" action="{{ route('cart.coupon.apply') }}"
                                    method="POST">
                                    @csrf
                                    <input type="text" name="code" class="form-control form-control-sm" placeholder="Coupon code"
                                        value="{{ old('code') }}">
                                    <button type="submit" class="btn btn-outline-secondary btn-sm text-nowrap">Apply</button>
                                </form>
                            @endif

                            {{-- Checkout CTA --}}
                            <a href="{{ route('checkout.index') }}" class="btn btn-primary w-100 mt-1">
                                <i class="bi bi-lock me-1"></i>Proceed to Checkout
                            </a>
                        </div>
                    </div>
                </div>

            </div>{{-- /row --}}
        @endif

    </div>{{-- /fade-in wrapper --}}
@endsection

@push('scripts')
    <script>
        /* IMP-007: Alpine.js data components for cart micro-interactions */
        document.addEventListener('alpine:init', () => {

            Alpine.data('imp007ToastManager', () => ({
                toasts: [],
                show(detail) {
                    this.toasts.push(detail);
                    setTimeout(() => {
                        const i = this.toasts.findIndex(t => t.id === detail.id);
                        if (i > -1) this.toasts.splice(i, 1);
                    }, 2800);
                },
            }));

            Alpine.data('imp007CartRow', (productId, _initialQty) => ({
                removing: false,
                saving: false,
                saved: false,
                productId,

                removeItem(form) {
                    if (this.removing) return;
                    this.removing = true;
                    const token = document.querySelector('meta[name="csrf-token"]').content;
                    // Wait for CSS fade-out (300ms) then fetch
                    setTimeout(() => {
                        fetch(form.action, {
                            method: 'DELETE',
                            headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': token },
                        })
                            .then(r => r.json())
                            .then(json => {
                                const row = form.closest('[data-product-id]');
                                if (row) row.remove();
                                const tot = document.getElementById('order-total');
                                if (tot && json.order_total !== undefined) tot.textContent = json.order_total;
                                if (json.discount_amount !== undefined && parseFloat(json.discount_amount) > 0) {
                                    const disc = document.getElementById('discount-amount');
                                    if (disc) disc.textContent = json.discount_amount;
                                    const gt = document.getElementById('grand-total');
                                    if (gt) gt.textContent = json.grand_total;
                                    const gtLine = document.getElementById('grand-total-line');
                                    if (gtLine) gtLine.style.display = '';
                                }
                                window.dispatchEvent(new CustomEvent('imp007-toast', {
                                    detail: { id: Date.now(), type: 'success', msg: 'Item removed from cart' }
                                }));
                            })
                            .catch(() => { this.removing = false; });
                    }, 330);
                },

                updateQty(form) {
                    if (this.saving) return;
                    this.saving = true;
                    this.saved = false;
                    const input = form.querySelector('[name="quantity"]');
                    const quantity = parseInt(input.value);
                    const token = document.querySelector('meta[name="csrf-token"]').content;
                    fetch(form.action, {
                        method: 'PATCH',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': token,
                        },
                        body: JSON.stringify({ quantity }),
                    })
                        .then(r => r.json())
                        .then(json => {
                            this.saving = false;
                            const sub = document.getElementById('subtotal-' + this.productId);
                            if (sub && json.subtotal !== undefined) sub.textContent = '$' + json.subtotal;
                            const tot = document.getElementById('order-total');
                            if (tot && json.order_total !== undefined) tot.textContent = json.order_total;
                            if (json.discount_amount !== undefined && parseFloat(json.discount_amount) > 0) {
                                const disc = document.getElementById('discount-amount');
                                if (disc) disc.textContent = json.discount_amount;
                                const gt = document.getElementById('grand-total');
                                if (gt) gt.textContent = json.grand_total;
                                const gtLine = document.getElementById('grand-total-line');
                                if (gtLine) gtLine.style.display = '';
                            }
                            this.saved = true;
                            window.dispatchEvent(new CustomEvent('imp007-toast', {
                                detail: { id: Date.now(), type: 'success', msg: 'Cart updated' }
                            }));
                            setTimeout(() => { this.saved = false; }, 1500);
                        })
                        .catch(() => { this.saving = false; });
                },
            }));

        });
    </script>
@endpush