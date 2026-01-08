var CategoryService = {
  getAll: function (callback, errorCallback) {
    RestClient.get("categories", callback, errorCallback);
  },

  getById: function (categoryId, callback, errorCallback) {
    RestClient.get("categories/" + categoryId, callback, errorCallback);
  },
};
