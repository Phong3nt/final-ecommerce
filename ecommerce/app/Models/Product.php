<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Cache;

/**
 * @property int $id
 * @property string $name
 * @property string $slug
 * @property string|null $sku
 * @property string|null $description
 * @property float $price
 * @property int $stock
 * @property int|null $low_stock_threshold
 * @property bool $low_stock_notified
 * @property string|null $image
 * @property array<int, string>|null $images
 * @property int $category_id
 * @property string $status
 */
class Product extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = ['name', 'slug', 'sku', 'description', 'price', 'stock', 'low_stock_threshold', 'low_stock_notified', 'image', 'images', 'category_id', 'rating', 'status', 'spec_processor', 'spec_display', 'spec_weight', 'is_icecat_locked', 'import_source'];

    // IMP-014: bump catalog_version in cache whenever a product changes, so all
    // version-keyed cache entries are automatically considered stale.
    protected static function booted(): void
    {
        $bump = fn () => Cache::increment('catalog_version', 1);
        static::saved($bump);
        static::deleted($bump);
        static::restored($bump);
    }

    protected $casts = [
        'price' => 'decimal:2',
        'stock' => 'integer',
        'low_stock_threshold' => 'integer',
        'low_stock_notified' => 'boolean',
        'is_icecat_locked' => 'boolean',
        'rating' => 'float',
        'category_id' => 'integer',
        'images' => 'array',
    ];

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    /**
     * Resolve a product image path or external URL to a display-ready URL.
     * Icecat-imported products store full HTTPS URLs; manually uploaded products
     * store a local storage path (e.g. "products/abc.jpg").
     */
    public function getImageUrlAttribute(): ?string
    {
        if (!$this->image) {
            return null;
        }
        if (str_starts_with($this->image, 'http://') || str_starts_with($this->image, 'https://')) {
            return $this->image;
        }
        return \Illuminate\Support\Facades\Storage::disk('public')->url($this->image);
    }

    /**
     * Resolve all product images (array) to display-ready URLs.
     */
    public function getImagesUrlsAttribute(): array
    {
        return collect($this->images ?? [])->map(function (string $path) {
            if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
                return $path;
            }
            return \Illuminate\Support\Facades\Storage::disk('public')->url($path);
        })->values()->all();
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function relatedProducts(int $limit = 4)
    {
        if (!$this->category_id) {
            return collect();
        }
        return static::where('category_id', $this->category_id)
            ->where('id', '!=', $this->id)
            ->latest()
            ->limit($limit)
            ->get();
    }

    public function scopeSearch($query, string $term)
    {
        return $query->where(function ($q) use ($term) {
            $q->where('name', 'like', '%' . $term . '%')
                ->orWhere('description', 'like', '%' . $term . '%');
        });
    }

    public function scopeFilter($query, array $filters)
    {
        if (!empty($filters['category'])) {
            $query->where('category_id', (int) $filters['category']);
        }

        if (isset($filters['min_price']) && $filters['min_price'] !== '') {
            $query->where('price', '>=', (float) $filters['min_price']);
        }

        if (isset($filters['max_price']) && $filters['max_price'] !== '') {
            $query->where('price', '<=', (float) $filters['max_price']);
        }

        if (isset($filters['min_rating']) && $filters['min_rating'] !== '') {
            $query->where('rating', '>=', (float) $filters['min_rating']);
        }

        return $query;
    }

    public function scopeSort($query, string $sort)
    {
        return match ($sort) {
            'price_asc' => $query->orderBy('price', 'asc'),
            'price_desc' => $query->orderBy('price', 'desc'),
            'oldest' => $query->orderBy('created_at', 'asc'),
            'rating' => $query->orderByDesc('rating'),
            default => $query->latest(), // newest
        };
    }

    public function scopePublished($query)
    {
        return $query->where('status', 'published');
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }
}
