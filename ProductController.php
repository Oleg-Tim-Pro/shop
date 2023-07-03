<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Models\Color;
use App\Models\Brand;
use App\Models\Product;
use App\Models\Gender;
use App\Models\Category;
use App\Models\Size;
use App\Models\Highlight;
use Illuminate\Support\Str;
use Inertia\Inertia;
use App\Models\Cart;


class ProductController extends Controller
{
    public function index(Request $request)
    {
        $query = Product::with('category', 'brand', 'gender', 'colors', 'sizes', 'highlights', 'images');

        if ($request->has('category')) {
            $query->whereHas('category', function ($query) use ($request) {
                $query->where('name', $request->input('category'));
            });
        }

        if ($request->has('sort')) {
            if ($request->input('sort') === 'price_asc') {
                $query->orderBy('price');
            } elseif ($request->input('sort') === 'price_desc') {
                $query->orderByDesc('price');
            }
        }

        if ($request->has('search')) {
            $query->where(function ($query) use ($request) {
                $query
                    ->where('name', 'like', '%' . $request->input('search') . '%')
                    ->orWhere('description', 'like', '%' . $request->input('search') . '%');
            });
        }

        $products = $query->get();
        $categories = Category::all();
        $cart = Cart::with('items.product')->first();
        $wishlists = auth()->user() ? auth()->user()->wishlists()->get() : [];

        return Inertia::render('Products/Index', [
            'products' => $products,
            'categories' => $categories,
            'cart' => $cart,
            'wishlists' => $wishlists
        ]);
    }

    public function search(Request $request)
    {
        $query = Product::query();

        if ($request->has('search')) {
            $query->where(function ($query) use ($request) {
                $query
                    ->where('name', 'like', '%' . $request->input('search') . '%')
                    ->orWhere('description', 'like', '%' . $request->input('search') . '%');
            });
        }

        return $query->get();
    }

    public function show(Product $product)
    {
        $product->load('category', 'brand', 'gender', 'colors', 'sizes', 'highlights', 'images', 'reviews');
        $wishlists = auth()->user() ? auth()->user()->wishlists()->get() : [];
        return Inertia::render('Products/Show', ['product' => $product, 'wishlists' => $wishlists]);
    }


    public function create()
    {
        $categories = Category::all();
        $brands = Brand::all();
        $genders = Gender::all();
        $colors = Color::all();
        $sizes = Size::all();
        $highlights = Highlight::all();
        return Inertia::render('Products/Create', [
            'categories' => $categories,
            'brands' => $brands,
            'genders' => $genders,
            'colors' => $colors,
            'sizes' => $sizes,
            'highlights' => $highlights
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'images' => 'nullable|array',
            'images.*' => 'image',
            'name' => 'required',
            'description' => 'nullable',
            'category_id' => 'required|exists:categories,id',
            'brand_id' => 'required|exists:brands,id',
            'gender_id' => 'required|exists:genders,id',
            'colors' => 'nullable|array',
            'colors.*' => 'exists:colors,id',
            'sizes' => 'nullable|array',
            'sizes.*' => 'exists:sizes,id',
            'highlights' => 'nullable|array',
            'highlights.*' => 'exists:highlights,id',
            'quantity' => 'required|integer|min:0',
            'price' => 'required|numeric|min:0',
        ]);

        $product = Product::create([
            'name' => $request->input('name'),
            'description' => $request->input('description'),
            // 'status' => $request->input('status'),
            'category_id' => $request->input('category_id'),
            'brand_id' => $request->input('brand_id'),
            'gender_id' => $request->input('gender_id'),
            'quantity' => $request->input('quantity'),
            'price' => $request->input('price'),

        ]);

        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $image) {
                $imageName = Str::uuid() . '.' . $image->extension();
                $image->move(public_path('images'), $imageName);
                $product->images()->create(['image' => '/images/' . $imageName]);
            }
        }

        if ($request->has('colors')) {
            $product->colors()->sync($request->input('colors'));
        }

        if ($request->has('sizes')) {
            $product->sizes()->sync($request->input('sizes'));
        }
        if ($request->has('highlights')) {
            $product->highlights()->sync($request->input('highlights'));
        }

        return redirect()->route('products.index');
    }

    public function edit(Product $product)
    {
        $product->load('category', 'brand', 'gender', 'colors', 'sizes', 'highlights', 'images');
        $categories = Category::all();
        $brands = Brand::all();
        $genders = Gender::all();
        $colors = Color::all();
        $sizes = Size::all();
        $highlights = Highlight::all();
        return Inertia::render('Products/Edit', [
            'product' => $product,
            'categories' => $categories,
            'brands' => $brands,
            'genders' => $genders,
            'colors' => $colors,
            'sizes' => $sizes,
            'highlights' => $highlights,
        ]);
    }

    public function update(Request $request, Product $product)
    {
        $request->validate([
            'images' => 'nullable|array',
            'images.*' => 'image',
            'name' => 'required',
            'description' => 'nullable',
//            'status' => 'required|in:active,deactive',
            'category_id' => 'required|exists:categories,id',
            'brand_id' => 'required|exists:brands,id',
            'gender_id' => 'required|exists:genders,id',
            'colors' => 'nullable|array',
            'colors.*' => 'exists:colors,id',
            'sizes' => 'nullable|array',
            'sizes.*' => 'exists:sizes,id',
            'highlights' => 'nullable|array',
            'highlights.*' => 'exists:highlights,id',
            'quantity' => 'required|integer|min:0',
            'price' => 'required|numeric|min:0',
        ]);

        if ($request->hasFile('images')) {
            $images = $request->file('images');
            foreach ($images as $image) {
                $imageName = Str::uuid() . '.' . $image->extension();
                $image->move(public_path('images'), $imageName);
                $product->images()->create(['image' => '/images/' . $imageName]);
            }
        }

        $product->update([
            'name' => $request->input('name'),
            'description' => $request->input('description'),
            // status
//            'status' => $request->input('status'),
            'category_id' => $request->input('category_id'),
            'brand_id' => $request->input('brand_id'),
            'gender_id' => $request->input('gender_id'),
            'quantity' => $request->input('quantity'),
            'price' => $request->input('price'),
        ]);

        if ($request->has('colors')) {
            $product->colors()->sync($request->input('colors'));
        }

        if ($request->has('sizes')) {
            $product->sizes()->sync($request->input('sizes'));
        }
        if ($request->has('highlights')) {
            $product->highlights()->sync($request->input('highlights'));
        }

        return redirect()->route('products.index');
    }


    public function destroy(Product $product)
    {
        // Удалить все элементы корзины, связанные с этим продуктом
        $product->cartItems()->delete();

        // Удалить все изображения продукта с диска
        foreach ($product->images as $image) {
            if (file_exists(public_path($image->image))) {
                unlink(public_path($image->image));
            }
        }

        // Удалить отзыв из базы данных
        $product->reviews()->delete();

        $product->order_items()->delete();
        $product->wishlists()->delete();

        // Удалить продукт из базы данных
        $product->delete();

        return redirect()->route('products.index');
    }


}
