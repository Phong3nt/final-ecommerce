@extends('layouts.admin')
@include('partials.toast')

@section('title', 'Admin - Coupons')
@section('page-title', 'Coupons')

@section('content')
    <div x-data x-init="$el.classList.add('fade-in')" id="coupon-admin-root">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h4 class="mb-0">Coupons</h4>
            <a href="{{ route('admin.coupons.create') }}" class="btn btn-primary btn-sm">
                <i class="bi bi-plus-lg me-1"></i> New Coupon
            </a>
        </div>

        <div class="card shadow-sm border-0 rounded-3 mb-4">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="mb-0">Generate Dynamic Coupon Templates</h5>
                    <div class="d-flex gap-2">
                        <button class="btn btn-outline-dark btn-sm js-preset" data-preset="new-user-2" type="button">POST preset: New user 2</button>
                        <button class="btn btn-outline-dark btn-sm js-preset" data-preset="summer-autumn-2" type="button">POST preset: Summer/Autumn 2</button>
                        <button class="btn btn-outline-dark btn-sm js-preset" data-preset="categories-2" type="button">POST preset: Any categories 2</button>
                    </div>
                </div>

                <div class="row g-3 align-items-end mb-3">
                    <div class="col-md-3">
                        <label class="form-label mb-1">Generation Case</label>
                        <select class="form-select" id="generation_case_switch">
                            <option value="new_user">New User (fixed + percent)</option>
                            <option value="seasons">Season (Summer + Autumn)</option>
                            <option value="categories">Categories (2 templates)</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label mb-1">Expire Preset</label>
                        <select class="form-select" id="global_expiry_preset">
                            <option value="week">1 week</option>
                            <option value="month">1 month</option>
                            <option value="year" selected>1 year</option>
                            <option value="fixed_day">Fixed day</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label mb-1">Fixed Expire Day</label>
                        <input class="form-control" type="date" id="global_fixed_expires_at">
                    </div>
                    <div class="col-md-1">
                        <label class="form-label mb-1">Uses</label>
                        <input class="form-control" type="number" min="1" id="global_uses_per_user" value="2">
                    </div>
                    <div class="col-md-1">
                        <label class="form-label mb-1">Min Order</label>
                        <input class="form-control" type="number" min="1" step="0.01" id="global_min_order" value="1">
                    </div>
                    <div class="col-md-1">
                        <div class="form-check mt-4">
                            <input class="form-check-input" type="checkbox" id="global_activate_now" checked>
                            <label class="form-check-label" for="global_activate_now">Active</label>
                        </div>
                    </div>
                </div>

                <div class="border rounded p-3 js-case-panel" data-case="new_user">
                    <h6 class="mb-3">New User Template Inputs</h6>
                    <div class="row g-2">
                        <div class="col-md-6">
                            <input class="form-control mb-2" id="nu_percent_name" value="New User {discount} Welcome" placeholder="Percent name">
                            <input class="form-control mb-2" id="nu_percent_desc" value="First-time user gift: {discount} off for your first year." placeholder="Percent description">
                            <div class="row g-2">
                                <div class="col"><input class="form-control" id="nu_percent_prefix" value="NUPCT" placeholder="Code prefix"></div>
                                <div class="col"><input class="form-control" type="number" min="0.01" step="0.01" id="nu_percent_value" value="5" placeholder="Value"></div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <input class="form-control mb-2" id="nu_fixed_name" value="New User {discount} Welcome" placeholder="Fixed name">
                            <input class="form-control mb-2" id="nu_fixed_desc" value="First-time user gift: {discount} off for your first year." placeholder="Fixed description">
                            <div class="row g-2">
                                <div class="col"><input class="form-control" id="nu_fixed_prefix" value="NUFIX" placeholder="Code prefix"></div>
                                <div class="col"><input class="form-control" type="number" min="0.01" step="0.01" id="nu_fixed_value" value="5" placeholder="Value"></div>
                            </div>
                        </div>
                    </div>
                    <button class="btn btn-primary btn-sm mt-3" type="button" id="btn-generate-new-user">Generate New User Templates</button>
                </div>

                <div class="border rounded p-3 js-case-panel d-none" data-case="seasons">
                    <h6 class="mb-3">Season Inputs (Summer + Autumn only)</h6>
                    <div class="row g-2">
                        <div class="col-md-3"><input class="form-control" type="number" id="season_year" value="{{ now()->year }}" placeholder="Year"></div>
                        <div class="col-md-3"><input class="form-control" id="season_name" value="{season} {year} Festival" placeholder="Name template"></div>
                        <div class="col-md-3"><input class="form-control" id="season_desc" value="Save {discount} in {season} {year}." placeholder="Description template"></div>
                        <div class="col-md-1"><input class="form-control" id="season_prefix" value="SEA" placeholder="Prefix"></div>
                        <div class="col-md-1">
                            <select class="form-select" id="season_type"><option value="percent">percent</option><option value="fixed">fixed</option></select>
                        </div>
                        <div class="col-md-1"><input class="form-control" type="number" min="0.01" step="0.01" id="season_value" value="12" placeholder="Value"></div>
                    </div>
                    <button class="btn btn-primary btn-sm mt-3" type="button" id="btn-generate-seasons">Generate Summer + Autumn Templates</button>
                </div>

                <div class="border rounded p-3 js-case-panel d-none" data-case="categories">
                    <h6 class="mb-3">Category Inputs (2 templates)</h6>
                    <div class="row g-2">
                        <div class="col-md-4"><input class="form-control" id="cat_name" value="{category} Saver" placeholder="Name template"></div>
                        <div class="col-md-4"><input class="form-control" id="cat_desc" value="Special {category} promotion: {discount}." placeholder="Description template"></div>
                        <div class="col-md-1"><input class="form-control" id="cat_prefix" value="CAT" placeholder="Prefix"></div>
                        <div class="col-md-1">
                            <select class="form-select" id="cat_type"><option value="percent">percent</option><option value="fixed" selected>fixed</option></select>
                        </div>
                        <div class="col-md-2"><input class="form-control" type="number" min="0.01" step="0.01" id="cat_value" value="8" placeholder="Value"></div>
                    </div>
                    <label class="form-label small mt-2">Pick up to 2 categories (leave blank = first 2)</label>
                    <select class="form-select" id="cat_ids" multiple size="5">
                        @foreach($categories as $category)
                            <option value="{{ $category->id }}">{{ $category->name }}</option>
                        @endforeach
                    </select>
                    <button class="btn btn-primary btn-sm mt-3" type="button" id="btn-generate-categories">Generate Category Templates</button>
                </div>

                <div class="alert alert-info mt-3 mb-0 py-2 d-none" id="ajax-status"></div>
            </div>
        </div>

        <div class="card shadow-sm border-0 rounded-3" id="coupon-table-wrap">
            <div class="card-body p-0">
                <div class="p-3 border-bottom d-flex flex-wrap gap-2 align-items-center">
                    <strong>Coupon Actions:</strong>
                    <select class="form-select form-select-sm" style="max-width:180px" id="coupon_bulk_action">
                        <option value="">Select action</option>
                        <option value="activate">Activate selected</option>
                        <option value="deactivate">Deactivate selected</option>
                        <option value="delete">Delete selected</option>
                    </select>
                    <button class="btn btn-sm btn-dark" type="button" id="coupon_apply_bulk" disabled>Apply</button>
                    <span class="text-muted small" id="coupon_selected_count">0 selected</span>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th><input type="checkbox" id="coupon_select_all"></th>
                                <th>Code</th>
                                <th>Name</th>
                                <th>Description</th>
                                <th>Type</th>
                                <th>Value</th>
                                <th>Min Order</th>
                                <th>Usage Limit</th>
                                <th>Times Used</th>
                                <th>Expires</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($coupons as $coupon)
                                <tr data-coupon-id="{{ $coupon->id }}">
                                    <td><input type="checkbox" class="coupon-select" value="{{ $coupon->id }}"></td>
                                    <td><strong>{{ $coupon->code }}</strong></td>
                                    <td>{{ $coupon->name ?? '-' }}</td>
                                    <td class="text-muted" style="max-width: 280px;">{{ $coupon->description ? \Illuminate\Support\Str::limit($coupon->description, 100) : '-' }}</td>
                                    <td>{{ $coupon->type === 'percent' ? '%' : 'Fixed' }}</td>
                                    <td>{{ $coupon->type === 'percent' ? $coupon->value . '%' : '$' . number_format($coupon->value, 2) }}</td>
                                    <td>{{ '$' . number_format($coupon->min_order_amount, 2) }}</td>
                                    <td>{{ $coupon->usage_limit ?? 'infinite' }}</td>
                                    <td>{{ $coupon->times_used }}</td>
                                    <td>{{ $coupon->expires_at ? $coupon->expires_at->format('Y-m-d') : '-' }}</td>
                                    <td>
                                        <span class="badge coupon-status {{ $coupon->is_active ? 'bg-success' : 'bg-danger' }}">{{ $coupon->is_active ? 'Active' : 'Inactive' }}</span>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="11" class="text-center text-muted py-4">No coupons yet.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="mt-3">{{ $coupons->links() }}</div>

        <div class="card shadow-sm border-0 rounded-3 mt-4" id="template-table-wrap">
            <div class="card-body p-0">
                <div class="p-3 border-bottom d-flex flex-wrap gap-2 align-items-center">
                    <strong>Template Actions:</strong>
                    <select class="form-select form-select-sm" style="max-width:200px" id="template_bulk_action">
                        <option value="">Select action</option>
                        <option value="activate">Activate + assign</option>
                        <option value="deactivate">Deactivate</option>
                        <option value="assign">Assign only</option>
                    </select>
                    <button class="btn btn-sm btn-dark" type="button" id="template_apply_bulk" disabled>Apply</button>
                    <span class="text-muted small" id="template_selected_count">0 selected</span>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th><input type="checkbox" id="template_select_all"></th>
                                <th>Template</th>
                                <th>Scope</th>
                                <th>Discount</th>
                                <th>Expiry</th>
                                <th>Issued</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                        @forelse($templates as $template)
                            <tr data-template-id="{{ $template->id }}">
                                <td><input type="checkbox" class="template-select" value="{{ $template->id }}"></td>
                                <td>
                                    <div class="fw-semibold">{{ $template->name_template }}</div>
                                    <div class="text-muted small">{{ \Illuminate\Support\Str::limit($template->description_template ?? '', 90) }}</div>
                                </td>
                                <td>
                                    {{ $template->scope }}
                                    @if($template->scope === 'seasonal' && $template->season)
                                        <div class="small text-muted">{{ ucfirst($template->season) }} {{ $template->season_year }}</div>
                                    @endif
                                    @if($template->scope === 'category' && $template->category)
                                        <div class="small text-muted">{{ $template->category->name }}</div>
                                    @endif
                                </td>
                                <td>{{ $template->type === 'percent' ? $template->value . '%' : '$' . number_format($template->value, 2) }}</td>
                                <td>
                                    @if($template->expiry_mode === 'duration_days')
                                        {{ $template->expiry_days }} days since assigned
                                    @elseif($template->expiry_mode === 'fixed_date')
                                        {{ $template->fixed_expires_at?->format('Y-m-d') }}
                                    @else
                                        -
                                    @endif
                                </td>
                                <td>{{ $template->quantity_issued }}{{ $template->quantity_limit ? ' / ' . $template->quantity_limit : ' / infinite' }}</td>
                                <td>
                                    <span class="badge template-status {{ $template->is_active ? 'bg-success' : 'bg-secondary' }}">{{ $template->is_active ? 'Active' : 'Inactive' }}</span>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="text-center text-muted py-4">No templates yet.</td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="mt-3">{{ $templates->links() }}</div>
    </div>
