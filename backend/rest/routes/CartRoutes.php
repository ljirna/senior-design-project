<?php
Flight::group('/cart', function () {
    // GET user's cart with items - BOTH admin and customer (their own cart)
    Flight::route('GET /', function () {
        Flight::auth_middleware()->authorizeRoles([Roles::ADMIN, Roles::CUSTOMER]);

        $user = Flight::get('user'); // Get user from JWT token
        Flight::json(Flight::cartService()->getCartWithItems($user['id']));
    });

    // Get cart total - BOTH admin and customer
    Flight::route('GET /total', function () {
        Flight::auth_middleware()->authorizeRoles([Roles::ADMIN, Roles::CUSTOMER]);

        $user = Flight::get('user');
        Flight::json(Flight::cartService()->getCartTotal($user['id']));
    });

    // Add item to cart - BOTH admin and customer
    Flight::route('POST /add', function () {
        Flight::auth_middleware()->authorizeRoles([Roles::ADMIN, Roles::CUSTOMER]);

        $user = Flight::get('user');
        $data = Flight::request()->data->getData();

        if (!isset($data['product_id'])) {
            Flight::json(['error' => 'Product ID is required'], 400);
            return;
        }

        try {
            $result = Flight::cartService()->addToCart(
                $user['id'],
                $data['product_id'],
                $data['quantity'] ?? 1
            );
            Flight::json([
                'success' => true,
                'message' => 'Item added to cart',
                'data' => $result
            ]);
        } catch (Exception $e) {
            Flight::json(['error' => $e->getMessage()], 400);
        }
    });

    // Update cart item quantity - BOTH admin and customer
    Flight::route('PUT /items/@cart_item_id', function ($cart_item_id) {
        Flight::auth_middleware()->authorizeRoles([Roles::ADMIN, Roles::CUSTOMER]);

        $user = Flight::get('user');
        $data = Flight::request()->data->getData();

        if (!isset($data['quantity'])) {
            Flight::json(['error' => 'Quantity is required'], 400);
            return;
        }

        try {
            $result = Flight::cartService()->updateCartItem(
                $user['id'],
                $cart_item_id,
                $data['quantity']
            );
            Flight::json([
                'success' => true,
                'message' => 'Cart item updated',
                'data' => $result
            ]);
        } catch (Exception $e) {
            Flight::json(['error' => $e->getMessage()], 400);
        }
    });

    // Remove item from cart - BOTH admin and customer
    Flight::route('DELETE /items/@cart_item_id', function ($cart_item_id) {
        Flight::auth_middleware()->authorizeRoles([Roles::ADMIN, Roles::CUSTOMER]);

        $user = Flight::get('user');

        try {
            $result = Flight::cartService()->removeFromCart($user['id'], $cart_item_id);
            Flight::json([
                'success' => true,
                'message' => 'Item removed from cart'
            ]);
        } catch (Exception $e) {
            Flight::json(['error' => $e->getMessage()], 400);
        }
    });

    // Clear cart - BOTH admin and customer
    Flight::route('DELETE /clear', function () {
        Flight::auth_middleware()->authorizeRoles([Roles::ADMIN, Roles::CUSTOMER]);

        $user = Flight::get('user');

        try {
            $result = Flight::cartService()->clearCart($user['id']);
            Flight::json([
                'success' => true,
                'message' => 'Cart cleared'
            ]);
        } catch (Exception $e) {
            Flight::json(['error' => $e->getMessage()], 400);
        }
    });

    // Validate cart for checkout - BOTH admin and customer
    Flight::route('GET /validate', function () {
        Flight::auth_middleware()->authorizeRoles([Roles::ADMIN, Roles::CUSTOMER]);

        $user = Flight::get('user');

        try {
            $result = Flight::cartService()->validateCartForCheckout($user['id']);
            Flight::json([
                'success' => true,
                'data' => $result
            ]);
        } catch (Exception $e) {
            Flight::json(['error' => $e->getMessage()], 400);
        }
    });
});
