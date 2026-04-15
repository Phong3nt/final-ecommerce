<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'description', 'price', 'stock', 'image', 'category_id', 'rating'];

    protected $casts = [
        'price' => 'decimal:2',
        'stock' => 'integer',
        'rating' => 'float',
        'category_id' => 'integer',
    ];

    public function category()
    {
        return $this->belongsTo(Category::class);
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
}