@endsection

@push('scripts')
    <script>
        (function () {
            const csrf = '{{ csrf_token() }}';
            const statusBox = document.getElementById('ajax-status');

            const showStatus = (message, ok = true) => {
                statusBox.classList.remove('d-none', 'alert-success', 'alert-danger', 'alert-info');
                statusBox.classList.add(ok ? 'alert-success' : 'alert-danger');
                statusBox.textContent = message;
            };

            const requestJson = async (url, method, payload) => {
                const response = await fetch(url, {
                    method,
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrf,
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify(payload)
                });
                const data = await response.json();
                if (!response.ok || data.ok === false) {
                    throw new Error(data.message || 'Request failed.');
                }
                return data;
            };

            const refreshTables = async () => {
                const html = await fetch(window.location.pathname, { headers: { 'X-Requested-With': 'XMLHttpRequest' } }).then(r => r.text());
                const parser = new DOMParser();
                const doc = parser.parseFromString(html, 'text/html');
                const nextCouponWrap = doc.querySelector('#coupon-table-wrap');
                const nextTemplateWrap = doc.querySelector('#template-table-wrap');
                if (nextCouponWrap) {
                    document.querySelector('#coupon-table-wrap').innerHTML = nextCouponWrap.innerHTML;
                }
                if (nextTemplateWrap) {
                    document.querySelector('#template-table-wrap').innerHTML = nextTemplateWrap.innerHTML;
                }
                wireBulkControls();
            };

            const readShared = () => ({
                expiry_preset: document.getElementById('global_expiry_preset').value,
                fixed_expires_at: document.getElementById('global_fixed_expires_at').value || null,
                uses_per_user: Number(document.getElementById('global_uses_per_user').value || 2),
                min_order_amount: Number(document.getElementById('global_min_order').value || 1),
                activate_now: document.getElementById('global_activate_now').checked ? 1 : 0,
            });

            const generationSwitch = document.getElementById('generation_case_switch');
            generationSwitch.addEventListener('change', function () {
                document.querySelectorAll('.js-case-panel').forEach(panel => {
                    panel.classList.toggle('d-none', panel.dataset.case !== generationSwitch.value);
                });
            });

            document.querySelectorAll('.js-preset').forEach(btn => {
                btn.addEventListener('click', async function () {
                    try {
                        const data = await requestJson('{{ route('admin.coupons.templates.presets.generate', ['preset' => 'PRESET_KEY']) }}'.replace('PRESET_KEY', btn.dataset.preset), 'POST', {});
                        showStatus(data.message, true);
                        await refreshTables();
                    } catch (e) {
                        showStatus(e.message, false);
                    }
                });
            });

            document.getElementById('btn-generate-new-user').addEventListener('click', async function () {
                const shared = readShared();
                const payload = {
                    generation_case: 'new_user',
                    ...shared,
                    new_user_percent_name_template: document.getElementById('nu_percent_name').value,
                    new_user_percent_description_template: document.getElementById('nu_percent_desc').value,
                    new_user_percent_code_prefix: document.getElementById('nu_percent_prefix').value,
                    new_user_percent_value: Number(document.getElementById('nu_percent_value').value),
                    new_user_fixed_name_template: document.getElementById('nu_fixed_name').value,
                    new_user_fixed_description_template: document.getElementById('nu_fixed_desc').value,
                    new_user_fixed_code_prefix: document.getElementById('nu_fixed_prefix').value,
                    new_user_fixed_value: Number(document.getElementById('nu_fixed_value').value),
                };
                try {
                    const data = await requestJson('{{ route('admin.coupons.templates.generate') }}', 'POST', payload);
                    showStatus(data.message, true);
                    await refreshTables();
                } catch (e) {
                    showStatus(e.message, false);
                }
            });

            document.getElementById('btn-generate-seasons').addEventListener('click', async function () {
                const shared = readShared();
                const payload = {
                    generation_case: 'seasons',
                    ...shared,
                    season_year: Number(document.getElementById('season_year').value),
                    season_name_template: document.getElementById('season_name').value,
                    season_description_template: document.getElementById('season_desc').value,
                    season_code_prefix: document.getElementById('season_prefix').value,
                    season_discount_type: document.getElementById('season_type').value,
                    season_discount_value: Number(document.getElementById('season_value').value),
                };
                try {
                    const data = await requestJson('{{ route('admin.coupons.templates.generate') }}', 'POST', payload);
                    showStatus(data.message, true);
                    await refreshTables();
                } catch (e) {
                    showStatus(e.message, false);
                }
            });

            document.getElementById('btn-generate-categories').addEventListener('click', async function () {
                const shared = readShared();
                const selected = Array.from(document.querySelectorAll('#cat_ids option:checked')).map(o => Number(o.value)).slice(0, 2);
                const payload = {
                    generation_case: 'categories',
                    ...shared,
                    category_name_template: document.getElementById('cat_name').value,
                    category_description_template: document.getElementById('cat_desc').value,
                    category_code_prefix: document.getElementById('cat_prefix').value,
                    category_discount_type: document.getElementById('cat_type').value,
                    category_discount_value: Number(document.getElementById('cat_value').value),
                    category_ids: selected,
                };
                try {
                    const data = await requestJson('{{ route('admin.coupons.templates.generate') }}', 'POST', payload);
                    showStatus(data.message, true);
                    await refreshTables();
                } catch (e) {
                    showStatus(e.message, false);
                }
            });

            function wireBulkControls() {
                const couponSelectAll = document.getElementById('coupon_select_all');
                const couponBoxes = () => Array.from(document.querySelectorAll('.coupon-select'));
                const couponCount = document.getElementById('coupon_selected_count');
                const couponApply = document.getElementById('coupon_apply_bulk');
                const couponAction = document.getElementById('coupon_bulk_action');

                const updateCouponUi = () => {
                    const ids = couponBoxes().filter(i => i.checked).map(i => Number(i.value));
                    couponCount.textContent = ids.length + ' selected';
                    couponApply.disabled = ids.length === 0 || !couponAction.value;
                };

                couponSelectAll?.addEventListener('change', function () {
                    couponBoxes().forEach(i => i.checked = couponSelectAll.checked);
                    updateCouponUi();
                });
                couponBoxes().forEach(i => i.addEventListener('change', updateCouponUi));
                couponAction?.addEventListener('change', updateCouponUi);
                couponApply?.addEventListener('click', async function () {
                    const ids = couponBoxes().filter(i => i.checked).map(i => Number(i.value));
                    if (!ids.length || !couponAction.value) return;
                    if (couponAction.value === 'delete' && !confirm('Delete selected coupons?')) return;
                    try {
                        const data = await requestJson('{{ route('admin.coupons.bulk') }}', 'POST', { action: couponAction.value, ids });
                        showStatus(data.message, true);
                        await refreshTables();
                    } catch (e) {
                        showStatus(e.message, false);
                    }
                });

                const templateSelectAll = document.getElementById('template_select_all');
                const templateBoxes = () => Array.from(document.querySelectorAll('.template-select'));
                const templateCount = document.getElementById('template_selected_count');
                const templateApply = document.getElementById('template_apply_bulk');
                const templateAction = document.getElementById('template_bulk_action');

                const updateTemplateUi = () => {
                    const ids = templateBoxes().filter(i => i.checked).map(i => Number(i.value));
                    templateCount.textContent = ids.length + ' selected';
                    templateApply.disabled = ids.length === 0 || !templateAction.value;
                };

                templateSelectAll?.addEventListener('change', function () {
                    templateBoxes().forEach(i => i.checked = templateSelectAll.checked);
                    updateTemplateUi();
                });
                templateBoxes().forEach(i => i.addEventListener('change', updateTemplateUi));
                templateAction?.addEventListener('change', updateTemplateUi);
                templateApply?.addEventListener('click', async function () {
                    const ids = templateBoxes().filter(i => i.checked).map(i => Number(i.value));
                    if (!ids.length || !templateAction.value) return;
                    try {
                        const data = await requestJson('{{ route('admin.coupons.templates.bulk') }}', 'POST', { action: templateAction.value, ids });
                        showStatus(data.message, true);
                        await refreshTables();
                    } catch (e) {
                        showStatus(e.message, false);
                    }
                });
            }

            wireBulkControls();
        })();
    </script>
@endpush
