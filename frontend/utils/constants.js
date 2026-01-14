const Constants = {
  get_api_base_url: function () {
    if (location.hostname === 'localhost') {
      return "http://localhost/diplomski/backend/";
    } else {
      return "https://octopus-app-2bxng.ondigitalocean.app/";
    }
  },
  PROJECT_BASE_URL: function () {
    return this.get_api_base_url();
  }(),
  USER_ROLE: "customer",
  ADMIN_ROLE: "admin",
};

window.Constants = Constants;
