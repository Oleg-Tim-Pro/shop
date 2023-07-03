# shop
Интернет-магазин на Laravel React TypeScript

### ProductController 

```php
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
```
### Страница товара
```tsx
import React from 'react';
import { ReviewsProduct } from "@/Pages/Products/Partials/Show/ReviewsProduct";
import { ImageProduct } from "@/Pages/Products/Partials/Show/ImageProduct";
import { CartProduct } from "@/Pages/Products/Partials/Show/CartProduct";


interface Product {
    id: number;
    images: { id: number; image: string }[];
    name: string;
    description: string;
    category: { id: number; name: string };
    brand: { id: number; name: string };
    gender: { id: number; name: string };
    colors: {
        id: number;
        name: string;
        class: string;
        selectedClass: string;
    }[];
    sizes: { id: number; name: string; inStock: boolean }[];
    highlights: { id: number; name: string }[];
    quantity: number;
    price: number;
    reviews: Review[];
}

interface Review {
    id: number;
    product: {
        name: string;
    };
    user: {
        name: string;
    };
    rating: number;
    comment?: string;
}

interface Props {
    product: Product;

}


const ProductShow: React.FC<Props> = ({ product, wishlists }) => {

    return (
        <div className="bg-white">
            <div className="max-w-2xl mx-auto py-16 px-4 sm:py-24 sm:px-6 lg:max-w-7xl lg:px-8">
                <div className="lg:grid lg:grid-cols-2 lg:gap-x-8 lg:items-start">

                    {/* Изображение */}
                    <ImageProduct images={product.images} />

                    {/* Информация о продукте */}
                    <div className="mt-10 px-4 sm:px-0 sm:mt-16 lg:mt-0">
                        <h1 className="text-3xl font-extrabold tracking-tight text-gray-900">{product.name}</h1>
                        <p className="text-base text-gray-700 space-y-6">{product.brand.name}</p>

                        <div className="mt-3">
                            <h2 className="sr-only">Информация</h2>
                            <p className="text-3xl text-gray-900">{product.price}</p>
                        </div>

                        {/* Отзыв */}
                        <ReviewsProduct reviews={product.reviews} product_id={product.id} />

                        <div className="mt-6">
                            <h3 className="sr-only">Описание</h3>
                            <div className="space-y-6">
                                <p className="text-base text-gray-700 space-y-6">{product.description}</p>
                            </div>

                            <div className="mt-10">
                                <h3 className="text-sm font-medium text-gray-900">Особенности</h3>

                                <div className="mt-4">
                                    <ul role="list" className="pl-4 list-disc text-sm space-y-2">
                                        {product.highlights.map((highlight) => (
                                            <li key={highlight.id}>{highlight.name}</li>
                                        ))}
                                    </ul>
                                </div>
                            </div>
                        </div>

                        <div className="mt-6">
                        {/* Оставить отзыв и атрибуты (цвет, размер*/}
                        <CartProduct product_id={product.id} colors={product.colors} sizes={product.sizes} wishlists={wishlists}/>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    );
};

export default ProductShow;
```
### Компонет CartProduct
```tsx
import React from 'react';
import { HeartIcon } from '@heroicons/react/solid';
import { Inertia } from '@inertiajs/inertia';
import { RadioGroup } from '@headlessui/react';

interface Color {
    id: number;
    name: string;
    class: string;
    selectedClass: string;
}

interface Size {
    id: number;
    name: string;
    inStock: boolean;
}

interface AddToCartProps {
    product_id: number;
    colors: Color[];
    sizes: Size[];
    wishlists: { product_id: number }[];
}

function classNames(...classes) {
    return classes.filter(Boolean).join(' ');
}

export const CartProduct: React.FC<AddToCartProps> = ({ product_id, colors, sizes, wishlists }) => {
    const [quantity, setQuantity] = React.useState(1);
    const [selectedColor, setSelectedColor] = React.useState<Color | null>(null);
    const [selectedSize, setSelectedSize] = React.useState<Size | null>(null);

    const handleWishlistToggle = () => {
        if (isProductInWishlist) {
            Inertia.delete(`/wishlist/${product_id}`);
        } else {
            Inertia.post(`/wishlist`, { product_id: product_id });
        }
    };

    const isProductInWishlist = wishlists.some((wishlist) => wishlist.product_id === product_id);

    const handleAddToCart = () => {
        if (selectedColor && selectedSize) {
            Inertia.post('/cart', {
                product_id: product_id,
                quantity,
                color_id: selectedColor.id,
                size_id: selectedSize.id,
            });
        }
    };

    return (
        <>
            {/* Цвет */}
            <div>
                <h3 className="text-sm text-gray-600">Color</h3>

                <RadioGroup value={selectedColor} onChange={setSelectedColor} className="mt-4">
                    <RadioGroup.Label className="sr-only">Choose a color</RadioGroup.Label>
                    <div className="flex items-center space-x-3">
                        {colors.map((color) => (
                            <RadioGroup.Option
                                key={color.name}
                                value={color}
                                className={({active, checked}) =>
                                    classNames(color.selectedClass, active && checked ? 'ring ring-offset-1' : '', !active && checked ? 'ring-2' : '',
                                        '-m-0.5 relative p-0.5 rounded-full flex items-center justify-center cursor-pointer focus:outline-none'
                                    )
                                }
                            >
                                <RadioGroup.Label as="p" className="sr-only">
                                    {color.name}
                                </RadioGroup.Label>
                                <span aria-hidden="true"
                                      className={classNames(color.class, 'h-8 w-8 border border-black border-opacity-10 rounded-full')}
                                />
                            </RadioGroup.Option>
                        ))}
                    </div>
                </RadioGroup>
            </div>

            {/* Размер */}
            <div className="mt-10">
                <div className="flex items-center justify-between">
                    <h3 className="text-sm text-gray-900 font-medium">Размер</h3>
                    <a href="#" className="text-sm font-medium text-indigo-600 hover:text-indigo-500">
                        Гайд
                    </a>
                </div>

                <RadioGroup value={selectedSize} onChange={setSelectedSize} className="mt-4">
                    <RadioGroup.Label className="sr-only">Choose a size</RadioGroup.Label>
                    <div className="grid grid-cols-4 gap-4 sm:grid-cols-8 lg:grid-cols-4">
                        {sizes.map((size) => (
                            <RadioGroup.Option
                                key={size.name}
                                value={size}
                                disabled={!size.inStock}
                                className={({active}) =>
                                    classNames(
                                        size.inStock
                                            ? 'bg-white shadow-sm text-gray-900 cursor-pointer'
                                            : 'bg-gray-50 text-gray-200 cursor-not-allowed',
                                        active ? 'ring-2 ring-indigo-500' : '',
                                        'group relative border rounded-md py-3 px-4 flex items-center justify-center text-sm font-medium uppercase hover:bg-gray-50 focus:outline-none sm:flex-1 sm:py-6'
                                    )
                                }
                            >
                                {({active, checked}) => (
                                    <>
                                        <RadioGroup.Label as="p">{size.name}</RadioGroup.Label>
                                        {size.inStock ? (
                                            <div
                                                className={classNames(
                                                    active ? 'border' : 'border-2',
                                                    checked ? 'border-indigo-500' : 'border-transparent',
                                                    'absolute -inset-px rounded-md pointer-events-none'
                                                )}
                                                aria-hidden="true"
                                            />
                                        ) : (
                                            <div
                                                aria-hidden="true"
                                                className="absolute -inset-px rounded-md border-2 border-gray-200 pointer-events-none"
                                            >
                                                <svg
                                                    className="absolute inset-0 w-full h-full text-gray-200 stroke-2"
                                                    viewBox="0 0 100 100"
                                                    preserveAspectRatio="none"
                                                    stroke="currentColor"
                                                >
                                                    <line x1={0} y1={100} x2={100} y2={0}
                                                          vectorEffect="non-scaling-stroke"/>
                                                </svg>
                                            </div>
                                        )}
                                    </>
                                )}
                            </RadioGroup.Option>
                        ))}
                    </div>
                </RadioGroup>
            </div>

            <div className="mt-10 flex sm:flex-col1">
                <div className="mr-4 py-3 px-3 rounded-md flex items-center justify-center text-gray-400">
                    <button
                        className="bg-color-input flex-1 p-2 focus:ring-indigo-500 focus:border-indigo-500 block w-full min-w-0 rounded-l-md sm:text-sm border-transparent"
                        onClick={() => setQuantity(quantity - 1)}>
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" strokeWidth={1.5} stroke="currentColor" className="w-5 h-5">
                            <path strokeLinecap="round" strokeLinejoin="round" d="M19.5 12h-15" />
                        </svg>
                    </button>
                    <input
                        type="number"
                        id="quantity"
                        value={quantity}
                        onChange={(e) => setQuantity(parseInt(e.target.value))}
                        min="1"
                        className="bg-color-input flex-1 focus:ring-indigo-500 focus:border-indigo-500 block w-full min-w-0  sm:text-sm border-transparent"
                        style={{width: "50px"}}
                    />
                    <button
                        className="bg-color-input flex-1 p-2 focus:ring-indigo-500 focus:border-indigo-500 block w-full min-w-0 rounded-r-md sm:text-sm border-transparent"
                        onClick={() => setQuantity(quantity + 1)}>
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" strokeWidth={1.5} stroke="currentColor" className="w-5 h-5">
                            <path strokeLinecap="round" strokeLinejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                        </svg>
                    </button>
                </div>

                <button
                    onClick={handleAddToCart}
                    className="max-w-xs flex-1 bg-indigo-600 border border-transparent rounded-md py-3 px-8 flex items-center justify-center text-base font-medium text-white hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-gray-50 focus:ring-indigo-500 sm:w-full"
                >
                    Добавить в корзину
                </button>

                {/* Добавить в список избранного */}

                <button
                    type="button"
                    className="ml-4 py-3 px-3 rounded-md flex items-center justify-center text-gray-400 hover:bg-gray-100 hover:text-gray-500"
                    onClick={handleWishlistToggle}
                >
                    <HeartIcon
                        className={`h-6 w-6 flex-shrink-0 ${
                            isProductInWishlist ? 'text-red-500' : ''
                        }`}
                        aria-hidden="true"
                    />
                    <span className="sr-only">Добавить в список избранного</span>
                </button>

            </div>
        </>
    );
};
```
