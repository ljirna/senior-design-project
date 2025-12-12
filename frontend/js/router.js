// ============================================
// ZIM COMMERCE - SPA ROUTER
// Updated for your file structure
// ============================================

// Global state
const appState = {
  user: JSON.parse(localStorage.getItem("zimUser")) || null,
  cart: JSON.parse(localStorage.getItem("zimCart")) || [],
  isLoading: false,
};

// Initialize app
function initApp() {
  console.log("Initializing ZIM Commerce SPA...");

  // Add CSS for router features
  addRouterStyles();

  // Initialize cart badge
  updateCartBadge();

  // Initialize dropdowns
  initDropdowns();

  // Start router
  router();

  console.log("SPA initialized successfully");
}

// Add necessary CSS
function addRouterStyles() {
  const style = document.createElement("style");
  style.textContent = `
    /* Loading overlay */
    .loading-overlay {
      position: fixed;
      top: 0;
      left: 0;
      right: 0;
      bottom: 0;
      background: rgba(248, 244, 233, 0.95);
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      z-index: 9999;
      opacity: 0;
      visibility: hidden;
      transition: all 0.3s ease;
    }
    
    .loading-overlay.active {
      opacity: 1;
      visibility: visible;
    }
    
    .loading-spinner {
      width: 50px;
      height: 50px;
      border: 4px solid #e8dfc8;
      border-top-color: #800020;
      border-radius: 50%;
      animation: spin 1s linear infinite;
      margin-bottom: 1rem;
    }
    
    .loading-text {
      color: #800020;
      font-size: 1.1rem;
      font-weight: 600;
    }
    
    @keyframes spin {
      to { transform: rotate(360deg); }
    }
    
    /* Toast messages */
    .message-toast {
      position: fixed;
      bottom: 20px;
      right: 20px;
      background: #800020;
      color: white;
      padding: 12px 24px;
      border-radius: 8px;
      z-index: 10000;
      opacity: 0;
      transform: translateX(100px);
      transition: all 0.3s ease;
      max-width: 300px;
      box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    }
    
    .message-toast.show {
      opacity: 1;
      transform: translateX(0);
    }
    
    .message-toast.error {
      background: #e74c3c;
    }
    
    .message-toast.success {
      background: #27ae60;
    }
    
    /* Cart badge */
    .cart-badge {
      position: absolute;
      top: -8px;
      right: -8px;
      background: #e74c3c;
      color: white;
      border-radius: 50%;
      width: 20px;
      height: 20px;
      font-size: 12px;
      display: flex;
      align-items: center;
      justify-content: center;
      font-weight: bold;
    }
    
    /* Auth modal */
    .auth-modal {
      position: fixed;
      top: 0;
      left: 0;
      right: 0;
      bottom: 0;
      background: rgba(0,0,0,0.7);
      display: flex;
      align-items: center;
      justify-content: center;
      z-index: 10001;
      opacity: 0;
      visibility: hidden;
      transition: all 0.3s ease;
    }
    
    .auth-modal.active {
      opacity: 1;
      visibility: visible;
    }
    
    .auth-modal-content {
      background: #f0ead6;
      border-radius: 12px;
      padding: 2rem;
      max-width: 400px;
      width: 90%;
      transform: translateY(-20px);
      transition: transform 0.3s ease;
      border: 1px solid #e8dfc8;
    }
    
    .auth-modal.active .auth-modal-content {
      transform: translateY(0);
    }
    
    /* Fade in animation */
    #app {
      animation: fadeIn 0.3s ease;
    }
    
    @keyframes fadeIn {
      from { opacity: 0.8; }
      to { opacity: 1; }
    }
  `;
  document.head.appendChild(style);
}

// Show loading spinner
function showLoading(message = "Loading...") {
  let overlay = document.getElementById("loadingOverlay");
  if (!overlay) {
    overlay = document.createElement("div");
    overlay.id = "loadingOverlay";
    overlay.className = "loading-overlay";
    overlay.innerHTML = `
      <div class="loading-spinner"></div>
      <div class="loading-text">${message}</div>
    `;
    document.body.appendChild(overlay);
  }

  setTimeout(() => overlay.classList.add("active"), 10);
  appState.isLoading = true;
}

// Hide loading spinner
function hideLoading() {
  const overlay = document.getElementById("loadingOverlay");
  if (overlay) {
    overlay.classList.remove("active");
    setTimeout(() => {
      if (overlay.parentNode) {
        overlay.parentNode.removeChild(overlay);
      }
    }, 300);
  }
  appState.isLoading = false;
}

