@extends('layouts.admin')

@section('title', 'Admin — Brands')
@section('page-title', 'Brands')

@section('content')
    <div x-data="adminBrands()" x-init="$el.classList.add('fade-in')">

        {{-- Header row --}}
        <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
            <h4 class="mb-0">Brands</h4>
            <div class="d-flex gap-2">
                <button type="button" class="btn btn-outline-secondary btn-sm" @click="importFromIcecat()">
                    <i class="bi bi-cloud-download me-1"></i> Import from Icecat
                </button>
                <button type="button" class="btn btn-primary btn-sm"
                    data-bs-toggle="modal" data-bs-target="#createBrandModal">
                    <i class="bi bi-plus-lg me-1"></i> New Brand
                </button>
            </div>
        </div>

        {{-- Import result alert --}}
        <div x-show="importMsg" x-cloak
             :class="importError ? 'alert alert-danger' : 'alert alert-success'"
             class="alert mb-3" role="alert" x-text="importMsg"></div>

        {{-- Brands table --}}
        <div class="card shadow-sm border-0 rounded-3">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>ID</th>
                                <th>Logo</th>
                                <th>Name</th>
                                <th>Slug</th>
                                <th>Icecat Supplier ID</th>
                                <th>Products</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($brands as $brand)
                                <tr>
                                    <td>{{ $brand->id }}</td>
                                    <td>
                                        @if($brand->logo_url)
                                            <img src="{{ $brand->logo_url }}" alt="{{ $brand->name }}"
                                                 style="max-height:32px;max-width:64px;object-fit:contain;">
                                        @else
                                            <span class="text-muted">—</span>
                                        @endif
                                    </td>
                                    <td class="fw-semibold">{{ $brand->name }}</td>
                                    <td><code>{{ $brand->slug }}</code></td>
                                    <td>{{ $brand->icecat_supplier_id ?? '—' }}</td>
                                    <td>{{ $brand->products_count }}</td>
                                    <td>
                                        <button type="button" class="btn btn-outline-secondary btn-sm"
                                            @click="openEdit({{ $brand->id }}, @js($brand->name), @js($brand->logo_url ?? ''), {{ $brand->icecat_supplier_id ?? 'null' }})"
                                            data-bs-toggle="modal" data-bs-target="#editBrandModal">
                                            Edit
                                        </button>
                                        <form method="POST"
                                              action="{{ route('admin.brands.destroy', $brand) }}"
                                              style="display:inline"
                                              data-confirm="Delete brand &quot;{{ $brand->name }}&quot;? Products assigned to this brand will have their brand cleared.">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-danger btn-sm ms-1">Delete</button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center text-muted py-4">No brands yet.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- Create Brand Modal --}}
        <div class="modal fade" id="createBrandModal" tabindex="-1" aria-labelledby="createBrandModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <form method="POST" action="{{ route('admin.brands.store') }}">
                        @csrf
                        <div class="modal-header">
                            <h5 class="modal-title" id="createBrandModalLabel">New Brand</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <div class="mb-3">
                                <label for="create_name" class="form-label fw-semibold">Name <span class="text-danger">*</span></label>
                                <input type="text" id="create_name" name="name" class="form-control @error('name') is-invalid @enderror"
                                       value="{{ old('name') }}" required maxlength="100">
                                @error('name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="mb-3">
                                <label for="create_logo_url" class="form-label fw-semibold">Logo URL</label>
                                <input type="url" id="create_logo_url" name="logo_url" class="form-control @error('logo_url') is-invalid @enderror"
                                       value="{{ old('logo_url') }}" maxlength="500" placeholder="https://…">
                                @error('logo_url')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="mb-3">
                                <label for="create_supplier_id" class="form-label fw-semibold">Icecat Supplier ID</label>
                                <input type="number" id="create_supplier_id" name="icecat_supplier_id"
                                       class="form-control @error('icecat_supplier_id') is-invalid @enderror"
                                       value="{{ old('icecat_supplier_id') }}" min="1">
                                @error('icecat_supplier_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-primary btn-sm">Create</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        {{-- Edit Brand Modal --}}
        <div class="modal fade" id="editBrandModal" tabindex="-1" aria-labelledby="editBrandModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <form method="POST" :action="editAction">
                        @csrf
                        @method('PATCH')
                        <div class="modal-header">
                            <h5 class="modal-title" id="editBrandModalLabel">Edit Brand</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <div class="mb-3">
                                <label for="edit_name" class="form-label fw-semibold">Name <span class="text-danger">*</span></label>
                                <input type="text" id="edit_name" name="name" class="form-control"
                                       x-model="editName" required maxlength="100">
                            </div>
                            <div class="mb-3">
                                <label for="edit_logo_url" class="form-label fw-semibold">Logo URL</label>
                                <input type="url" id="edit_logo_url" name="logo_url" class="form-control"
                                       x-model="editLogoUrl" maxlength="500" placeholder="https://…">
                            </div>
                            <div class="mb-3">
                                <label for="edit_supplier_id" class="form-label fw-semibold">Icecat Supplier ID</label>
                                <input type="number" id="edit_supplier_id" name="icecat_supplier_id" class="form-control"
                                       x-model="editSupplierId" min="1">
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-primary btn-sm">Save</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

    </div>
@endsection

@push('scripts')
    <script>
        function adminBrands() {
            return {
                editAction: '',
                editName: '',
                editLogoUrl: '',
                editSupplierId: '',
                importMsg: '',
                importError: false,

                openEdit(id, name, logoUrl, supplierId) {
                    this.editAction   = '{{ url("admin/brands") }}/' + id;
                    this.editName     = name;
                    this.editLogoUrl  = logoUrl || '';
                    this.editSupplierId = supplierId || '';
                },

                async importFromIcecat() {
                    this.importMsg   = 'Importing brands from Icecat…';
                    this.importError = false;
                    try {
                        const res  = await fetch('{{ route('admin.brands.import-from-icecat') }}', {
                            method: 'POST',
                            headers: {
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                                'Accept': 'application/json',
                            },
                        });
                        const data = await res.json();
                        if (res.ok) {
                            this.importMsg = `Imported ${data.imported}, updated ${data.updated}, skipped ${data.skipped} brands.`;
                            if (data.imported > 0 || data.updated > 0) {
                                setTimeout(() => window.location.reload(), 1500);
                            }
                        } else {
                            this.importMsg   = data.message || 'Import failed.';
                            this.importError = true;
                        }
                    } catch (e) {
                        this.importMsg   = 'Network error: ' + e.message;
                        this.importError = true;
                    }
                },
            };
        }

        document.querySelectorAll('form[data-confirm]').forEach(function (form) {
            form.addEventListener('submit', function (e) {
                if (!confirm(form.dataset.confirm)) { e.preventDefault(); }
            });
        });
    </script>
@endpush
