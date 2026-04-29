<nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm sticky-top">
    <div class="container">
        {{-- Brand --}}
        <a class="navbar-brand fw-bold text-primary d-flex align-items-center gap-2" href="{{ url('/') }}">
            <i class="bi bi-shop"></i> ShopName
        </a>

        {{-- Mobile toggle --}}
        <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#mainNav">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="mainNav">
            {{-- Left links --}}
            <ul class="navbar-nav me-auto">
                <li class="nav-item">
                    <a class="nav-link {{ request()->is('products*') ? 'active fw-semibold' : '' }}"
                        href="{{ route('products.index') }}">Shop</a>
                </li>
            </ul>

            {{-- Right: cart + user --}}
            @php $__navCartCount = array_sum(array_column(session('cart', []), 'quantity')); @endphp
            <ul class="navbar-nav align-items-center gap-2">
                {{-- Cart icon with badge --}}
                <li class="nav-item">
                    <a class="nav-link position-relative" href="{{ route('cart.index') }}">
                        <i class="bi bi-cart3 fs-5"></i>
                        <span id="navbar-cart-badge"
                            class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger{{ $__navCartCount > 0 ? '' : ' d-none' }}">{{ $__navCartCount > 0 ? $__navCartCount : '' }}</span>
                    </a>
                </li>

                @auth
                    {{-- User dropdown --}}
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle d-flex align-items-center gap-2" href="#"
                            data-bs-toggle="dropdown">
                            @if(auth()->user()->avatar)
                                <img src="{{ Storage::url(auth()->user()->avatar) }}" class="rounded-circle" width="28"
                                    height="28" style="object-fit:cover;" alt="">
                            @else
                                <div class="rounded-circle bg-primary text-white d-flex
                                                            align-items-center justify-content-center fw-bold"
                                    style="width:28px;height:28px;font-size:0.75rem;">
                                    {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                                </div>
                            @endif
                            {{ auth()->user()->name }}
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0">
                            <li><a class="dropdown-item" href="{{ route('dashboard') }}"><i
                                        class="bi bi-grid me-2"></i>Dashboard</a></li>
                            <li><a class="dropdown-item" href="{{ route('profile.show') }}"><i
                                        class="bi bi-person me-2"></i>Profile</a></li>
                            <li><a class="dropdown-item" href="{{ route('orders.index') }}"><i class="bi bi-bag me-2"></i>My
                                    Orders</a></li>
                            <li><a class="dropdown-item" href="{{ route('addresses.index') }}"><i
                                        class="bi bi-geo-alt me-2"></i>Addresses</a></li>
                            <li>
                                <hr class="dropdown-divider">
                            </li>
                            <li>
                                <form method="POST" action="{{ route('logout') }}">
                                    @csrf
                                    <button class="dropdown-item text-danger"><i
                                            class="bi bi-box-arrow-right me-2"></i>Logout</button>
                                </form>
                            </li>
                        </ul>
                    </li>
                @else
                    <li class="nav-item">
                        <a class="nav-link" href="{{ route('login') }}">Login</a>
                    </li>
                    <li class="nav-item">
                        <a class="btn btn-primary btn-sm px-3" href="{{ route('register') }}">Register</a>
                    </li>
                @endauth
            </ul>
        </div>
    </div>
</nav>