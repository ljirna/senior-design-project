var FavoriteService = {
  addToFavorites: function (productId, callback, errorCallback) {
    RestClient.post(
      "favorites/add",
      { product_id: productId },
      callback,
      errorCallback
    );
  },

  removeFromFavorites: function (productId, callback, errorCallback) {
    RestClient.delete("favorites/remove/" + productId, callback, errorCallback);
  },

  getFavorites: function (limit, offset, callback, errorCallback) {
    const params = new URLSearchParams();
    if (limit) params.append("limit", limit);
    if (offset) params.append("offset", offset);

    const url =
      "favorites" + (params.toString() ? "?" + params.toString() : "");
    RestClient.get(url, callback, errorCallback);
  },

  isFavorited: function (productId, callback, errorCallback) {
    RestClient.get("favorites/check/" + productId, callback, errorCallback);
  },
};

window.FavoriteService = FavoriteService;
