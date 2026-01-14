var UserService = {
  init: function () {
    if (!window.appState.user && localStorage.getItem("zimUser")) {
      window.appState.user = JSON.parse(localStorage.getItem("zimUser"));
    }

    var token = localStorage.getItem("user_token");
    if (token && token !== undefined) {
      const currentHash = window.location.hash.substring(1);
      if (currentHash === "login" || currentHash === "register") {
        const user =
          window.appState.user || JSON.parse(localStorage.getItem("zimUser"));
        const redirectHash =
          user && user.role === Constants.ADMIN_ROLE
            ? "admin-dashboard"
            : "home";
        window.location.hash = redirectHash;
      }
      return;
    }

    const loginSelector = "#loginForm, #login-form";
    $(loginSelector).each(function () {
      $(this).validate({
        submitHandler: function (form) {
          var entity = Object.fromEntries(new FormData(form).entries());
          UserService.login(entity);
        },
      });
    });

    const registerSelector = "#registerForm";
    $(registerSelector).each(function () {
      $(this).validate({
        rules: {
          fullName: { required: true },
          email: { required: true, email: true },
          password: { required: true, minlength: 8 },
          confirmPassword: { required: true, equalTo: "#registerPassword" },
          street: { required: true },
          city: { required: true },
          postalCode: { required: true },
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
    console.log("[UserService.login] Starting login with email:", entity.email);
    $.ajax({
      url: Constants.get_api_base_url() + "auth/login",
      type: "POST",
      data: JSON.stringify(entity),
      contentType: "application/json",
      dataType: "json",
      success: function (result) {
        console.log("[UserService.login] Login response received:", result);
        console.log("[UserService.login] result.data:", result.data);
        console.log(
          "[UserService.login] result.data.token:",
          result.data?.token
        );

        if (result && result.data && result.data.token) {
          console.log(
            "[UserService.login] Token found, storing in localStorage"
          );
          localStorage.setItem("user_token", result.data.token);
          console.log(
            "[UserService.login] Token stored, verifying:",
            localStorage.getItem("user_token")
          );

          var user = Object.assign({}, result.data);
          delete user.token;
          console.log("[UserService.login] User object to store:", user);
          localStorage.setItem("zimUser", JSON.stringify(user));
          console.log(
            "[UserService.login] zimUser stored, verifying:",
            localStorage.getItem("zimUser")
          );

          if (window.appState) appState.user = user;

          let redirectHash = sessionStorage.getItem("prevHash") || "home";
          if (user.role === Constants.ADMIN_ROLE) {
            redirectHash = "admin-dashboard";
          }
          console.log("[UserService.login] Redirecting to:", redirectHash);
          window.location.hash = redirectHash;

          if (window.UserService && UserService.generateMenuItems)
            UserService.generateMenuItems();
        } else {
          console.error(
            "[UserService.login] Missing token or data in response"
          );
          toastr.error("Unexpected response from server");
        }
      },
      error: function (XMLHttpRequest, textStatus, errorThrown) {
        console.error(
          "[UserService.login] Error occurred:",
          textStatus,
          errorThrown
        );
        console.error(
          "[UserService.login] Response status:",
          XMLHttpRequest.status
        );
        console.error(
          "[UserService.login] Response text:",
          XMLHttpRequest.responseText
        );
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

  register: function (entity) {
    var payload = {
      email: entity.email,
      password: entity.password,
      fullName: entity.fullName,
    };

    if (entity.phone) {
      payload.phone_number = entity.phone;
    }

    if (entity.street) {
      payload.address = entity.street;
    }
    if (entity.city) {
      payload.city = entity.city;
    }
    if (entity.postalCode) {
      payload.postal_code = entity.postalCode;
    }

    $.ajax({
      url: Constants.get_api_base_url() + "auth/register",
      type: "POST",
      data: JSON.stringify(payload),
      contentType: "application/json",
      dataType: "json",
      success: function (result) {
        if (result && result.data) {
          toastr.success("Account created successfully! Please sign in.");
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
    const token = localStorage.getItem("user_token");
    if (!token) {
      showToast("Already logged out", "info");
      if (window.UserService && UserService.generateMenuItems) {
        UserService.generateMenuItems();
      }
      return;
    }

    console.log("[UserService.logout] Starting logout process");
    console.log(
      "[UserService.logout] Before logout - localStorage keys:",
      Object.keys(localStorage)
    );

    for (let i = 0; i < localStorage.length; i++) {
      const key = localStorage.key(i);
      console.log(
        `[UserService.logout] Will remove: ${key} = ${localStorage.getItem(
          key
        )}`
      );
    }

    localStorage.clear();
    console.log("[UserService.logout] Executed localStorage.clear()");

    if (window.appState) {
      appState.user = null;
      console.log("[UserService.logout] Cleared appState.user");
    }

    console.log(
      "[UserService.logout] After logout - localStorage keys:",
      Object.keys(localStorage)
    );

    console.log(
      "[UserService.logout] localStorage is now completely empty. All data removed."
    );

    showToast("Successfully logged out", "success");
    window.location.hash = "home";
    if (window.UserService && UserService.generateMenuItems)
      UserService.generateMenuItems();

    console.log("[UserService.logout] Logout complete");
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
      url: Constants.get_api_base_url() + "users/" + userId + "/change-password",
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
