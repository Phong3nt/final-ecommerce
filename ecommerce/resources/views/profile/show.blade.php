@extends('layouts.app')

@section('title', 'My Profile — E-Commerce')

@section('content')
    <div x-data x-init="$el.classList.add('fade-in')">

        {{-- Page header --}}
        <div class="mb-4">
            <h1 class="h3 fw-bold mb-1">My Profile</h1>
            <p class="text-muted mb-0">Update your name, email address and avatar.</p>
        </div>

        <div class="row justify-content-center">
            <div class="col-lg-7 col-xl-6">
                <div class="card shadow-sm border-0 rounded-3">
                    <div class="card-body p-4">

                        {{-- Avatar preview --}}
                        <div class="text-center mb-4">
                            @if ($user->avatar)
                                <img src="{{ Storage::url($user->avatar) }}" alt="Avatar" class="rounded-circle border"
                                    width="88" height="88" style="object-fit:cover;">
                            @else
                                <div class="rounded-circle bg-primary bg-opacity-10 d-inline-flex
                                                                                        align-items-center justify-content-center border"
                                    style="width:88px;height:88px;">
                                    <i class="bi bi-person fs-2 text-primary"></i>
                                </div>
                            @endif
                            <p class="text-muted small mt-2 mb-0">{{ $user->email }}</p>
                        </div>

                        {{-- Profile form --}}
                        <form action="{{ route('profile.update') }}" method="POST" enctype="multipart/form-data"
                            x-data="{ loading: false }" @submit="loading = true">
                            @csrf
                            @method('PUT')

                            {{-- Name --}}
                            <div class="mb-3">
                                <label for="name" class="form-label fw-semibold">Full Name</label>
                                <input type="text" id="name" name="name"
                                    class="form-control @error('name') is-invalid @enderror"
                                    value="{{ old('name', $user->name) }}" required>
                                @error('name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            {{-- Email --}}
                            <div class="mb-3">
                                <label for="email" class="form-label fw-semibold">Email Address</label>
                                <input type="email" id="email" name="email"
                                    class="form-control @error('email') is-invalid @enderror"
                                    value="{{ old('email', $user->email) }}" required>
                                @error('email')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            {{-- Avatar upload --}}
                            <div class="mb-4">
                                <label for="avatar" class="form-label fw-semibold">
                                    Avatar
                                    <span class="text-muted fw-normal">(jpg / png, max 2 MB)</span>
                                </label>
                                <input type="file" id="avatar" name="avatar" accept="image/jpeg,image/png"
                                    class="form-control @error('avatar') is-invalid @enderror">
                                @error('avatar')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            {{-- Submit --}}
                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary px-4" :disabled="loading">
                                    <span x-show="loading" class="spinner-border spinner-border-sm me-2"
                                        role="status"></span>
                                    <span x-text="loading ? 'Saving…' : 'Save Changes'">Save Changes</span>
                                </button>
                            </div>

                        </form>
                    </div>
                </div>
            </div>
        </div>

        {{-- IMP-035 / IMP-041: Card Vault — saved payment methods + inline Add New Card --}}
        <div class="row justify-content-center mt-4">
            <div class="col-lg-7 col-xl-6" x-data="{
                        open: false,
                        loading: false,
                        errorMsg: '',
                        pmId: '',
                        stripeObj: null,
                        elementsObj: null,
                        async openForm() {
                            this.open = true;
                            this.loading = false;
                            this.errorMsg = '';
                            this.pmId = '';
                            await this.$nextTick();
                            document.getElementById('setup-element').innerHTML = '';
                            await this.initStripe();
                        },
                        closeForm() {
                            this.open = false;
                            this.errorMsg = '';
                            this.loading = false;
                            this.stripeObj = null;
                            this.elementsObj = null;
                        },
                        async initStripe() {
                            const key = '{{ config('services.stripe.key') }}';
                            if (!key) { this.errorMsg = 'Payment is currently unavailable.'; return; }
                            this.stripeObj = Stripe(key);
                            try {
                                const resp = await fetch('{{ route('payment-methods.setup-intent') }}', {
                                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                                    credentials: 'same-origin',
                                });
                                const data = await resp.json();
                                if (!data.client_secret) { this.errorMsg = 'Could not load payment form.'; return; }
                                this.elementsObj = this.stripeObj.elements({ clientSecret: data.client_secret });
                                const el = this.elementsObj.create('payment');
                                el.mount('#setup-element');
                            } catch (e) {
                                this.errorMsg = 'Could not load payment form.';
                            }
                        },
                        async saveCard() {
                            if (!this.stripeObj || !this.elementsObj) return;
                            this.loading = true;
                            this.errorMsg = '';
                            const { error, setupIntent } = await this.stripeObj.confirmSetup({
                                elements: this.elementsObj,
                                confirmParams: {},
                                redirect: 'if_required',
                            });
                            if (error) {
                                this.errorMsg = error.message;
                                this.loading = false;
                                return;
                            }
                            this.pmId = setupIntent.payment_method;
                            await this.$nextTick();
                            this.$refs.pmForm.submit();
                        },
                    }">

                {{-- Saved cards list --}}
                <div class="card shadow-sm border-0 rounded-3">
                    <div
                        class="card-header bg-white border-bottom py-3 px-4 d-flex justify-content-between align-items-center">
                        <h2 class="h6 fw-semibold mb-0">
                            <i class="bi bi-credit-card me-1"></i> Saved Cards
                        </h2>
                        <button type="button" class="btn btn-outline-primary btn-sm" @click="openForm()" x-show="!open">
                            <i class="bi bi-plus-lg me-1"></i>Add Card
                        </button>
                    </div>
                    <div class="card-body p-0">
                        @if ($user->savedPaymentMethods->isEmpty())
                            <p class="text-muted text-center py-4 mb-0 small" x-show="!open">No saved cards yet.</p>
                        @else
                            <ul class="list-group list-group-flush">
                                @foreach ($user->savedPaymentMethods as $card)
                                    <li class="list-group-item d-flex align-items-center gap-3 px-4 py-3">
                                        <i class="bi bi-credit-card fs-5 text-secondary flex-shrink-0"></i>
                                        <div class="flex-grow-1 min-w-0">
                                            <span class="fw-semibold">{{ $card->display_label }}</span>
                                            <span class="text-muted small ms-2">exp {{ $card->expiry }}</span>
                                            @if ($card->is_default)
                                                <span class="badge bg-primary ms-2">Default</span>
                                            @endif
                                        </div>
                                        <div class="d-flex gap-2 flex-shrink-0">
                                            @unless ($card->is_default)
                                                <form action="{{ route('payment-methods.setDefault', $card) }}" method="POST">
                                                    @csrf
                                                    @method('PATCH')
                                                    <button type="submit" class="btn btn-outline-secondary btn-sm">
                                                        Set Default
                                                    </button>
                                                </form>
                                            @endunless
                                            <form action="{{ route('payment-methods.destroy', $card) }}" method="POST" x-data
                                                @submit.prevent="if(confirm('Remove this card from your account?')) $el.submit()">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-outline-danger btn-sm"
                                                    aria-label="Remove card">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </li>
                                @endforeach
                            </ul>
                        @endif
                    </div>
                </div>

                {{-- IMP-041: Inline Add New Card form (no modal — avoids Alpine scope issues) --}}
                <div x-show="open" x-cloak class="card shadow-sm border-0 rounded-3 mt-3">
                    <div class="card-header bg-white border-bottom py-3 px-4">
                        <h5 class="mb-0 fw-semibold">
                            <i class="bi bi-credit-card me-1"></i> Add New Card
                        </h5>
                    </div>
                    <div class="card-body p-4">
                        <p class="text-muted small mb-3">
                            <i class="bi bi-shield-lock me-1 text-success"></i>
                            Your card details are handled securely by Stripe and never touch our server.
                        </p>
                        <div id="setup-element" class="mb-3"></div>
                        <div x-show="errorMsg" class="alert alert-danger small py-2" x-text="errorMsg"></div>
                        <div class="d-flex gap-2 justify-content-end mt-3">
                            <button type="button" class="btn btn-outline-secondary" @click="closeForm()">Cancel</button>
                            <button type="button" class="btn btn-primary" :disabled="loading" @click="saveCard()">
                                <span x-show="loading" class="spinner-border spinner-border-sm me-1" role="status"></span>
                                <span x-text="loading ? 'Saving…' : 'Save Card'">Save Card</span>
                            </button>
                        </div>
                    </div>
                </div>

                {{-- Hidden form: POST confirmed PM ID to server --}}
                <form x-ref="pmForm" action="{{ route('payment-methods.store') }}" method="POST" style="display:none;">
                    @csrf
                    <input type="hidden" name="payment_method_id" x-model="pmId">
                </form>

            </div>
        </div>

    </div>
@endsection

@push('scripts')
    <script src="https://js.stripe.com/v3/"></script>
@endpush