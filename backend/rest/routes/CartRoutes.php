<?php
require_once __DIR__ . '/../services/CartService.php';

// Get user's cart with items
Flight::route('GET /users/@user_id/cart', function ($user_id) {
    Flight::json(Flight::cartService()->getCartWithItems($user_id));
});

// Get cart total
Flight::route('GET /users/@user_id/cart/total', function ($user_id) {
    Flight::json(Flight::cartService()->getCartTotal($user_id));
});

// Add item to cart
Flight::route('POST /users/@user_id/cart/items', function ($user_id) {
    try {
        $data = Flight::request()->data->getData();
        Flight::cartService()->addToCart($user_id, $data['product_id'], $data['quantity'] ?? 1);
        Flight::json(['message' => 'Item added to cart']);
    } catch (Exception $e) {
        Flight::json(['error' => $e->getMessage()], 400);
    }
});

// Update cart item quantity
Flight::route('PUT /users/@user_id/cart/items/@cart_item_id', function ($user_id, $cart_item_id) {
    try {
        $data = Flight::request()->data->getData();
        Flight::cartService()->updateCartItem($user_id, $cart_item_id, $data['quantity']);
        Flight::json(['message' => 'Cart item updated']);
    } catch (Exception $e) {
        Flight::json(['error' => $e->getMessage()], 400);
    }
});

// Remove item from cart
Flight::route('DELETE /users/@user_id/cart/items/@cart_item_id', function ($user_id, $cart_item_id) {
    try {
        Flight::cartService()->removeFromCart($user_id, $cart_item_id);
        Flight::json(['message' => 'Item removed from cart']);
    } catch (Exception $e) {
        Flight::json(['error' => $e->getMessage()], 400);
    }
});

// Clear cart
Flight::route('DELETE /users/@user_id/cart', function ($user_id) {
    try {
        Flight::cartService()->clearCart($user_id);
        Flight::json(['message' => 'Cart cleared']);
    } catch (Exception $e) {
        Flight::json(['error' => $e->getMessage()], 400);
    }
});

// Validate cart for checkout
Flight::route('GET /users/@user_id/cart/validate', function ($user_id) {
    try {
        Flight::json(Flight::cartService()->validateCartForCheckout($user_id));
    } catch (Exception $e) {
        Flight::json(['error' => $e->getMessage()], 400);
    }
});
