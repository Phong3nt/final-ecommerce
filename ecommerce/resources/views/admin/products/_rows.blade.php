@forelse($products as $product)
    <tr>
        <td>
            <input type="checkbox" class="form-check-input" data-product-id="{{ $product->id }}"
                :checked="selected.includes('{{ $product->id }}')" @change="toggleRow('{{ $product->id }}')">
        </td>
        <td>{{ $product->id }}</td>
        <td>{{ $product->name }}</td>
        <td>${{ number_format($product->price, 2) }}</td>
        <td>{{ $product->stock }}</td>
        <td>{{ $product->category?->name ?? '—' }}</td>
        <td>{{ $product->brand?->name ?? '—' }}</td>
        <td>
            <span class="badge bg-{{ $product->status === 'published' ? 'success' : 'secondary' }}">
                {{ ucfirst($product->status) }}
            </span>
        </td>
        <td>{{ $product->created_at->format('Y-m-d') }}</td>
        <td>
            <a href="{{ route('admin.products.edit', $product) }}" class="btn btn-outline-secondary btn-sm">Edit</a>
            <form method="POST" action="{{ route('admin.products.destroy', $product) }}" style="display:inline"
                data-confirm="Archive &quot;{{ $product->name }}&quot;? This will hide it from the store.">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn btn-danger btn-sm ms-1">Archive</button>
            </form>
        </td>
    </tr>
@empty
    <tr>
        <td colspan="10" class="text-center text-muted py-4">No products yet.</td>
    </tr>
@endforelse