// Show toast message
function showToast(message, type = "success", duration = 3000) {
  // Remove existing toast
  const existingToast = document.querySelector(".message-toast");
  if (existingToast) {
    existingToast.remove();
  }

  // Create new toast
  const toast = document.createElement("div");
  toast.className = `message-toast ${type}`;
  toast.textContent = message;
  document.body.appendChild(toast);

  // Show toast
  setTimeout(() => toast.classList.add("show"), 10);

  // Auto hide
  setTimeout(() => {
    toast.classList.remove("show");
    setTimeout(() => {
      if (toast.parentNode) {
        toast.parentNode.removeChild(toast);
      }
    }, 300);
  }, duration);
}

// Initialize dropdown menus
function initDropdowns() {
  const dropdowns = document.querySelectorAll(".has-dropdown");

  dropdowns.forEach((dropdown) => {
    const link = dropdown.querySelector(".nav-link");
    const menu = dropdown.querySelector(".dropdown-menu");

    if (link && menu) {
      // Desktop hover
      dropdown.addEventListener("mouseenter", () => {
        menu.style.opacity = "1";
        menu.style.visibility = "visible";
        menu.style.transform = "translateY(0)";
      });

      dropdown.addEventListener("mouseleave", () => {
        menu.style.opacity = "0";
        menu.style.visibility = "hidden";
        menu.style.transform = "translateY(10px)";
      });

      // Mobile touch
      link.addEventListener("click", (e) => {
        if (window.innerWidth <= 768) {
          e.preventDefault();
          const isVisible = menu.style.visibility === "visible";
          menu.style.opacity = isVisible ? "0" : "1";
          menu.style.visibility = isVisible ? "hidden" : "visible";
          menu.style.transform = isVisible
            ? "translateY(10px)"
            : "translateY(0)";
        }
      });
    }
  });

  // Close dropdowns when clicking outside
  document.addEventListener("click", (e) => {
    if (!e.target.closest(".has-dropdown")) {
      document.querySelectorAll(".dropdown-menu").forEach((menu) => {
        menu.style.opacity = "0";
        menu.style.visibility = "hidden";
        menu.style.transform = "translateY(10px)";
      });
    }
  });
}

// Check if page requires authentication
function requiresAuth(page) {
  const protectedPages = ["profile", "cart", "payment"];
  return protectedPages.includes(page);
}

// Show authentication modal
function showAuthModal(page) {
  const modal = document.createElement("div");
  modal.className = "auth-modal";
  modal.innerHTML = `
    <div class="auth-modal-content">
      <h2 style="color: #800020; margin-bottom: 1rem;">Sign In Required</h2>
      <p style="color: #5d4037; margin-bottom: 2rem;">Please sign in to access the ${page} page.</p>
      <div style="display: flex; gap: 1rem;">
        <button onclick="hideAuthModal()" style="flex:1; padding:0.8rem; background:transparent; border:2px solid #e8dfc8; color:#5d4037; border-radius:4px; cursor:pointer;">
          Cancel
        </button>
        <button onclick="goToLogin()" style="flex:1; padding:0.8rem; background:#800020; color:white; border:none; border-radius:4px; cursor:pointer;">
          Sign In
        </button>
      </div>
    </div>
  `;
  document.body.appendChild(modal);

  setTimeout(() => modal.classList.add("active"), 10);
}

// Hide auth modal
function hideAuthModal() {
  const modal = document.querySelector(".auth-modal");
  if (modal) {
    modal.classList.remove("active");
    setTimeout(() => {
      if (modal.parentNode) {
        modal.parentNode.removeChild(modal);
      }
    }, 300);
  }
}

// Go to login page
function goToLogin() {
  hideAuthModal();
  window.location.hash = "login";
}

