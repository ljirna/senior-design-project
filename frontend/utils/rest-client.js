// Simple REST client wrapper using jQuery $.ajax
// Automatically prepends Constants.PROJECT_BASE_URL and sets Authentication header
const RestClient = {
  request: function (url, method, data, callback, error_callback) {
    const token = localStorage.getItem("user_token");

    // Prepare ajax settings
    const settings = {
      url: Constants.PROJECT_BASE_URL + url,
      type: method,
      dataType: "json",
      beforeSend: function (xhr) {
        if (token) xhr.setRequestHeader("Authentication", token);
      },
    };

    // Attach data when provided
    if (data !== undefined && data !== null) {
      // If data is a plain object, send as JSON
      if (typeof data === "object" && !(data instanceof FormData)) {
        settings.contentType = "application/json; charset=utf-8";
        settings.data = JSON.stringify(data);
      } else {
        // Allow FormData or raw strings
        settings.contentType = false;
        settings.processData = false;
        settings.data = data;
      }
    }

    $.ajax(settings)
      .done(function (response, status, jqXHR) {
        if (callback) callback(response);
      })
      .fail(function (jqXHR, textStatus, errorThrown) {
        if (error_callback) {
          error_callback(jqXHR);
        } else {
          if (window.toastr && jqXHR?.responseJSON?.message) {
            toastr.error(jqXHR.responseJSON.message);
          } else if (jqXHR?.responseText) {
            console.error("Request failed:", jqXHR.responseText);
          } else {
            console.error("Request failed", textStatus, errorThrown);
          }
        }
      });
  },

  get: function (url, callback, error_callback) {
    const token = localStorage.getItem("user_token");
    $.ajax({
      url: Constants.PROJECT_BASE_URL + url,
      type: "GET",
      dataType: "json",
      beforeSend: function (xhr) {
        if (token) xhr.setRequestHeader("Authentication", token);
      },
      success: function (response) {
        if (callback) callback(response);
      },
      error: function (jqXHR, textStatus, errorThrown) {
        if (error_callback) error_callback(jqXHR);
        else {
          if (window.toastr && jqXHR?.responseJSON?.message) {
            toastr.error(jqXHR.responseJSON.message);
          } else {
            console.error("GET request failed", jqXHR);
          }
        }
      },
    });
  },

  post: function (url, data, callback, error_callback) {
    RestClient.request(url, "POST", data, callback, error_callback);
  },

  put: function (url, data, callback, error_callback) {
    RestClient.request(url, "PUT", data, callback, error_callback);
  },

  patch: function (url, data, callback, error_callback) {
    RestClient.request(url, "PATCH", data, callback, error_callback);
  },

  delete: function (url, data, callback, error_callback) {
    RestClient.request(url, "DELETE", data, callback, error_callback);
  },
};

window.RestClient = RestClient;
