<?php


namespace App\Http\Controllers;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Image;
use Illuminate\Http\Request;
use Inertia\Inertia;

class CartController extends Controller
{
    public function index()
    {
        $cart = Cart::where('user_id', auth()->id())->with(['items.product', 'items.color', 'items.size','items.image'])->first();

        return Inertia::render('Cart/Index', [
            'cart' => $cart
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'quantity' => 'required|integer|min:1',
            'color_id' => 'nullable|exists:colors,id',
            'size_id' => 'nullable|exists:sizes,id',
            'image_id' => 'nullable|exists:images,id'
        ]);

        $cart = Cart::firstOrCreate(['user_id' => auth()->id()]);

        $cartItem = CartItem::where('cart_id', $cart->id)
            ->where('product_id', $request->product_id)
            ->where('color_id', $request->color_id)
            ->where('size_id', $request->size_id)
            ->where('image_id', $request->image_id)
            ->first();

        if ($cartItem) {
            $cartItem->increment('quantity', $request->quantity);
        } else {
            $image = Image::where('product_id', $request->product_id)->first();

            CartItem::create([
                'cart_id' => $cart->id,
                'product_id' => $request->product_id,
                'color_id' => $request->color_id,
                'size_id' => $request->size_id,
                'quantity' => $request->quantity,
                'image_id' => $image->id
            ]);

        }

        return redirect()->route('carts.index');
    }

    public function update(Request $request, CartItem $cartItem)
    {
        $request->validate([
            'quantity' => 'required|integer|min:1'
        ]);

        if ($cartItem->cart->user_id !== auth()->id()) {
            abort(403);
        }

        $cartItem->update(['quantity' => $request->quantity]);

        return redirect()->route('carts.index');
    }

    public function destroy(CartItem $cartItem)
    {
        if ($cartItem->cart->user_id !== auth()->id()) {
            abort(403);
        }

        $cartItem->delete();

        return redirect()->route('carts.index');
    }
}