// Load page content
async function loadPage(page, params = {}) {
  try {
    console.log(`Loading page: ${page}`);

    // Check authentication
    if (requiresAuth(page) && !appState.user) {
      showAuthModal(page);
      return;
    }

    showLoading(`Loading ${page}...`);

    // Special handling for product pages
    let viewName = page;
    if (page.startsWith("single-product") && params.id) {
      viewName = "single-product";
    }

    // TRY DIFFERENT PATHS for your structure
    const paths = [
      `./pages/${viewName}.html`, // Relative path
      `/diplomski/frontend/pages/${viewName}.html`, // Absolute path
      `pages/${viewName}.html`, // Simple path
      `${viewName}.html`, // Root path
    ];

    let html = null;
    let lastError = null;

    // Try each path until one works
    for (const path of paths) {
      try {
        console.log(`Trying path: ${path}`);
        const response = await fetch(path);

        if (response.ok) {
          html = await response.text();
          console.log(`Success with path: ${path}`);
          break;
        } else {
          console.log(`Path ${path} returned status: ${response.status}`);
        }
      } catch (error) {
        lastError = error;
        console.log(`Failed with path ${path}:`, error.message);
      }
    }

    if (!html) {
      throw new Error(
        `Failed to load ${viewName}.html. Tried: ${paths.join(", ")}`
      );
    }

    // Update content with fade animation
    const app = document.getElementById("app");
    app.style.opacity = "0.8";

    setTimeout(() => {
      app.innerHTML = html;
      app.style.opacity = "1";

      // Update page metadata
      updatePageTitle(page, params);
      updateActiveNavLink(page);

      // Initialize page scripts
      initPageScripts(page, params);

      hideLoading();
    }, 150);
  } catch (error) {
    console.error("Error loading page:", error);
    hideLoading();

    document.getElementById("app").innerHTML = `
      <div style="text-align:center; padding:3rem; color:#5d4037;">
        <h2 style="color:#800020; margin-bottom:1rem;">Page Loading Error</h2>
        <p style="margin-bottom:1rem;">${error.message}</p>
        <p style="margin-bottom:2rem; font-size:0.9rem; color:#8d6e63;">
          Make sure the file "pages/${page}.html" exists in your project.
        </p>
        <button onclick="window.location.hash='home'" style="padding:0.8rem 1.5rem; background:#800020; color:white; border:none; border-radius:4px; cursor:pointer;">
          Go to Homepage
        </button>
      </div>
    `;
  }
}

// Update page title
function updatePageTitle(page, params) {
  const titles = {
    home: "ZIM Commerce - Home",
    about: "About Us - ZIM Commerce",
    profile: "My Profile - ZIM Commerce",
    "single-product": "Product Details - ZIM Commerce",
    products: "Products - ZIM Commerce",
    cart: "Shopping Cart - ZIM Commerce",
    payment: "Payment - ZIM Commerce",
    login: "Sign In - ZIM Commerce",
  };

  document.title = titles[page] || "ZIM Commerce";
}

// Update active navigation link
function updateActiveNavLink(page) {
  // Remove active class from all nav links
  document.querySelectorAll(".nav-link").forEach((link) => {
    link.classList.remove("active");
  });

  // Add active class to current page link
  const navLink = document.querySelector(`[href="#${page}"]`);
  if (navLink) {
    navLink.classList.add("active");
  }

  // Highlight parent for product pages
  if (page === "products" || page.startsWith("single-product")) {
    const productsLink = document.querySelector('[href="#products"]');
    if (productsLink) {
      productsLink.classList.add("active");
    }
  }
}

// Initialize page-specific scripts
function initPageScripts(page, params) {
  console.log(`Initializing scripts for: ${page}`);

  // Reinitialize dropdowns for newly loaded content
  initDropdowns();

  // Initialize global event listeners
  initGlobalEvents();

  // Page-specific initializations
  switch (page) {
    case "single-product":
      initProductPage(params);
      break;
    case "profile":
      initProfilePage();
      break;
    case "products":
      initProductsPage(params);
      break;
    case "cart":
      initCartPage();
      break;
    case "login":
      initLoginPage();
      break;
  }
}

