<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Size extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'inStock',
    ];

    public function product()
    {
        return $this->belongsToMany(Product::class);
    }
}
