@extends('layouts.admin')

@section('title', '[DEMO] Shipping Simulator Sandbox')

@push('styles')
    <style>
        .demo-badge {
            background: #fbbf24;
            color: #78350f;
            font-size: .65rem;
            font-weight: 700;
            letter-spacing: .06em;
            padding: 2px 7px;
            border-radius: 4px;
            text-transform: uppercase;
        }

        .sim-step {
            display: flex;
            align-items: center;
            gap: .5rem;
        }

        .sim-step .step-dot {
            width: 14px;
            height: 14px;
            border-radius: 50%;
            background: #cbd5e1;
            flex-shrink: 0;
            border: 2px solid #94a3b8;
            transition: background .3s, border-color .3s;
        }

        .sim-step.active .step-dot {
            background: #3b82f6;
            border-color: #2563eb;
        }

        .sim-step.done .step-dot {
            background: #22c55e;
            border-color: #16a34a;
        }

        .sim-step.incident .step-dot {
            background: #ef4444;
            border-color: #dc2626;
        }

        .sim-connector {
            width: 2px;
            height: 18px;
            background: #e2e8f0;
            margin-left: 6px;
        }

        .sim-connector.done {
            background: #22c55e;
        }
    </style>
@endpush

@section('content')
    <div class="d-flex align-items-center gap-3 mb-4">
        <h1 class="h4 mb-0 fw-bold">Shipping Simulator</h1>
        <span class="demo-badge">[DEMO]</span>
        <span class="text-muted small">Sandbox only — no stock or revenue impact</span>
    </div>

    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="row g-4">
        {{-- ── Left: Create demo order ──────────────────────────── --}}
        <div class="col-lg-4">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-warning-subtle border-0">
                    <span class="fw-semibold"><i class="bi bi-play-circle me-1"></i> Start New Demo</span>
                    <span class="demo-badge ms-2">[DEMO]</span>
                </div>
                <div class="card-body">
                    <form method="POST" action="{{ route('admin.demo.simulate') }}">
                        @csrf
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Product</label>
                            <select name="product_id" class="form-select @error('product_id') is-invalid @enderror"
                                required>
                                <option value="">— Select a product —</option>
                                @foreach ($products as $p)
                                    <option value="{{ $p->id }}" {{ old('product_id') == $p->id ? 'selected' : '' }}>
                                        {{ $p->name }} — ${{ number_format($p->price, 2) }}
                                    </option>
                                @endforeach
                            </select>
                            @error('product_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="mb-4">
                            <label class="form-label fw-semibold">Quantity</label>
                            <input type="number" name="quantity"
                                class="form-control @error('quantity') is-invalid @enderror"
                                value="{{ old('quantity', 1) }}" min="1" max="99" required>
                            @error('quantity')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="alert alert-info py-2 small mb-3">
                            <i class="bi bi-info-circle me-1"></i>
                            This creates a <strong>[DEMO]</strong> order. Stock is <em>not</em> decremented,
                            revenue is <em>not</em> counted. Stripe is in sandbox mode.
                        </div>
                        <button type="submit" class="btn btn-warning w-100 fw-semibold">
                            <i class="bi bi-rocket-takeoff me-1"></i> Launch Demo Simulation
                        </button>
                    </form>
                </div>
            </div>
        </div>

        {{-- ── Right: Recent demo orders ───────────────────────── --}}
        <div class="col-lg-8">
            <div class="card shadow-sm border-0">
                <div class="card-header d-flex align-items-center justify-content-between border-0">
                    <span class="fw-semibold"><i class="bi bi-clock-history me-1"></i> Recent Demo Orders</span>
                    <span class="demo-badge">[DEMO]</span>
                </div>
                <div class="card-body p-0">
                    @if ($recentDemoOrders->isEmpty())
                        <p class="text-muted p-4 mb-0">No demo orders yet. Start a simulation above.</p>
                    @else
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>#</th>
                                        <th>User</th>
                                        <th>Total</th>
                                        <th>Simulation Status</th>
                                        <th>Started</th>
                                        <th></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($recentDemoOrders as $demo)
                                        <tr x-data="demoRow('{{ route('admin.demo.status', $demo->id) }}', '{{ $demo->ship_sim_status }}')"
                                            x-init="startPolling()">
                                            <td>
                                                <span class="fw-semibold">#{{ $demo->id }}</span>
                                                <span class="demo-badge ms-1">[DEMO]</span>
                                            </td>
                                            <td>{{ $demo->user?->name ?? 'N/A' }}</td>
                                            <td>${{ number_format($demo->total, 2) }}</td>
                                            <td>
                                                <span x-text="statusLabel(currentStatus)" :class="statusClass(currentStatus)"
                                                    class="badge"></span>
                                            </td>
                                            <td class="text-muted small">
                                                {{ $demo->ship_sim_started_at?->diffForHumans() ?? '—' }}
                                            </td>
                                            <td>
                                                <a href="{{ route('admin.orders.show', $demo->id) }}"
                                                    class="btn btn-sm btn-outline-secondary">View</a>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- Simulation legend --}}
    <div class="card mt-4 shadow-sm border-0">
        <div class="card-body">
            <h6 class="fw-bold mb-3"><i class="bi bi-diagram-3 me-1"></i> Simulation Flow <span
                    class="demo-badge">[DEMO]</span></h6>
            <div class="d-flex flex-wrap gap-4 align-items-start">
                @php
                    $steps = [
                        ['payment_confirmed', 'Payment Confirmed', 'bi-check-circle-fill text-success', 'Trigger'],
                        ['preparing', 'Preparing Goods', 'bi-box-seam text-primary', '5–10 s'],
                        ['picked_up', 'Handed to Courier', 'bi-truck text-primary', '5–15 s'],
                        ['in_transit', 'On the Way', 'bi-geo-alt-fill text-primary', '10–20 s'],
                        ['arrived', 'Arrived', 'bi-house-check-fill text-primary', '5–10 s'],
                        ['delivered', 'Delivered (90%)', 'bi-bag-check-fill text-success', '—'],
                        ['incident', 'Incident / Refund (10%)', 'bi-exclamation-triangle-fill text-danger', '—'],
                    ];
                @endphp
                @foreach ($steps as [$key, $label, $icon, $delay])
                    <div class="text-center" style="min-width:90px">
                        <i class="bi {{ $icon }} fs-4 mb-1"></i>
                        <div class="small fw-semibold">{{ $label }}</div>
                        <div class="text-muted" style="font-size:.7rem">{{ $delay }}</div>
                    </div>
                    @if (!$loop->last && $loop->index < 4)
                        <div class="align-self-center text-muted"><i class="bi bi-arrow-right"></i></div>
                    @endif
                    @if ($loop->index === 4)
                        <div class="align-self-center text-muted"><i class="bi bi-arrow-right"></i></div>
                    @endif
                @endforeach
            </div>
            <p class="text-muted small mt-3 mb-0">
                <i class="bi bi-bell me-1"></i>
                <strong>Notifications sent:</strong>
                Payment Confirmed (Admin + User), Arrived (User), Incident (Admin + User).
                Intermediate states show progress bar only — no notification.
            </p>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        function demoRow(statusUrl, initialStatus) {
            const TERMINAL = ['delivered', 'incident'];
            const LABELS = {
                payment_confirmed: '[DEMO] Payment Confirmed',
                preparing: '[DEMO] Preparing Goods',
                picked_up: '[DEMO] Handed to Courier',
                in_transit: '[DEMO] On the Way',
                arrived: '[DEMO] Arrived',
                delivered: '[DEMO] Delivered',
                incident: '[DEMO] Incident / Refund',
            };
            const CLASSES = {
                payment_confirmed: 'bg-success',
                preparing: 'bg-info text-dark',
                picked_up: 'bg-primary',
                in_transit: 'bg-primary',
                arrived: 'bg-success',
                delivered: 'bg-success',
                incident: 'bg-danger',
            };

            return {
                currentStatus: initialStatus,
                timer: null,

                startPolling() {
                    if (TERMINAL.includes(this.currentStatus)) return;
                    this.timer = setInterval(() => this.poll(), 3000);
                },

                async poll() {
                    try {
                        const res = await fetch(statusUrl);
                        if (!res.ok) return;
                        const data = await res.json();
                        this.currentStatus = data.ship_sim_status ?? this.currentStatus;
                        if (TERMINAL.includes(this.currentStatus)) {
                            clearInterval(this.timer);
                        }
                    } catch (_) { }
                },

                statusLabel(s) { return LABELS[s] ?? s; },
                statusClass(s) { return CLASSES[s] ?? 'bg-secondary'; },
            };
        }
    </script>
@endpush