// Product page initialization
function initProductPage(params) {
  console.log("Initializing product page");

  // Image gallery
  const thumbnails = document.querySelectorAll(".thumbnail-item");
  const mainImage = document.getElementById("mainProductImage");

  if (thumbnails.length && mainImage) {
    thumbnails.forEach((thumb) => {
      thumb.addEventListener("click", function () {
        thumbnails.forEach((t) => t.classList.remove("active"));
        this.classList.add("active");
        const imgSrc = this.querySelector("img").src;
        mainImage.src = imgSrc;
      });
    });
  }

  // Quantity selector
  const qtyInput = document.getElementById("productQuantity");
  const decreaseBtn = document.getElementById("decreaseQty");
  const increaseBtn = document.getElementById("increaseQty");

  if (qtyInput && decreaseBtn && increaseBtn) {
    decreaseBtn.addEventListener("click", () => {
      let current = parseInt(qtyInput.value);
      if (current > 1) {
        qtyInput.value = current - 1;
      }
    });

    increaseBtn.addEventListener("click", () => {
      let current = parseInt(qtyInput.value);
      const max = parseInt(qtyInput.max) || 10;
      if (current < max) {
        qtyInput.value = current + 1;
      }
    });
  }

  // Add to cart button
  const addToCartBtn = document.getElementById("addToCartBtn");
  if (addToCartBtn) {
    addToCartBtn.addEventListener("click", () => {
      const quantity = parseInt(qtyInput?.value || 1);
      const productId = mainImage?.dataset.productId || "default";
      const productName =
        document.getElementById("productTitle")?.textContent || "Product";
      const price =
        document.getElementById("productPrice")?.textContent || "$0.00";

      addToCart(productId, productName, price, quantity);
    });
  }

  // Wishlist button
  const wishlistBtn = document.getElementById("wishlistBtn");
  if (wishlistBtn) {
    wishlistBtn.addEventListener("click", function () {
      this.classList.toggle("active");
      const icon = this.querySelector("i");
      if (icon.classList.contains("far")) {
        icon.className = "fas fa-heart";
        showToast("Added to wishlist!");
      } else {
        icon.className = "far fa-heart";
        showToast("Removed from wishlist!");
      }
    });
  }
}

// Profile page initialization
function initProfilePage() {
  console.log("Initializing profile page");

  // Check if user is logged in
  if (!appState.user) {
    window.location.hash = "login";
    return;
  }

  // Update profile info if user exists
  const profileName = document.querySelector(".profile-name");
  const profileEmail = document.querySelector(".profile-email-compact");

  if (profileName && appState.user.name) {
    profileName.textContent = appState.user.name;
  }

  if (profileEmail && appState.user.email) {
    profileEmail.textContent = appState.user.email;
  }

  // Profile photo change
  const changePhotoBtn = document.querySelector(".change-photo-btn-compact");
  if (changePhotoBtn) {
    changePhotoBtn.addEventListener("click", () => {
      showToast("Profile photo change coming soon!", "info");
    });
  }

  // Account actions
  document.querySelectorAll(".account-action-item").forEach((btn) => {
    btn.addEventListener("click", function () {
      const action = this.querySelector(".action-text").textContent;
      if (action.includes("Log Out")) {
        logout();
      } else {
        showToast(`${action} feature coming soon!`, "info");
      }
    });
  });

  // Liked items
  document.querySelectorAll(".favorite-btn.liked").forEach((btn) => {
    btn.addEventListener("click", function (e) {
      e.preventDefault();
      this.classList.toggle("liked");
      const icon = this.querySelector("i");
      icon.classList.toggle("fas");
      icon.classList.toggle("far");

      // Update count
      const itemCount = document.querySelector(".item-count");
      if (itemCount) {
        const current = parseInt(itemCount.textContent.match(/\d+/)[0] || 0);
        const newCount = this.classList.contains("liked")
          ? current + 1
          : current - 1;
        itemCount.textContent = `(${newCount})`;
      }
    });
  });
}

// Products page initialization
function initProductsPage(params) {
  console.log("Initializing products page");

  // Category filter links
  document
    .querySelectorAll('.dropdown-item[href^="#products?category="]')
    .forEach((link) => {
      link.addEventListener("click", function (e) {
        e.preventDefault();
        const href = this.getAttribute("href");
        window.location.hash = href.substring(1);
      });
    });

  // Favorite buttons
  document.querySelectorAll(".favorite-btn").forEach((btn) => {
    if (!btn.closest(".liked-item-card")) {
      btn.addEventListener("click", function () {
        this.classList.toggle("active");
        const icon = this.querySelector("i");
        if (icon.classList.contains("far")) {
          icon.className = "fas fa-heart";
          showToast("Added to favorites!");
        } else {
          icon.className = "far fa-heart";
          showToast("Removed from favorites!");
        }
      });
    }
  });
}

// Cart page initialization
function initCartPage() {
  console.log("Initializing cart page");

  if (!appState.user) {
    window.location.hash = "login";
    return;
  }

  // Load cart items
  loadCartItems();
}

