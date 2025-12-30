var ProductService = {
  getAll: function (limit, offset, callback, errorCallback) {
    const params = new URLSearchParams();
    if (limit) params.append("limit", limit);
    if (offset) params.append("offset", offset);

    const url = "products" + (params.toString() ? "?" + params.toString() : "");
    RestClient.get(url, callback, errorCallback);
  },

  getById: function (productId, callback, errorCallback) {
    RestClient.get("products/" + productId, callback, errorCallback);
  },

  getFeatured: function (limit, callback, errorCallback) {
    const url = "products/featured" + (limit ? "?limit=" + limit : "");
    RestClient.get(url, callback, errorCallback);
  },

  getNewArrivals: function (limit, callback, errorCallback) {
    const url = "products/new-arrivals" + (limit ? "?limit=" + limit : "");
    RestClient.get(url, callback, errorCallback);
  },

  getByCategory: function (categoryId, limit, offset, callback, errorCallback) {
    const params = new URLSearchParams();
    if (limit) params.append("limit", limit);
    if (offset) params.append("offset", offset);

    const url =
      "products/category/" +
      categoryId +
      (params.toString() ? "?" + params.toString() : "");
    RestClient.get(url, callback, errorCallback);
  },

  searchProducts: function (
    searchTerm,
    limit,
    offset,
    callback,
    errorCallback
  ) {
    const params = new URLSearchParams();
    params.append("search", searchTerm);
    if (limit) params.append("limit", limit);
    if (offset) params.append("offset", offset);

    RestClient.get(
      "products/search?" + params.toString(),
      callback,
      errorCallback
    );
  },
};

window.ProductService = ProductService;
