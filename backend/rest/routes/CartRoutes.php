<?php
$getCurrentUserId = function () {
    $u = Flight::get('user');
    if (is_array($u)) {
        return $u['id'] ?? $u['user_id'] ?? null;
    }
    if (is_object($u)) {
        return $u->id ?? $u->user_id ?? null;
    }
    return null;
};

Flight::group('/cart', function () use ($getCurrentUserId) {
    Flight::route('GET /', function () use ($getCurrentUserId) {
        Flight::auth_middleware()->authorizeRoles([Roles::ADMIN, Roles::CUSTOMER]);

        $userId = $getCurrentUserId ? $getCurrentUserId() : null;
        if (!$userId) {
            Flight::json(['error' => 'User not found'], 401);
            return;
        }
        Flight::json(Flight::cartService()->getCartWithItems($userId));
    });

    Flight::route('GET /total', function () use ($getCurrentUserId) {
        Flight::auth_middleware()->authorizeRoles([Roles::ADMIN, Roles::CUSTOMER]);

        $userId = $getCurrentUserId ? $getCurrentUserId() : null;
        if (!$userId) {
            Flight::json(['error' => 'User not found'], 401);
            return;
        }
        Flight::json(Flight::cartService()->getCartTotal($userId));
    });

    Flight::route('POST /add', function () use ($getCurrentUserId) {
        Flight::auth_middleware()->authorizeRoles([Roles::ADMIN, Roles::CUSTOMER]);

        $userId = $getCurrentUserId ? $getCurrentUserId() : null;
        if (!$userId) {
            Flight::json(['error' => 'User not found'], 401);
            return;
        }
        $data = Flight::request()->data->getData();

        if (!isset($data['product_id'])) {
            Flight::json(['error' => 'Product ID is required'], 400);
            return;
        }

        try {
            $result = Flight::cartService()->addToCart(
                $userId,
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

    Flight::route('PUT /items/@cart_item_id', function ($cart_item_id) use ($getCurrentUserId) {
        Flight::auth_middleware()->authorizeRoles([Roles::ADMIN, Roles::CUSTOMER]);

        $userId = $getCurrentUserId ? $getCurrentUserId() : null;
        if (!$userId) {
            Flight::json(['error' => 'User not found'], 401);
            return;
        }
        $data = Flight::request()->data->getData();

        if (!isset($data['quantity'])) {
            Flight::json(['error' => 'Quantity is required'], 400);
            return;
        }

        try {
            $result = Flight::cartService()->updateCartItem(
                $userId,
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

    Flight::route('DELETE /items/@cart_item_id', function ($cart_item_id) use ($getCurrentUserId) {
        Flight::auth_middleware()->authorizeRoles([Roles::ADMIN, Roles::CUSTOMER]);

        $userId = $getCurrentUserId ? $getCurrentUserId() : null;
        if (!$userId) {
            Flight::json(['error' => 'User not found'], 401);
            return;
        }

        try {
            $result = Flight::cartService()->removeFromCart($userId, $cart_item_id);
            Flight::json([
                'success' => true,
                'message' => 'Item removed from cart'
            ]);
        } catch (Exception $e) {
            Flight::json(['error' => $e->getMessage()], 400);
        }
    });

    Flight::route('DELETE /clear', function () use ($getCurrentUserId) {
        Flight::auth_middleware()->authorizeRoles([Roles::ADMIN, Roles::CUSTOMER]);

        $userId = $getCurrentUserId ? $getCurrentUserId() : null;
        if (!$userId) {
            Flight::json(['error' => 'User not found'], 401);
            return;
        }

        try {
            $result = Flight::cartService()->clearCart($userId);
            Flight::json([
                'success' => true,
                'message' => 'Cart cleared'
            ]);
        } catch (Exception $e) {
            Flight::json(['error' => $e->getMessage()], 400);
        }
    });

    Flight::route('GET /validate', function () use ($getCurrentUserId) {
        Flight::auth_middleware()->authorizeRoles([Roles::ADMIN, Roles::CUSTOMER]);

        $userId = $getCurrentUserId ? $getCurrentUserId() : null;
        if (!$userId) {
            Flight::json(['error' => 'User not found'], 401);
            return;
        }

        try {
            $result = Flight::cartService()->validateCartForCheckout($userId);
            Flight::json([
                'success' => true,
                'data' => $result
            ]);
        } catch (Exception $e) {
            Flight::json(['error' => $e->getMessage()], 400);
        }
    });
});
