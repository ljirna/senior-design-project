var UserService = {
  init: function () {
    // Sync appState with localStorage if needed
    if (!window.appState.user && localStorage.getItem("zimUser")) {
      window.appState.user = JSON.parse(localStorage.getItem("zimUser"));
    }

    var token = localStorage.getItem("user_token");
    if (token && token !== undefined) {
      // Already logged in — only redirect from login/register pages
      const currentHash = window.location.hash.substring(1);
      if (currentHash === "login" || currentHash === "register") {
        window.location.hash = "home";
      }
      return;
    }

    // Initialize form validation for both possible login form IDs
    const loginSelector = "#loginForm, #login-form";
    $(loginSelector).each(function () {
      $(this).validate({
        submitHandler: function (form) {
          var entity = Object.fromEntries(new FormData(form).entries());
          UserService.login(entity);
        },
      });
    });

    // Initialize register form validation if present
    const registerSelector = "#registerForm";
    $(registerSelector).each(function () {
      $(this).validate({
        rules: {
          fullName: { required: true },
          email: { required: true, email: true },
          password: { required: true, minlength: 8 },
          confirmPassword: { required: true, equalTo: "#registerPassword" },
        },
        messages: {
          confirmPassword: { equalTo: "Passwords do not match" },
        },
        submitHandler: function (form) {
          var entity = Object.fromEntries(new FormData(form).entries());
          UserService.register(entity);
        },
      });
    });
  },

  login: function (entity) {
    $.ajax({
      url: Constants.PROJECT_BASE_URL + "auth/login",
      type: "POST",
      data: JSON.stringify(entity),
      contentType: "application/json",
      dataType: "json",
      success: function (result) {
        if (result && result.data && result.data.token) {
          localStorage.setItem("user_token", result.data.token);

          // Store complete user object without token
          var user = Object.assign({}, result.data);
          delete user.token;
          localStorage.setItem("zimUser", JSON.stringify(user));

          // Update appState if available
          if (window.appState) appState.user = user;

          // Redirect to previous page or home
          const prevHash = sessionStorage.getItem("prevHash") || "home";
          window.location.hash = prevHash;

          // Update header/menu
          if (window.UserService && UserService.generateMenuItems)
            UserService.generateMenuItems();
        } else {
          toastr.error("Unexpected response from server");
        }
      },
      error: function (XMLHttpRequest, textStatus, errorThrown) {
        try {
          var txt = XMLHttpRequest?.responseText;
          // If JSON with message
          try {
            var parsed = JSON.parse(txt);
            if (parsed?.message) txt = parsed.message;
          } catch (e) {}
          toastr.error(txt ? txt : "Error");
        } catch (e) {
          toastr.error("Error");
        }
      },
    });
  },

  register: function (entity) {
    // Map frontend field names to backend expectations
    var payload = {
      email: entity.email,
      password: entity.password,
      fullName: entity.fullName,
    };

    // Map phone to phone_number if provided
    if (entity.phone) {
      payload.phone_number = entity.phone;
    }

    $.ajax({
      url: Constants.PROJECT_BASE_URL + "auth/register",
      type: "POST",
      data: JSON.stringify(payload),
      contentType: "application/json",
      dataType: "json",
      success: function (result) {
        if (result && result.data) {
          toastr.success("Account created successfully! Please sign in.");
          // Redirect to login page
          window.location.hash = "login";
        } else {
          toastr.error("Unexpected response from server");
        }
      },
      error: function (XMLHttpRequest, textStatus, errorThrown) {
        try {
          var txt = XMLHttpRequest?.responseText;
          try {
            var parsed = JSON.parse(txt);
            if (parsed?.message) txt = parsed.message;
          } catch (e) {}
          toastr.error(txt ? txt : "Error");
        } catch (e) {
          toastr.error("Error");
        }
      },
    });
  },

  logout: function () {
    localStorage.removeItem("user_token");
    localStorage.removeItem("zimUser");
    if (window.appState) appState.user = null;
    showToast("Successfully logged out", "success");
    window.location.hash = "home";
    if (window.UserService && UserService.generateMenuItems)
      UserService.generateMenuItems();
  },

  changePassword: function (
    userId,
    currentPassword,
    newPassword,
    callback,
    errorCallback
  ) {
    const token = localStorage.getItem("user_token");
    $.ajax({
      url: Constants.PROJECT_BASE_URL + "users/" + userId + "/change-password",
      type: "POST",
      data: JSON.stringify({
        current_password: currentPassword,
        new_password: newPassword,
      }),
      contentType: "application/json",
      dataType: "json",
      beforeSend: function (xhr) {
        if (token) {
          xhr.setRequestHeader("Authentication", token);
        }
      },
      success: function (result) {
        if (callback) callback(result);
      },
      error: function (XMLHttpRequest, textStatus, errorThrown) {
        if (errorCallback) {
          try {
            var txt = XMLHttpRequest?.responseText;
            try {
              var parsed = JSON.parse(txt);
              if (parsed?.message) txt = parsed.message;
              if (parsed?.error) txt = parsed.error;
            } catch (e) {}
            errorCallback(txt ? txt : "Error changing password");
          } catch (e) {
            errorCallback("Error changing password");
          }
        }
      },
    });
  },

  generateMenuItems: function () {
    const token = localStorage.getItem("user_token");
    const user = token ? Utils.parseJwt(token)?.user : null;

    const headerActions = document.querySelector(".header-actions");

    if (user && user.role) {
      if (headerActions) {
        headerActions.innerHTML = `
          <a href="#cart" class="action-icon" id="nav-cart"><i class="fas fa-shopping-cart"></i></a>
          <button class="btn" style="margin-left:8px; background:#800020; color:white;" onclick="UserService.logout()">Logout</button>
        `;
      }

      const adminLink = document.querySelector('[href="#admin-dashboard"]');
      if (adminLink)
        adminLink.style.display =
          user.role === Constants.ADMIN_ROLE ? "block" : "none";
    } else {
      if (headerActions) {
        headerActions.innerHTML = `
          <a href="#cart" class="action-icon" id="nav-cart"><i class="fas fa-shopping-cart"></i></a>
          <a href="#login" class="btn" style="margin-left:8px; background:#800020; color:white; padding:0.5rem 0.8rem; text-decoration:none;">Login</a>
        `;
      }
      const adminLink = document.querySelector('[href="#admin-dashboard"]');
      if (adminLink) adminLink.style.display = "none";
    }
  },
};

window.UserService = UserService;
