<!DOCTYPE html>
<html>

<head>
    <title>Admin — Manage Images: {{ $product->name }}</title>
    <style>
        body {
            font-family: sans-serif;
            margin: 2rem;
            background: #f5f5f5;
        }

        h1 {
            margin-bottom: 1.5rem;
        }

        .alert-success {
            background: #d1e7dd;
            border: 1px solid #badbcc;
            color: #0f5132;
            padding: .75rem 1rem;
            border-radius: 4px;
            margin-bottom: 1rem;
        }

        .alert-error {
            background: #f8d7da;
            border: 1px solid #f5c2c7;
            color: #842029;
            padding: .75rem 1rem;
            border-radius: 4px;
            margin-bottom: 1rem;
        }

        .card {
            background: #fff;
            border: 1px solid #ddd;
            border-radius: 6px;
            padding: 1rem 1.25rem;
            margin-bottom: 1.5rem;
        }

        .card h2 {
            margin: 0 0 .75rem;
            font-size: 1.05rem;
        }

        #image-list {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        #image-list li {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: .6rem .75rem;
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 4px;
            margin-bottom: .5rem;
            cursor: grab;
            user-select: none;
        }

        #image-list li.dragging {
            opacity: .4;
        }

        #image-list li img {
            width: 72px;
            height: 72px;
            object-fit: cover;
            border-radius: 4px;
            border: 2px solid transparent;
        }

        #image-list li img.is-thumbnail {
            border-color: #198754;
        }

        .drag-handle {
            font-size: 1.2rem;
            color: #aaa;
            cursor: grab;
        }

        .badge-thumbnail {
            display: inline-block;
            background: #198754;
            color: #fff;
            font-size: .72rem;
            font-weight: 600;
            padding: .1rem .4rem;
            border-radius: 3px;
            text-transform: uppercase;
        }

        .btn {
            display: inline-block;
            padding: .35rem .9rem;
            border: none;
            border-radius: 4px;
            font-size: .875rem;
            cursor: pointer;
            text-decoration: none;
        }

        .btn-primary {
            background: #0d6efd;
            color: #fff;
        }

        .btn-success {
            background: #198754;
            color: #fff;
        }

        .btn-danger {
            background: #dc3545;
            color: #fff;
        }

        .btn-secondary {
            background: #6c757d;
            color: #fff;
        }

        .image-meta {
            flex: 1;
            min-width: 0;
        }

        .image-path {
            font-size: .8rem;
            color: #6c757d;
            word-break: break-all;
        }

        .image-actions {
            display: flex;
            gap: .5rem;
            flex-shrink: 0;
        }

        .empty-state {
            color: #6c757d;
            padding: 1rem 0;
        }
    </style>
</head>

<body>
    <h1>Manage Images: {{ $product->name }}</h1>

    <p>
        <a href="{{ route('admin.products.edit', $product) }}" class="btn btn-secondary">← Back to Edit</a>
        <a href="{{ route('admin.products.index') }}" class="btn btn-secondary" style="margin-left:.5rem;">Products List</a>
    </p>

    @if(session('success'))
        <div class="alert-success">{{ session('success') }}</div>
    @endif

    @if($errors->any())
        <div class="alert-error">
            @foreach($errors->all() as $err)
                <div>{{ $err }}</div>
            @endforeach
        </div>
    @endif

    <div class="card">
        <h2>Current Images</h2>
        <p style="font-size:.875rem;color:#555;margin:.25rem 0 1rem;">
            Drag rows to reorder. The <span class="badge-thumbnail">thumbnail</span> image is used as the primary product image.
        </p>

        @if($product->images && count($product->images) > 0)
            <form id="reorder-form" method="POST" action="{{ route('admin.products.images.reorder', $product) }}">
                @csrf
                {{-- Hidden inputs are injected by JS after drag --}}
                @foreach($product->images as $path)
                    <input type="hidden" name="image_order[]" value="{{ $path }}">
                @endforeach
                <ul id="image-list">
                    @foreach($product->images as $i => $path)
                        <li data-path="{{ $path }}">
                            <span class="drag-handle" title="Drag to reorder">&#8597;</span>
                            <img
                                src="{{ Storage::disk('public')->exists($path) ? Storage::url($path) : 'https://placehold.co/72x72?text=No+Image' }}"
                                alt="Product image {{ $i + 1 }}"
                                class="{{ $product->image === $path ? 'is-thumbnail' : '' }}"
                            >
                            <div class="image-meta">
                                @if($product->image === $path)
                                    <span class="badge-thumbnail">Thumbnail</span>
                                @endif
                                <div class="image-path">{{ $path }}</div>
                            </div>
                            <div class="image-actions">
                                @if($product->image !== $path)
                                    <form method="POST" action="{{ route('admin.products.images.thumbnail', $product) }}" style="display:inline;">
                                        @csrf
                                        <input type="hidden" name="thumbnail_index" value="{{ $i }}">
                                        <button type="submit" class="btn btn-success">Set Thumbnail</button>
                                    </form>
                                @endif
                                <form method="POST" action="{{ route('admin.products.images.destroy', [$product, $i]) }}" style="display:inline;"
                                      onsubmit="return confirm('Remove this image?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-danger">Remove</button>
                                </form>
                            </div>
                        </li>
                    @endforeach
                </ul>
                <div style="margin-top:1rem;">
                    <button type="submit" class="btn btn-primary">Save Order</button>
                </div>
            </form>
        @else
            <p class="empty-state">No images uploaded yet. <a href="{{ route('admin.products.edit', $product) }}">Go to Edit</a> to upload images.</p>
        @endif
    </div>

    <script>
        const list = document.getElementById('image-list');
        if (list) {
            let dragged = null;

            list.addEventListener('dragstart', function(e) {
                dragged = e.target.closest('li');
                if (dragged) dragged.classList.add('dragging');
            });

            list.addEventListener('dragend', function() {
                if (dragged) dragged.classList.remove('dragging');
                dragged = null;
                syncHiddenInputs();
            });

            list.addEventListener('dragover', function(e) {
                e.preventDefault();
                const target = e.target.closest('li');
                if (target && dragged && target !== dragged) {
                    const rect = target.getBoundingClientRect();
                    const after = (e.clientY - rect.top) > (rect.height / 2);
                    list.insertBefore(dragged, after ? target.nextSibling : target);
                }
            });

            function syncHiddenInputs() {
                const form = document.getElementById('reorder-form');
                form.querySelectorAll('input[name="image_order[]"]').forEach(el => el.remove());
                list.querySelectorAll('li[data-path]').forEach(function(li) {
                    const input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = 'image_order[]';
                    input.value = li.dataset.path;
                    form.appendChild(input);
                });
            }

            // Set draggable on li items
            list.querySelectorAll('li').forEach(function(li) {
                li.setAttribute('draggable', 'true');
            });
        }
    </script>
</body>

</html>
