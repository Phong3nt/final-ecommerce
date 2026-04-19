<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Your Cart</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <!-- IMP-007: Alpine.js micro-interactions -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.14.1/dist/cdn.min.js"></script>
    <style>
        /* IMP-007: Cart page micro-interaction styles */
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
    </style>
</head>

<body>
    @include('partials.toast')
    <!-- IMP-007: Toast notification area -->
    <div class="imp007-toast-area" x-data="imp007ToastManager()" x-on:imp007-toast.window="show($event.detail)">
        <template x-for="t in toasts" :key="t.id">
            <div class="imp007-toast" :class="'imp007-toast--' + t.type" x-text="t.msg" x-transition></div>
        </template>
    </div>

    <h1>Your Cart</h1>

    <a href="{{ route('products.index') }}">&larr; Continue Shopping</a>

    @if ($errors->has('coupon'))
        <p class="alert-error">{{ $errors->first('coupon') }}</p>
    @endif

    @if (empty($cart))
        <p class="cart-empty">Your cart is empty.</p>
    @else
        <table class="cart-table">
            <thead>
                <tr>
                    <th>Product</th>
                    <th>Unit Price</th>
                    <th>Qty</th>
                    <th>Subtotal</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($cart as $item)
                    <tr class="cart-item" data-product-id="{{ $item['product_id'] }}"
                        x-data="imp007CartRow({{ $item['product_id'] }}, {{ $item['quantity'] }})"
                        :class="{ 'imp007-removing': removing }">
                        <td>
                            @if ($item['slug'])
                                <a href="{{ route('products.show', $item['slug']) }}">{{ $item['name'] }}</a>
                            @else
                                {{ $item['name'] }}
                            @endif
                        </td>
                        <td class="unit-price">${{ number_format($item['price'], 2) }}</td>
                        <td class="item-qty">
                            {{-- SC-003: quantity update form (AJAX or regular submit) --}}
                            <form class="qty-update-form" action="{{ route('cart.update', $item['product_id']) }}" method="POST"
                                x-on:submit.prevent="updateQty($el.closest('form'))">
                                @csrf
                                @method('PATCH')
                                <input type="number" name="quantity" value="{{ $item['quantity'] }}" min="1" class="qty-input"
                                    data-product-id="{{ $item['product_id'] }}">
                                <button type="submit" class="qty-update-btn" :disabled="saving">
                                    <span x-show="!saving && !saved">Update</span>
                                    <span x-show="saving" style="display:none"><span class="imp007-spinner"></span></span>
                                    <span x-show="saved" style="display:none" class="qty-saved">✓</span>
                                </button>
                            </form>
                        </td>
                        <td class="item-subtotal" id="subtotal-{{ $item['product_id'] }}">
                            ${{ number_format($item['price'] * $item['quantity'], 2) }}</td>
                        <td class="item-actions">
                            {{-- SC-004: remove item form (AJAX or regular submit) --}}
                            <form class="remove-form" action="{{ route('cart.destroy', $item['product_id']) }}" method="POST"
                                x-on:submit.prevent="removeItem($el.closest('form'))">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="remove-btn" :disabled="removing">
                                    <span x-show="!removing">Remove</span>
                                    <span x-show="removing" style="display:none"><span class="imp007-spinner"></span></span>
                                </button>
                            </form>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <p class="order-total">Order Total: $<span id="order-total">{{ number_format($subtotal, 2) }}</span></p>

        {{-- SC-005: coupon discount --}}
        @if($discount > 0)
            <p class="order-discount" id="discount-line">
                Discount ({{ $coupon['code'] }}): -$<span id="discount-amount">{{ number_format($discount, 2) }}</span>
            </p>
            <p class="grand-total"><strong>Grand Total: $<span id="grand-total">{{ number_format($total, 2) }}</span></strong>
            </p>
        @else
            <p class="grand-total" id="grand-total-line" style="display:none;">
                Discount (<span id="coupon-code-label"></span>): -$<span id="discount-amount">0.00</span><br>
                <strong>Grand Total: $<span id="grand-total">{{ number_format($total, 2) }}</span></strong>
            </p>
        @endif

        {{-- SC-005: coupon apply form --}}
        <div class="coupon-section">
            @if($coupon)
                <p>Coupon <strong>{{ $coupon['code'] }}</strong> applied.</p>
                <form class="coupon-remove-form" action="{{ route('cart.coupon.remove') }}" method="POST">
                    @csrf
                    @method('DELETE')
                    <button type="submit">Remove Coupon</button>
                </form>
            @else
                <form class="coupon-apply-form" action="{{ route('cart.coupon.apply') }}" method="POST">
                    @csrf
                    <input type="text" name="code" placeholder="Coupon code" value="{{ old('code') }}">
                    <button type="submit">Apply Coupon</button>
                </form>
            @endif
        </div>
    @endif

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
                                const tr = form.closest('tr');
                                if (tr) tr.remove();
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
</body>

</html>