<?php

use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// ─── Live Search (public, no prefix for simple internal calls) ──────────
Route::get('/search/live', function (Request $request) {
    $query = $request->get('q');
    if (strlen($query) < 2) {
        return response()->json([]);
    }

    $products = Product::published()
        ->where(function ($q) use ($query) {
            $q->where('name', 'like', "%{$query}%")
              ->orWhere('tags', 'like', "%{$query}%");
        })
        ->with(['category'])
        ->take(6)
        ->get()
        ->map(fn ($p) => [
            'id'    => $p->id,
            'name'  => $p->name,
            'slug'  => $p->slug,
            'price' => $p->price,
            'image' => $p->thumbnail,
            'url'   => route('products.show', $p->slug),
        ]);

    return response()->json($products);
})->name('api.search.live');

// ─── Menu Categories ────────────────────────────────────────────────────
Route::get('/menu/categories', function () {
    return Category::active()->parents()->ordered()
        ->with(['children' => fn ($q) => $q->active()->ordered()])
        ->get(['id', 'name', 'slug', 'icon']);
})->name('api.menu.categories');

Route::prefix('v1')->group(function () {
    // Public
    Route::get('/products', [App\Http\Controllers\Api\ProductController::class, 'index']);
    Route::get('/products/{id}', [App\Http\Controllers\Api\ProductController::class, 'show']);
    Route::get('/categories', [App\Http\Controllers\Api\CategoryController::class, 'index']);
    Route::get('/categories/{id}', [App\Http\Controllers\Api\CategoryController::class, 'show']);

    // Auth required
    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/user', fn (Request $request) => $request->user());
        Route::get('/cart', [App\Http\Controllers\Api\CartController::class, 'index']);
        Route::post('/cart/add', [App\Http\Controllers\Api\CartController::class, 'add']);
        Route::post('/cart/update', [App\Http\Controllers\Api\CartController::class, 'update']);
        Route::delete('/cart/{id}', [App\Http\Controllers\Api\CartController::class, 'remove']);
        Route::get('/orders', [App\Http\Controllers\Api\OrderController::class, 'index']);
        Route::get('/orders/{id}', [App\Http\Controllers\Api\OrderController::class, 'show']);
        Route::post('/checkout', [App\Http\Controllers\Api\CheckoutController::class, 'store']);
        Route::get('/wishlist', [App\Http\Controllers\Api\WishlistController::class, 'index']);
        Route::post('/wishlist/toggle', [App\Http\Controllers\Api\WishlistController::class, 'toggle']);
    });
});
