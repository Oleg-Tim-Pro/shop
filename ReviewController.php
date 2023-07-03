<?php

namespace App\Http\Controllers;

use App\Models\Review;
use App\Models\Product;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ReviewController extends Controller
{

    public function index(Request $request)
    {
        $product_id = $request->input('product_id');
        $product = Product::find($product_id);
        $reviews = Review::with('product', 'user')->when($product_id, function ($query, $product_id) {return $query->where('product_id', $product_id);})->get();
        return Inertia::render('Reviews/Index', ['reviews' => $reviews, 'product' => $product]);
    }

    public function show(Review $review)
    {
        $review->load('product', 'user');
        return Inertia::render('Reviews/Show', ['review' => $review]);
    }

    public function create()
    {
        return Inertia::render('Reviews/Create', ['product_id' => $request->input('product_id')]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'rating' => 'required|integer|min:1|max:5',
            'comment' => 'nullable|string',
        ]);


        $review = Review::create([
            'product_id' => $request->input('product_id'),
            'rating' => $request->input('rating'),
            'comment' => $request->input('comment'),
            'user_id' => auth()->id(),
        ]);

        return redirect()->back();
    }

    public function edit(Review $review)
    {
        return Inertia::render('Reviews/Edit', ['review' => $review]);
    }

    public function update(Request $request, Review $review)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'rating' => 'required|integer|min:1|max:5',
            'comment' => 'nullable|string',
        ]);

        $review->update([
            'product_id' => $request->input('product_id'),
            'rating' => $request->input('rating'),
            'comment' => $request->input('comment'),
        ]);


        return redirect()->route('reviews.index')->with('success', 'Review updated successfully.');
    }

    public function destroy(Review $review)
    {
        $review->delete();
        return redirect()->back()->with('success', 'Review deleted successfully.');
    }
}