// Login page initialization
function initLoginPage() {
  console.log("Initializing login page");

  const loginForm = document.getElementById("loginForm");
  if (loginForm) {
    loginForm.addEventListener("submit", function (e) {
      e.preventDefault();

      const email = document.getElementById("email")?.value;
      const password = document.getElementById("password")?.value;

      if (email && password) {
        // Mock login - in real app, call your API
        const user = {
          id: 1,
          name: "John Doe",
          email: email,
          role: "customer",
        };

        appState.user = user;
        localStorage.setItem("zimUser", JSON.stringify(user));

        showToast("Successfully logged in!", "success");

        // Redirect to previous page or home
        const prevHash = sessionStorage.getItem("prevHash") || "home";
        window.location.hash = prevHash;
      } else {
        showToast("Please fill in all fields", "error");
      }
    });
  }
}

// Global event listeners
function initGlobalEvents() {
  // Favorite buttons
  document.querySelectorAll(".favorite-btn:not(.liked)").forEach((btn) => {
    if (!btn.hasAttribute("data-listener")) {
      btn.setAttribute("data-listener", "true");
      btn.addEventListener("click", function () {
        this.classList.toggle("active");
        const icon = this.querySelector("i");
        if (icon.classList.contains("far")) {
          icon.className = "fas fa-heart";
          showToast("Added to favorites!");
        } else {
          icon.className = "far fa-heart";
          showToast("Removed from favorites!");
        }
      });
    }
  });

  // Cart icon protection
  const cartIcon = document.querySelector('.action-icon[href="#cart"]');
  if (cartIcon && !cartIcon.hasAttribute("data-listener")) {
    cartIcon.setAttribute("data-listener", "true");
    cartIcon.addEventListener("click", function (e) {
      if (!appState.user) {
        e.preventDefault();
        showAuthModal("cart");
      }
    });
  }
}

// Shopping cart functionality
function addToCart(productId, productName, price, quantity = 1) {
  if (!appState.user) {
    showAuthModal("cart");
    return;
  }

  let cart = appState.cart;
  const existingIndex = cart.findIndex((item) => item.id === productId);

  if (existingIndex > -1) {
    cart[existingIndex].quantity += quantity;
  } else {
    cart.push({
      id: productId,
      name: productName,
      price: price,
      quantity: quantity,
      image: document.getElementById("mainProductImage")?.src || "",
      addedAt: new Date().toISOString(),
    });
  }

  // Update state
  appState.cart = cart;
  localStorage.setItem("zimCart", JSON.stringify(cart));

  // Update UI
  updateCartBadge();
  showToast(`${productName} added to cart!`, "success");
}

// Update cart badge
function updateCartBadge() {
  const cart = appState.cart;
  const totalItems = cart.reduce((sum, item) => sum + item.quantity, 0);

  let badge = document.querySelector(".cart-badge");
  const cartIcon = document.querySelector('.action-icon[href="#cart"]');

  if (cartIcon && !badge) {
    badge = document.createElement("span");
    badge.className = "cart-badge";
    cartIcon.appendChild(badge);
  }

  if (badge) {
    if (totalItems > 0) {
      badge.textContent = totalItems > 9 ? "9+" : totalItems;
      badge.style.display = "flex";
    } else {
      badge.style.display = "none";
    }
  }
}

