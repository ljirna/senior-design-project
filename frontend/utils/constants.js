const Constants = {
  get_api_base_url: function () {
    if (location.hostname === 'localhost') {
      return "http://localhost/diplomski/backend/";
    } else {
      return "https://octopus-app-2bxng.ondigitalocean.app/api/";
    }
  },
  USER_ROLE: "customer",
  ADMIN_ROLE: "admin",
};

// Set PROJECT_BASE_URL after object is created
Constants.PROJECT_BASE_URL = Constants.get_api_base_url();

window.Constants = Constants;
