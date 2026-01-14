var CartService = {
  isAuthenticated: function () {
    return !!localStorage.getItem("user_token");
  },

  mapItems: function (items) {
    if (!Array.isArray(items)) return [];
    return items.map(function (item) {
      var priceNumber = parseFloat(item.price ?? item.unit_price ?? 0) || 0;
      var productId = parseInt(item.product_id || item.id, 10);
      return {
        id: productId,
        cart_item_id: item.cart_item_id || null,
        name: item.name || item.product_name || "",
        price: "$" + priceNumber.toFixed(2),
        quantity: parseInt(item.quantity, 10) || 1,
        image:
          item.image_url ||
          item.image ||
          "data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='200' height='200'%3E%3Crect fill='%23e8dfc8' width='200' height='200'/%3E%3Ctext x='50%25' y='50%25' text-anchor='middle' dy='.3em' fill='%23800020' font-family='Arial' font-size='16'%3ENo Image%3C/text%3E%3C/svg%3E",
      };
    });
  },

  syncFromServer: function () {
    if (!this.isAuthenticated()) return Promise.resolve(appState.cart);

    return new Promise(function (resolve, reject) {
      RestClient.get(
        "cart/",
        function (res) {
          try {
            var mapped = CartService.mapItems(
              res?.items || res?.cart?.items || []
            );
            appState.cart = mapped;
            localStorage.setItem("zimCart", JSON.stringify(mapped));
            if (typeof updateCartBadge === "function") updateCartBadge();
            resolve(mapped);
          } catch (e) {
            reject(e);
          }
        },
        function (err) {
          reject(
            new Error(
              err?.responseJSON?.error ||
                err?.responseText ||
                "Failed to load cart"
            )
          );
        }
      );
    });
  },

  add: function (productId, quantity) {
    if (!this.isAuthenticated())
      return Promise.reject(new Error("Login required"));

    return new Promise(function (resolve, reject) {
      RestClient.post(
        "cart/add",
        { product_id: productId, quantity: quantity },
        function () {
          CartService.syncFromServer().then(resolve).catch(reject);
        },
        function (err) {
          reject(
            new Error(
              err?.responseJSON?.error ||
                err?.responseText ||
                "Could not add to cart"
            )
          );
        }
      );
    });
  },

  updateQuantity: function (cartItemId, quantity) {
    if (!this.isAuthenticated())
      return Promise.reject(new Error("Login required"));

    return new Promise(function (resolve, reject) {
      RestClient.put(
        "cart/items/" + cartItemId,
        { quantity: quantity },
        function () {
          CartService.syncFromServer().then(resolve).catch(reject);
        },
        function (err) {
          reject(
            new Error(
              err?.responseJSON?.error ||
                err?.responseText ||
                "Could not update cart"
            )
          );
        }
      );
    });
  },

  remove: function (cartItemId) {
    if (!this.isAuthenticated())
      return Promise.reject(new Error("Login required"));

    return new Promise(function (resolve, reject) {
      RestClient.delete(
        "cart/items/" + cartItemId,
        null,
        function () {
          CartService.syncFromServer().then(resolve).catch(reject);
        },
        function (err) {
          reject(
            new Error(
              err?.responseJSON?.error ||
                err?.responseText ||
                "Could not remove item"
            )
          );
        }
      );
    });
  },

  clear: function () {
    if (!this.isAuthenticated())
      return Promise.reject(new Error("Login required"));

    return new Promise(function (resolve, reject) {
      RestClient.delete(
        "cart/clear",
        null,
        function () {
          CartService.syncFromServer().then(resolve).catch(reject);
        },
        function (err) {
          reject(
            new Error(
              err?.responseJSON?.error ||
                err?.responseText ||
                "Could not clear cart"
            )
          );
        }
      );
    });
  },
};

window.CartService = CartService;
