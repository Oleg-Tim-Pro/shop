<?php

namespace App\Http\Controllers;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Http\Request;
use App\Models\Image;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class OrderController extends Controller
{
    public function index(Request $request)
    {
        $orders = Order::where('user_id', $request->user()->id)->with('items.product')->get();

        return Inertia::render('Orders/Index', [
            'orders' => $orders,
        ]);
    }

    public function create(Request $request)
    {
        $cart = Cart::where('user_id', $request->user()->id)->with(['items.product', 'items.color', 'items.size','items.image'])->firstOrFail();

        return Inertia::render('Orders/Create', [
            'cart' => $cart,
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'phone' => 'required|string|max:255',
            'address' => 'required_if:delivery_method,delivery',
            'city' => 'required_if:delivery_method,delivery',
            'payment_method' => 'required|in:наличными,картой',
            'delivery_method' => 'required|in:доставка,самовывоз',
        ]);

        $cart = Cart::where('user_id', $request->user()->id)->with('items.product')->firstOrFail();

        DB::transaction(function () use ($request, $cart) {
            $order = Order::create([
                'user_id' => $request->user()->id,
                'status' => 'новый',
                'name' => $request->input('name'),
                'email' => $request->input('email'),
                'phone' => $request->input('phone'),
                'address' => $request->input('address'),
                'city' => $request->input('city'),
                'payment_method' => $request->input('payment_method'),
                'delivery_method' => $request->input('delivery_method'),
            ]);

            foreach ($cart->items as $item) {
                $image = Image::where('product_id', $item->product_id)->first();
                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $item->product_id,
                    'price' => $item->product->price,
                    'color_id' => $item->color_id,
                    'size_id' => $item->size_id,
                    'image_id' => $image ? $image->id : null,
                    'quantity' => $item->quantity,
                ]);
            }

//            if ($request->input('payment_method') === 'картой') {
//                // перенаправление на страницу оплаты
//                return redirect()->route('payment.checkout', $order);
//            } else {
//                return redirect()->route('orders.show', $order);
//            }



            CartItem::where('cart_id', $cart->id)->delete();

            if ($cart) {
                // Удаление всех связанных записей из таблицы cart_items
                $cart->items()->delete();

                // Удаление записи из таблицы carts
                $cart->delete();
            } else {
                // Запись с указанным идентификатором не найдена
            }

        });

        return redirect()->route('orders.index');
    }

    public function show(Request $request, Order $order)
    {
        if ($order->user_id !== $request->user()->id) {
            abort(403);
        }

        return Inertia::render('Orders/Show', [
            'order' => $order->load('items.product', 'items.color', 'items.size','items.image'),
        ]);
    }
}
