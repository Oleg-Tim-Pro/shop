<?php

namespace App\Http\Controllers;
use App\Models\Wishlist;
use Illuminate\Http\Request;
use Inertia\Inertia;

class WishlistController extends Controller
{
    public function index()
    {
        $wishlists = auth()->user()->wishlists()->with('product')->get();
        return Inertia::render('Wishlist/Index', ['wishlists' => $wishlists]);
    }

    public function store(Request $request)
    {
        $product_id = $request->input('product_id');
        if (!auth()->user()->wishlists()->where('product_id', $product_id)->exists()) {
            $wishlist = new Wishlist(['product_id' => $product_id]);
            auth()->user()->wishlists()->save($wishlist);
        }
        return redirect()->back()->with('wishlists', auth()->user()->wishlists()->get());
    }

    public function destroy($id)
    {
        auth()->user()->wishlists()->where('product_id', $id)->delete();
        return redirect()->back()->with('wishlists', auth()->user()->wishlists()->get());
    }
}
