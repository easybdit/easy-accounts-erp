<?php

namespace App\Models\Inventory;

use Database\Factories\Inventory\ProductCategoryFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProductCategory extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    protected static function newFactory(): ProductCategoryFactory
    {
        return ProductCategoryFactory::new();
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }
}
