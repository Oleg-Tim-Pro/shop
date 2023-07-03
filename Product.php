<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;
    protected $fillable = [
        'image',
        'name',
        'description',
        'status',
        'category_id',
        'brand_id',
        'gender_id',
        'quantity',
        'price',
    ];

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function brand()
    {
        return $this->belongsTo(Brand::class);
    }

    public function gender()
    {
        return $this->belongsTo(Gender::class);
    }

    public function colors()
    {
        return $this->belongsToMany(Color::class);
    }

    public function sizes()
    {
        return $this->belongsToMany(Size::class);
    }

    public function highlights()
    {
        return $this->belongsToMany(Highlight::class);
    }

    public function images()
    {
        return $this->hasMany(Image::class);
    }

    public function reviews()
    {
        return $this->hasMany(Review::class);
    }

    public function cartItems()
    {
        return $this->hasMany(CartItem::class);
    }

    public function order_items()
    {
        return $this->hasMany(OrderItem::class);
    }

    public function items()
    {
        return $this->hasMany(CartItem::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function wishlists()
    {
        return $this->hasMany(Wishlist::class);
    }
}
