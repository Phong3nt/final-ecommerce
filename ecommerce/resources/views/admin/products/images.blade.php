@extends('layouts.admin')
{{-- @include('partials.toast') --}}

@section('title', 'Admin — Manage Images')
@section('page-title', 'Manage Images')

@section('content')
    <div x-data x-init="$el.classList.add('fade-in')">
        <div class="d-flex gap-2 mb-3">
            <a href="{{ route('admin.products.edit', $product) }}" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-arrow-left me-1"></i> Back to Edit
            </a>
            <a href="{{ route('admin.products.index') }}" class="btn btn-outline-secondary btn-sm">Products List</a>
        </div>

        <h5 class="mb-3">Manage Images: {{ $product->name }}</h5>

        @if($errors->any())
            <div class="alert alert-danger py-2 mb-3">
                @foreach($errors->all() as $err)
                    <div class="small">{{ $err }}</div>
                @endforeach
            </div>
        @endif

        <div class="card shadow-sm border-0 rounded-3">
            <div class="card-body">
                <h6 class="card-title mb-1">Current Images</h6>
                <p class="small text-muted mb-3">
                    Drag rows to reorder. The <span class="badge bg-success" style="font-size:.72rem;">thumbnail</span>
                    image is used as the primary product image.
                </p>

                @if($product->images && count($product->images) > 0)
                    <form id="reorder-form" method="POST" action="{{ route('admin.products.images.reorder', $product) }}">
                        @csrf
                        @foreach($product->images as $path)
                            <input type="hidden" name="image_order[]" value="{{ $path }}">
                        @endforeach
                        <ul id="image-list" class="list-unstyled">
                            @foreach($product->images as $i => $path)
                                <li class="d-flex align-items-center gap-3 p-2 bg-light border rounded mb-2"
                                    data-path="{{ $path }}">
                                    <span class="text-muted fs-5" title="Drag to reorder" style="cursor:grab;">&#8597;</span>
                                    <img src="{{ str_starts_with($path, 'http') ? $path : (Storage::disk('public')->exists($path) ? Storage::url($path) : 'https://placehold.co/72x72?text=No+Image') }}"
                                        alt="Product image {{ $i + 1 }}"
                                        class="rounded {{ $product->image === $path ? 'border border-success border-2' : '' }}"
                                        style="width:72px;height:72px;object-fit:cover;">
                                    <div class="flex-grow-1">
                                        @if($product->image === $path)
                                            <span class="badge bg-success" style="font-size:.72rem;">Thumbnail</span>
                                        @endif
                                        <div class="small text-muted text-break">{{ $path }}</div>
                                    </div>
                                    <div class="d-flex gap-2 flex-shrink-0">
                                        @if($product->image !== $path)
                                            <form method="POST" action="{{ route('admin.products.images.thumbnail', $product) }}"
                                                style="display:inline;">
                                                @csrf
                                                <input type="hidden" name="thumbnail_index" value="{{ $i }}">
                                                <button type="submit" class="btn btn-success btn-sm">Set Thumbnail</button>
                                            </form>
                                        @endif
                                        <form method="POST" action="{{ route('admin.products.images.destroy', [$product, $i]) }}"
                                            style="display:inline;" onsubmit="return confirm('Remove this image?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-danger btn-sm">Remove</button>
                                        </form>
                                    </div>
                                </li>
                            @endforeach
                        </ul>
                        <div class="mt-2">
                            <button type="submit" class="btn btn-primary btn-sm">Save Order</button>
                        </div>
                    </form>
                @else
                    <p class="text-muted">No images uploaded yet. <a href="{{ route('admin.products.edit', $product) }}">Go to
                            Edit</a> to upload images.</p>
                @endif
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        const list = document.getElementById('image-list');
        if (list) {
            let dragged = null;
            list.addEventListener('dragstart', function (e) { dragged = e.target.closest('li'); if (dragged) dragged.classList.add('opacity-50'); });
            list.addEventListener('dragend', function () { if (dragged) dragged.classList.remove('opacity-50'); dragged = null; syncHiddenInputs(); });
            list.addEventListener('dragover', function (e) {
                e.preventDefault();
                const target = e.target.closest('li');
                if (target && dragged && target !== dragged) {
                    const rect = target.getBoundingClientRect();
                    list.insertBefore(dragged, (e.clientY - rect.top) > (rect.height / 2) ? target.nextSibling : target);
                }
            });
            function syncHiddenInputs() {
                const form = document.getElementById('reorder-form');
                form.querySelectorAll('input[name="image_order[]"]').forEach(el => el.remove());
                list.querySelectorAll('li[data-path]').forEach(function (li) {
                    const input = document.createElement('input');
                    input.type = 'hidden'; input.name = 'image_order[]'; input.value = li.dataset.path;
                    form.appendChild(input);
                });
            }
            list.querySelectorAll('li').forEach(function (li) { li.setAttribute('draggable', 'true'); });
        }
    </script>
@endpush