// Load cart items for cart page
function loadCartItems() {
  const cartItemsContainer = document.getElementById("cartItems");
  if (!cartItemsContainer) return;

  if (appState.cart.length === 0) {
    cartItemsContainer.innerHTML = `
      <div style="text-align:center; padding:3rem; color:#5d4037;">
        <i class="fas fa-shopping-cart" style="font-size:3rem; color:#e8dfc8; margin-bottom:1rem;"></i>
        <h3 style="color:#800020; margin-bottom:0.5rem;">Your cart is empty</h3>
        <p style="margin-bottom:1.5rem;">Add some items to get started!</p>
        <a href="#products" style="display:inline-block; padding:0.8rem 1.5rem; background:#800020; color:white; text-decoration:none; border-radius:4px;">
          Browse Products
        </a>
      </div>
    `;
    return;
  }

  // Generate cart items
  cartItemsContainer.innerHTML = appState.cart
    .map(
      (item) => `
    <div class="cart-item" style="display:flex; gap:1rem; padding:1rem; border-bottom:1px solid #e8dfc8; align-items:center;">
      <img src="${item.image}" alt="${item.name}" style="width:100px; height:100px; object-fit:cover; border-radius:4px; border:1px solid #e8dfc8;">
      <div style="flex:1;">
        <h3 style="color:#800020; margin-bottom:0.5rem;">${item.name}</h3>
        <div style="color:#5a0017; font-weight:bold; margin-bottom:0.5rem;">${item.price}</div>
        <div style="display:flex; align-items:center; gap:0.5rem; margin-bottom:0.5rem;">
          <label style="color:#5d4037;">Quantity:</label>
          <div style="display:flex; align-items:center; border:1px solid #e8dfc8; border-radius:4px;">
            <button onclick="updateCartQuantity('${item.id}', -1)" style="width:30px; height:30px; background:#f0ead6; border:none; cursor:pointer;">-</button>
            <input type="number" value="${item.quantity}" min="1" style="width:50px; height:30px; border:none; text-align:center;" readonly>
            <button onclick="updateCartQuantity('${item.id}', 1)" style="width:30px; height:30px; background:#f0ead6; border:none; cursor:pointer;">+</button>
          </div>
        </div>
        <button onclick="removeFromCart('${item.id}')" style="padding:0.5rem 1rem; background:transparent; border:1px solid #800020; color:#800020; border-radius:4px; cursor:pointer; font-size:0.9rem;">
          Remove
        </button>
      </div>
    </div>
  `
    )
    .join("");

  // Add summary
  const total = appState.cart.reduce((sum, item) => {
    const price = parseFloat(item.price.replace(/[^0-9.-]+/g, ""));
    return sum + price * item.quantity;
  }, 0);

  cartItemsContainer.innerHTML += `
    <div style="padding:1.5rem; text-align:right;">
      <div style="font-size:1.2rem; color:#800020; font-weight:bold;">
        Total: $${total.toFixed(2)}
      </div>
      <button onclick="window.location.hash='payment'" style="margin-top:1rem; padding:1rem 2rem; background:#800020; color:white; border:none; border-radius:4px; cursor:pointer; font-weight:bold;">
        Proceed to Checkout
      </button>
    </div>
  `;
}

// Update cart quantity
function updateCartQuantity(productId, change) {
  const itemIndex = appState.cart.findIndex((item) => item.id === productId);
  if (itemIndex > -1) {
    const newQuantity = appState.cart[itemIndex].quantity + change;
    if (newQuantity >= 1) {
      appState.cart[itemIndex].quantity = newQuantity;
      localStorage.setItem("zimCart", JSON.stringify(appState.cart));
      loadCartItems();
      updateCartBadge();
      showToast("Cart updated!", "success");
    }
  }
}

// Remove from cart
function removeFromCart(productId) {
  appState.cart = appState.cart.filter((item) => item.id !== productId);
  localStorage.setItem("zimCart", JSON.stringify(appState.cart));
  loadCartItems();
  updateCartBadge();
  showToast("Item removed from cart", "success");
}

// Logout function
function logout() {
  appState.user = null;
  localStorage.removeItem("zimUser");
  showToast("Successfully logged out", "success");

  // Redirect to home if on protected page
  const currentPage = parseHash().page;
  if (requiresAuth(currentPage)) {
    window.location.hash = "home";
  } else {
    router();
  }
}

// Parse URL hash
function parseHash() {
  const hash = window.location.hash.substring(1);

  if (!hash) {
    return { page: "home", params: {} };
  }

  const [pagePath, queryString] = hash.split("?");
  const params = {};

  if (queryString) {
    queryString.split("&").forEach((pair) => {
      const [key, value] = pair.split("=");
      if (key && value) {
        params[key] = decodeURIComponent(value);
      }
    });
  }

  let page = pagePath;
  if (pagePath.startsWith("single-product/")) {
    const parts = pagePath.split("/");
    page = "single-product";
    params.id = parts[1] || "";
  }

  return { page, params };
}

// Main router function
function router() {
  // Store previous hash for login redirect
  const currentHash = window.location.hash;
  if (currentHash && !currentHash.includes("login")) {
    sessionStorage.setItem("prevHash", currentHash);
  }

  const { page, params } = parseHash();
  loadPage(page, params);
}

// Event listeners
window.addEventListener("hashchange", router);
window.addEventListener("load", initApp);

// Make functions globally available
window.updateCartQuantity = updateCartQuantity;
window.removeFromCart = removeFromCart;
window.hideAuthModal = hideAuthModal;
window.goToLogin = goToLogin;
window.logout = logout;
