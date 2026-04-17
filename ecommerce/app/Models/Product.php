<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = ['name', 'slug', 'sku', 'description', 'price', 'stock', 'image', 'images', 'category_id', 'rating', 'status'];

    protected $casts = [
        'price' => 'decimal:2',
        'stock' => 'integer',
        'rating' => 'float',
        'category_id' => 'integer',
        'images' => 'array',
    ];

    public function getRouteKeyName(): string
    {
        return 'slug';
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
