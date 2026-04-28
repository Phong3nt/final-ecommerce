<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int         $id
 * @property string      $name
 * @property string      $slug
 * @property string|null $logo_url
 * @property int|null    $icecat_supplier_id
 */
class Brand extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'slug', 'logo_url', 'icecat_supplier_id'];

    public function products()
    {
        return $this->hasMany(Product::class);
    }
}
