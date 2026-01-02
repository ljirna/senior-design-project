// ============================================
// ZIM COMMERCE - SPA ROUTER
// Updated with registration and admin pages
// ============================================

// Helper function to deduplicate favorites
function deduplicateFavorites(favorites) {
  const seen = new Set();
  return favorites.filter((item) => {
    if (seen.has(item.id)) {
      return false;
    }
    seen.add(item.id);
    return true;
  });
}

// Global state
const appState = {
  user: JSON.parse(localStorage.getItem("zimUser")) || null,
  cart: JSON.parse(localStorage.getItem("zimCart")) || [],
  favorites: deduplicateFavorites(
    JSON.parse(localStorage.getItem("zimFavorites")) || []
  ),
  isLoading: false,
};

// Ensure no duplicates in localStorage on page load
localStorage.setItem("zimFavorites", JSON.stringify(appState.favorites));

// Expose appState globally
window.appState = appState;

// Function to hide/show navbar on admin pages
function toggleNavbarOnAdminPages(page) {
  const header = document.querySelector(".main-header");
  const footer = document.querySelector(".main-footer");

  if (!header || !footer) return;

  // Hide header and footer on admin pages
  if (page.startsWith("admin-")) {
    header.style.display = "none";
    footer.style.display = "none";

    // Also hide any admin link in the navbar if it exists
    const adminLink = document.querySelector('[href="#admin-dashboard"]');
    if (adminLink) {
      adminLink.style.display = "none";
    }
  } else {
    header.style.display = "";
    footer.style.display = "";

    // Show admin link only if user is admin
    const adminLink = document.querySelector('[href="#admin-dashboard"]');
    if (adminLink) {
      if (isAdmin()) {
        adminLink.style.display = "block";
      } else {
        adminLink.style.display = "none";
      }
    }
  }
}

// Initialize app
function initApp() {
  console.log("Initializing ZIM Commerce SPA...");

  // Add CSS for router features
  addRouterStyles();

  // Initialize cart badge
  updateCartBadge();

  // Initialize dropdowns
  initDropdowns();

  // Update header/menu items based on auth
  if (window.UserService && UserService.generateMenuItems)
    UserService.generateMenuItems();

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

// Check if page requires admin privileges
function requiresAdmin(page) {
  return page.startsWith("admin-");
}

// Check if user is admin
function isAdmin() {
  return appState.user && appState.user.role === "admin";
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
        <button onclick="goToRegister()" style="flex:1; padding:0.8rem; background:#5d4037; color:white; border:none; border-radius:4px; cursor:pointer;">
          Register
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

// Go to register page
function goToRegister() {
  hideAuthModal();
  window.location.hash = "register";
}

// Enable admin CSS when on admin pages
function enableAdminCSS() {
  const adminCSS = document.getElementById("admin-css");
  if (adminCSS) {
    adminCSS.disabled = false;
  }
}

// Disable admin CSS when leaving admin pages
function disableAdminCSS() {
  const adminCSS = document.getElementById("admin-css");
  if (adminCSS) {
    adminCSS.disabled = true;
  }
}

// Load page content
async function loadPage(page, params = {}) {
  try {
    console.log(`Loading page: ${page}`);

    // Check authentication for protected pages
    if (requiresAuth(page) && !appState.user) {
      showAuthModal(page);
      return;
    }

    // Check admin privileges for admin pages
    if (requiresAdmin(page) && !isAdmin()) {
      showToast("Access denied. Admin privileges required.", "error");
      window.location.hash = "home";
      return;
    }

    // Hide navbar on admin pages
    toggleNavbarOnAdminPages(page);

    showLoading(`Loading ${page}...`);

    // Special handling for product pages
    let viewName = page;
    if (page.startsWith("single-product") && params.id) {
      viewName = "single-product";
    }

    // Special handling for admin order detail
    if (page.startsWith("admin-order-detail") && params.id) {
      viewName = "admin-order-detail";
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

      // Enable/disable admin CSS based on page
      if (requiresAdmin(page)) {
        enableAdminCSS();
      } else {
        disableAdminCSS();
      }

      hideLoading();
    }, 150);
  } catch (error) {
    console.error("Error loading page:", error);
    hideLoading();

    // Disable admin CSS on error
    disableAdminCSS();

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
    register: "Create Account - ZIM Commerce",
    "forgot-password": "Reset Password - ZIM Commerce",
    "admin-dashboard": "Admin Dashboard - ZIM Commerce",
    "admin-orders": "Order Management - ZIM Commerce",
    "admin-products": "Product Management - ZIM Commerce",
    "admin-users": "User Management - ZIM Commerce",
    "admin-categories": "Category Management - ZIM Commerce",
    "admin-settings": "Store Settings - ZIM Commerce",
    "admin-reports": "Reports - ZIM Commerce",
    "admin-order-detail": "Order Details - ZIM Commerce",
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

  // Show/hide admin link based on user role AND current page
  const adminLink = document.querySelector('[href="#admin-dashboard"]');
  if (adminLink) {
    // Don't show admin link on admin pages (navbar is hidden anyway)
    if (page.startsWith("admin-")) {
      adminLink.style.display = "none";
    } else if (isAdmin()) {
      adminLink.style.display = "block";
    } else {
      adminLink.style.display = "none";
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
    case "home":
      initHomePage();
      break;
    case "single-product":
      initProductPage(params);
      break;
    case "profile":
      initProfilePage();
      break;
    case "products":
      initProductsPage(params);
      break;
    case "favorite":
      initFavoritesPage();
      break;
    case "cart":
      initCartPage();
      break;
    case "payment":
      initPaymentPage();
      break;
    case "login":
      initLoginPage();
      break;
    case "register":
      initRegisterPage();
      break;
    case "admin-dashboard":
      initAdminDashboard();
      break;
    case "admin-orders":
      initAdminOrders();
      break;
    case "admin-products":
      initAdminProducts();
      break;
    case "admin-users":
      initAdminUsers();
      break;
    case "admin-categories":
      initAdminCategories();
      break;
    case "admin-settings":
      initAdminSettings();
      break;
    case "admin-reports":
      initAdminReports();
      break;
    case "admin-order-detail":
      initAdminOrderDetail(params);
      break;
  }
}

// Home page initialization
function initHomePage() {
  console.log("Initializing home page");

  const productsGrid = document.querySelector(".products-grid");
  if (!productsGrid) return;

  // Show loading state
  productsGrid.innerHTML =
    '<div style="text-align:center; padding:2rem;">Loading products...</div>';

  // Fetch featured products
  ProductService.getFeatured(
    3,
    function (products) {
      if (products && products.length > 0) {
        productsGrid.innerHTML = products
          .map(
            (product) => `
        <div class="product-card">
          <button class="favorite-btn" aria-label="Add to favorites">
            <i class="far fa-heart"></i>
          </button>
          <img
            src="${
              product.image_url ||
              "https://images.unsplash.com/photo-1540574163026-643ea20ade25?w=600"
            }"
            alt="${product.name}"
            class="product-image"
          />
          <div class="product-content">
            <h3 class="product-title">${product.name}</h3>
            <p class="product-category">${
              product.category_name || "Furniture"
            }</p>
            <div class="product-price">${parseFloat(product.price).toFixed(
              2
            )} KM</div>
            <div class="product-actions">
              <a href="#single-product/${
                product.product_id
              }" class="btn-see-more">View Details</a>
            </div>
          </div>
        </div>
      `
          )
          .join("");

        // Initialize favorite buttons
        initFavoriteButtons();
      } else {
        productsGrid.innerHTML =
          '<div style="text-align:center; padding:2rem;">No products available</div>';
      }
    },
    function (error) {
      productsGrid.innerHTML =
        '<div style="text-align:center; padding:2rem; color:red;">Failed to load products</div>';
      console.error("Failed to load products:", error);
    }
  );
}

// Product page initialization
function initProductPage(params) {
  console.log("Initializing product page with params:", params);

  // Get product ID from params
  const productId = params.id;

  if (!productId) {
    showToast("Product not found", "error");
    window.location.hash = "products";
    return;
  }

  // Show loading state
  const productContent = document.getElementById("productContent");
  if (productContent) {
    productContent.innerHTML =
      '<div style="text-align:center; padding:4rem;">Loading product...</div>';
  }

  // Fetch product data
  ProductService.getById(
    productId,
    function (product) {
      console.log("Product loaded:", product);
      displayProductDetails(product);
      loadRelatedProducts(product.category_id, productId);
      initProductInteractions(product);
    },
    function (error) {
      console.error("Error loading product:", error);
      showToast("Failed to load product details", "error");
      if (productContent) {
        productContent.innerHTML =
          '<div style="text-align:center; padding:4rem;">Failed to load product. <a href="#products">Back to products</a></div>';
      }
    }
  );
}

function displayProductDetails(product) {
  const productContent = document.getElementById("productContent");
  if (!productContent) return;

  // Build images array (use images array if available, otherwise use single image_url)
  const images =
    product.images && product.images.length > 0
      ? product.images.map((img) => img.image_url)
      : product.image_url
      ? [product.image_url]
      : [];

  const mainImage =
    images[0] ||
    "https://images.unsplash.com/photo-1555041469-a586c61ea9bc?w=800";

  productContent.innerHTML = `
    <!-- Product Gallery Section -->
    <section class="product-gallery-section" aria-label="Product images">
      <div class="product-main-image">
        ${
          images.length > 1
            ? `
          <button class="carousel-btn carousel-prev" id="carouselPrev" aria-label="Previous image">
            <i class="fas fa-chevron-left"></i>
          </button>
        `
            : ""
        }
        <img
          id="mainProductImage"
          src="${mainImage}"
          alt="${product.name}"
          data-product-id="${product.product_id}"
          data-current-index="0"
        />
        ${
          images.length > 1
            ? `
          <button class="carousel-btn carousel-next" id="carouselNext" aria-label="Next image">
            <i class="fas fa-chevron-right"></i>
          </button>
          <div class="carousel-indicators">
            ${images
              .map(
                (_, index) => `
              <span class="indicator ${
                index === 0 ? "active" : ""
              }" data-index="${index}"></span>
            `
              )
              .join("")}
          </div>
        `
            : ""
        }
      </div>

      ${
        images.length > 1
          ? `
        <div class="thumbnail-gallery">
          ${images
            .map(
              (img, index) => `
            <div class="thumbnail-item ${
              index === 0 ? "active" : ""
            }" data-image-index="${index}">
              <img src="${img}" alt="${product.name} - Image ${index + 1}" />
            </div>
          `
            )
            .join("")}
        </div>
      `
          : ""
      }
    </section>

    <!-- Product Info Section -->
    <section class="product-info-section" aria-label="Product information">
      <!-- Product Header -->
      <div class="product-header">
        <span class="product-category">${
          product.category_name || "Furniture"
        }</span>
        <h1 class="product-title" id="productTitle">${product.name}</h1>
        <p class="product-subtitle">${product.description || ""}</p>

        <!-- Rating & Stock -->
        <div class="product-meta-row">
        </div>
      </div>

      <!-- Price Section -->
      <div class="product-price-section">
        <div class="price-display">
          <span class="current-price" id="productPrice">${parseFloat(
            product.price
          ).toFixed(2)} KM</span>
        </div>
        <p class="price-note">Free shipping & 30-day return policy</p>
      </div>

      <!-- Additional Fees -->
      ${
        product.delivery_fee_override || product.assembly_fee_override
          ? `
        <ul class="product-details-list">
          ${
            product.delivery_fee_override
              ? `
            <li>
              <i class="fas fa-truck"></i>
              <span>Delivery fee: ${parseFloat(
                product.delivery_fee_override
              ).toFixed(2)} KM</span>
            </li>
          `
              : ""
          }
          ${
            product.assembly_fee_override
              ? `
            <li>
              <i class="fas fa-tools"></i>
              <span>Assembly fee: ${parseFloat(
                product.assembly_fee_override
              ).toFixed(2)} KM</span>
            </li>
          `
              : ""
          }
        </ul>
      `
          : ""
      }

      <!-- Quantity and Action Buttons -->
      <div class="quantity-actions-section">
        <div class="quantity-selector">
          <button type="button" class="qty-btn" id="decreaseQty" aria-label="Decrease quantity">
            <i class="fas fa-minus"></i>
          </button>
          <input type="number" class="qty-input" id="productQuantity" value="1" min="1" max="10" aria-label="Quantity" />
          <button type="button" class="qty-btn" id="increaseQty" aria-label="Increase quantity">
            <i class="fas fa-plus"></i>
          </button>
        </div>

        <div class="action-buttons">
          <button type="button" class="add-to-cart-btn" id="addToCartBtn">
            <i class="fas fa-shopping-cart"></i>
            Add to Cart
          </button>
          <button type="button" class="wishlist-btn" id="wishlistBtn" aria-label="Add to wishlist">
            <i class="far fa-heart"></i>
          </button>
        </div>
      </div>
    </section>
  `;
}

function loadRelatedProducts(categoryId, currentProductId) {
  const relatedGrid = document.querySelector(
    ".related-products .products-grid"
  );
  if (!relatedGrid) return;

  relatedGrid.innerHTML =
    '<div style="text-align:center; padding:2rem;">Loading related products...</div>';

  ProductService.getByCategory(
    categoryId,
    4,
    0,
    function (products) {
      // Filter out current product and limit to 4
      const relatedProducts = products
        .filter((p) => p.product_id != currentProductId)
        .slice(0, 4);
      displayProducts(relatedProducts, relatedGrid);
    },
    function (error) {
      console.error("Error loading related products:", error);
      relatedGrid.innerHTML = "";
    }
  );
}

function initProductInteractions(product) {
  const mainImage = document.getElementById("mainProductImage");
  const thumbnails = document.querySelectorAll(".thumbnail-item");
  const images =
    product.images && product.images.length > 0
      ? product.images.map((img) => img.image_url)
      : product.image_url
      ? [product.image_url]
      : [];

  // Carousel navigation
  if (images.length > 1) {
    const carouselPrev = document.getElementById("carouselPrev");
    const carouselNext = document.getElementById("carouselNext");
    const indicators = document.querySelectorAll(".indicator");

    function updateImage(index) {
      if (index < 0) index = images.length - 1;
      if (index >= images.length) index = 0;

      mainImage.src = images[index];
      mainImage.dataset.currentIndex = index;

      // Update thumbnails
      thumbnails.forEach((t, i) => {
        t.classList.toggle("active", i === index);
      });

      // Update indicators
      indicators.forEach((ind, i) => {
        ind.classList.toggle("active", i === index);
      });
    }

    if (carouselPrev) {
      carouselPrev.addEventListener("click", () => {
        const currentIndex = parseInt(mainImage.dataset.currentIndex || 0);
        updateImage(currentIndex - 1);
      });
    }

    if (carouselNext) {
      carouselNext.addEventListener("click", () => {
        const currentIndex = parseInt(mainImage.dataset.currentIndex || 0);
        updateImage(currentIndex + 1);
      });
    }

    // Indicator clicks
    indicators.forEach((indicator) => {
      indicator.addEventListener("click", function () {
        const index = parseInt(this.dataset.index);
        updateImage(index);
      });
    });

    // Keyboard navigation
    document.addEventListener("keydown", function (e) {
      if (e.key === "ArrowLeft") {
        const currentIndex = parseInt(mainImage.dataset.currentIndex || 0);
        updateImage(currentIndex - 1);
      } else if (e.key === "ArrowRight") {
        const currentIndex = parseInt(mainImage.dataset.currentIndex || 0);
        updateImage(currentIndex + 1);
      }
    });
  }

  // Thumbnail clicks
  if (thumbnails.length && mainImage) {
    thumbnails.forEach((thumb) => {
      thumb.addEventListener("click", function () {
        const index = parseInt(this.dataset.imageIndex);
        mainImage.src = images[index];
        mainImage.dataset.currentIndex = index;

        thumbnails.forEach((t) => t.classList.remove("active"));
        this.classList.add("active");

        // Update indicators
        const indicators = document.querySelectorAll(".indicator");
        indicators.forEach((ind, i) => {
          ind.classList.toggle("active", i === index);
        });
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

// Fetch currently authenticated user profile from backend
async function fetchCurrentUserProfile() {
  const token = localStorage.getItem("user_token");
  if (!token) {
    throw new Error("Authentication required");
  }

  return new Promise((resolve, reject) => {
    RestClient.get(
      "users/profile/me",
      (response) => resolve(response),
      (jqXHR) => {
        const message =
          jqXHR?.responseJSON?.error ||
          jqXHR?.responseJSON?.message ||
          "Unable to load profile";
        reject(new Error(message));
      }
    );
  });
}

// Update user profile details on backend
async function updateUserProfile(userId, payload) {
  return new Promise((resolve, reject) => {
    RestClient.put(
      `users/${userId}`,
      payload,
      (response) => {
        if (response?.success) {
          resolve(response.data || payload);
        } else {
          reject(
            new Error(
              response?.error || response?.message || "Failed to update profile"
            )
          );
        }
      },
      (jqXHR) => {
        const message =
          jqXHR?.responseJSON?.error ||
          jqXHR?.responseJSON?.message ||
          jqXHR?.responseText ||
          "Failed to update profile";
        reject(new Error(message));
      }
    );
  });
}

// Open edit profile modal
function openEditProfileModal(user) {
  const modal = document.getElementById("editProfileModal");
  if (!modal) return;

  // Populate form with current user data
  document.getElementById("editFullName").value =
    user.full_name || user.name || "";
  document.getElementById("editEmail").value = user.email || "";
  document.getElementById("editPhone").value =
    user.phone_number || user.phone || "";
  document.getElementById("editAddress").value = user.address || "";
  document.getElementById("editCity").value = user.city || "";
  document.getElementById("editPostal").value = user.postal_code || "";

  modal.style.display = "flex";

  // Handle form submission
  const form = document.getElementById("editProfileForm");
  form.onsubmit = async (e) => {
    e.preventDefault();

    const updatedUser = {
      full_name: document.getElementById("editFullName").value,
      email: document.getElementById("editEmail").value,
      phone_number: document.getElementById("editPhone").value,
      address: document.getElementById("editAddress").value,
      city: document.getElementById("editCity").value,
      postal_code: document.getElementById("editPostal").value,
    };

    const submitButton = form.querySelector(".btn-save");
    if (submitButton) {
      submitButton.disabled = true;
      submitButton.textContent = "Saving...";
    }

    try {
      const updatedUserResponse = await updateUserProfile(
        user.user_id,
        updatedUser
      );

      // Merge existing user data with updated fields
      let updatedUserData = {
        ...user,
        ...updatedUserResponse,
      };

      // Refresh from backend to ensure we persist what is stored in DB
      try {
        const fresh = await fetchCurrentUserProfile();
        updatedUserData = { ...updatedUserData, ...fresh };
      } catch (refreshErr) {
        console.warn("Could not refresh profile after update", refreshErr);
      }

      appState.user = updatedUserData;
      localStorage.setItem("zimUser", JSON.stringify(updatedUserData));

      modal.style.display = "none";
      showToast("Profile updated successfully!", "success");
      initProfilePage();
    } catch (error) {
      console.error("Error updating profile:", error);
      showToast(error.message || "Failed to update profile", "error");
    } finally {
      if (submitButton) {
        submitButton.disabled = false;
        submitButton.textContent = "Save Changes";
      }
    }
  };

  // Handle close button
  const closeBtn = modal.querySelector(".close-modal");
  if (closeBtn) {
    closeBtn.onclick = () => {
      modal.style.display = "none";
    };
  }

  // Handle cancel button
  const cancelBtn = modal.querySelector(".btn-cancel");
  if (cancelBtn) {
    cancelBtn.onclick = () => {
      modal.style.display = "none";
    };
  }

  // Close modal when clicking outside
  window.onclick = (event) => {
    if (event.target === modal) {
      modal.style.display = "none";
    }
  };
}

// Profile page initialization
async function initProfilePage() {
  console.log("Initializing profile page");

  // Sync appState.user from localStorage if not set
  if (!appState.user && localStorage.getItem("zimUser")) {
    appState.user = JSON.parse(localStorage.getItem("zimUser"));
  }

  const token = localStorage.getItem("user_token");

  // Check if user is logged in
  if (!appState.user && !token) {
    showToast("Please log in to view your profile", "error");
    window.location.hash = "login";
    return;
  }

  // Try to refresh profile from backend to keep data current
  if (token) {
    try {
      showLoading("Loading profile...");
      const profile = await fetchCurrentUserProfile();
      appState.user = { ...appState.user, ...profile };
      localStorage.setItem("zimUser", JSON.stringify(appState.user));
    } catch (error) {
      console.error("Failed to refresh profile:", error);
      showToast(error.message || "Unable to load profile", "error");
    } finally {
      hideLoading();
    }
  }

  // Get user from appState (which is synced with localStorage.zimUser)
  const user = appState.user;

  if (!user) {
    showToast("Please log in to view your profile", "error");
    window.location.hash = "login";
    return;
  }

  // Update profile header
  const profileName = document.querySelector(".profile-name");
  const profileEmail = document.querySelector(".profile-email-compact");

  if (profileName) {
    profileName.textContent = user.full_name || user.name || "User";
  }

  if (profileEmail) {
    profileEmail.textContent = user.email || "";
  }

  // Update personal information fields with IDs
  const profilePhone = document.getElementById("profile-phone");
  const profileAddress = document.getElementById("profile-address");
  const profileCity = document.getElementById("profile-city");
  const profilePostal = document.getElementById("profile-postal");
  const profileRole = document.getElementById("profile-role");
  const profileCreated = document.getElementById("profile-created");

  if (profilePhone) {
    profilePhone.textContent =
      user.phone_number || user.phone || "Not provided";
  }

  if (profileAddress) {
    profileAddress.textContent = user.address || "Not provided";
  }

  if (profileCity) {
    profileCity.textContent = user.city || "Not provided";
  }

  if (profilePostal) {
    profilePostal.textContent = user.postal_code || "Not provided";
  }

  if (profileRole) {
    const roleText = user.role === "admin" ? "Administrator" : "Customer";
    profileRole.textContent = roleText;
  }

  if (profileCreated && user.created_at) {
    // Format the date nicely
    const date = new Date(user.created_at);
    const options = { year: "numeric", month: "long", day: "numeric" };
    profileCreated.textContent = date.toLocaleDateString("en-US", options);
  } else if (profileCreated) {
    profileCreated.textContent = "Recently";
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
    if (btn.dataset.bound === "true") return;
    btn.dataset.bound = "true";
    btn.addEventListener("click", function () {
      const action = this.querySelector(".action-text").textContent;
      if (action.includes("Log Out")) {
        logout();
      } else if (action.includes("Edit Profile")) {
        // Always open with the freshest user data
        const latestUser = appState.user || user;
        openEditProfileModal(latestUser);
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
  console.log("Initializing products page with params:", params);

  const productsGrid = document.querySelector(".products-grid");
  if (!productsGrid) return;

  // Show loading state
  productsGrid.innerHTML =
    '<div style="text-align:center; padding:2rem;">Loading products...</div>';

  // Map category slugs to category IDs
  const categoryMap = {
    chair: 1,
    bed: 2,
    kitchen: 3,
    living: 4,
    bedroom: 5,
    dining: 6,
    office: 7,
    outdoor: 8,
  };

  // Determine what to load based on params
  const categoryParam = params.category;

  if (categoryParam && categoryMap[categoryParam]) {
    // Load products by category ID
    const categoryId = categoryMap[categoryParam];
    ProductService.getByCategory(
      categoryId,
      50,
      0,
      function (products) {
        displayProducts(products, productsGrid);
      },
      handleProductsError
    );
  } else {
    // Load all products
    ProductService.getAll(
      50,
      0,
      function (products) {
        displayProducts(products, productsGrid);
      },
      handleProductsError
    );
  }

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
}

// Helper function to display products
function displayProducts(products, container) {
  if (products && products.length > 0) {
    container.innerHTML = products
      .map(
        (product) => `
      <div class="product-card">
        <button class="favorite-btn" aria-label="Add to favorites">
          <i class="far fa-heart"></i>
        </button>
        <img
          src="${
            product.image_url ||
            "https://images.unsplash.com/photo-1540574163026-643ea20ade25?w=600"
          }"
          alt="${product.name}"
          class="product-image"
        />
        <div class="product-content">
          <h3 class="product-title">${product.name}</h3>
          <p class="product-category">${
            product.category_name || "Furniture"
          }</p>
          <div class="product-price">${parseFloat(product.price).toFixed(
            2
          )} KM</div>
          <div class="product-actions">
            <a href="#single-product/${
              product.product_id
            }" class="btn-see-more">View Details</a>
          </div>
        </div>
      </div>
    `
      )
      .join("");

    // Initialize favorite buttons
    initFavoriteButtons();
  } else {
    container.innerHTML =
      '<div style="text-align:center; padding:2rem;">No products available</div>';
  }
}

// Helper function to handle products error
function handleProductsError(error) {
  const productsGrid = document.querySelector(".products-grid");
  if (productsGrid) {
    productsGrid.innerHTML =
      '<div style="text-align:center; padding:2rem; color:red;">Failed to load products</div>';
  }
  console.error("Failed to load products:", error);
}

// Initialize favorite buttons
function initFavoriteButtons() {
  document.querySelectorAll(".favorite-btn").forEach((btn) => {
    if (!btn.dataset.initialized) {
      btn.dataset.initialized = "true";

      // Get product ID from the card's link
      const productCard = btn.closest(".product-card");
      const productId = productCard
        ?.querySelector(".btn-see-more")
        ?.href?.match(/single-product\/(\d+)/)?.[1];

      // Check if this product is already in favorites
      const isFavorited = appState.favorites.some((fav) => fav.id == productId);
      const icon = btn.querySelector("i");

      if (isFavorited) {
        btn.classList.add("active");
        icon.className = "fas fa-heart";
      } else {
        btn.classList.remove("active");
        icon.className = "far fa-heart";
      }

      btn.addEventListener("click", function (e) {
        e.preventDefault();
        this.classList.toggle("active");
        const icon = this.querySelector("i");
        const productCard = this.closest(".product-card");
        const productName =
          productCard?.querySelector(".product-title")?.textContent ||
          "Product";
        const productImage =
          productCard?.querySelector(".product-image")?.src || "";
        const productPrice =
          productCard?.querySelector(".product-price")?.textContent || "0";
        const productId = productCard
          ?.querySelector(".btn-see-more")
          ?.href?.match(/single-product\/(\d+)/)?.[1];

        if (icon.classList.contains("far")) {
          icon.className = "fas fa-heart";
          addToFavorites(productId, productName, productPrice, productImage);
          showToast("Added to favorites!", "success");
        } else {
          icon.className = "far fa-heart";
          removeFromFavorites(productId);
          showToast("Removed from favorites!", "info");
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
    // Use UserService.init to attach validation/submit handler (jQuery validate)
    if (window.UserService && UserService.init) UserService.init();
  }

  // Register button
  const goToRegisterBtn = document.getElementById("goToRegisterBtn");
  if (goToRegisterBtn) {
    goToRegisterBtn.addEventListener("click", function () {
      window.location.hash = "register";
    });
  }

  // Forgot password link
  const forgotPasswordLink = document.querySelector(
    'a[href="#forgot-password"]'
  );
  if (forgotPasswordLink) {
    forgotPasswordLink.addEventListener("click", function (e) {
      e.preventDefault();
      showToast("Password reset feature coming soon!", "info");
    });
  }
}

// Register page initialization
function initRegisterPage() {
  console.log("Initializing register page");

  // Delegate registration validation & submit to UserService
  if (window.UserService && UserService.init) UserService.init();

  // Back to login button
  const goToLoginBtn = document.getElementById("goToLoginBtn");
  if (goToLoginBtn) {
    goToLoginBtn.addEventListener("click", function () {
      window.location.hash = "login";
    });
  }

  // Terms and privacy links
  document
    .querySelectorAll('a[href="#terms"], a[href="#privacy"]')
    .forEach((link) => {
      link.addEventListener("click", function (e) {
        e.preventDefault();
        showToast("Terms & Privacy pages coming soon!", "info");
      });
    });
}

// ADMIN PAGE INITIALIZATIONS

function initAdminDashboard() {
  console.log("Initializing admin dashboard");
  enableAdminCSS();

  // Update current date and time
  function updateDateTime() {
    const now = new Date();
    const options = {
      weekday: "long",
      year: "numeric",
      month: "long",
      day: "numeric",
      hour: "2-digit",
      minute: "2-digit",
    };
    const dateTimeElement = document.getElementById("currentDateTime");
    if (dateTimeElement) {
      dateTimeElement.textContent = now.toLocaleDateString("en-US", options);
    }
  }

  updateDateTime();
  setInterval(updateDateTime, 60000);
}

function initAdminOrders() {
  console.log("Initializing admin orders");
  enableAdminCSS();

  // Initialize order management functionality
  const selectAllCheckbox = document.getElementById("selectAll");
  if (selectAllCheckbox) {
    selectAllCheckbox.addEventListener("change", function () {
      const checkboxes = document.querySelectorAll(
        'input[type="checkbox"]:not(#selectAll)'
      );
      checkboxes.forEach((checkbox) => {
        checkbox.checked = this.checked;
      });
    });
  }
}

function initAdminProducts() {
  console.log("Initializing admin products");
  enableAdminCSS();

  // Initialize product management functionality
  const imageUploadArea = document.querySelector(".image-upload-area");
  if (imageUploadArea) {
    imageUploadArea.addEventListener("click", function () {
      const input = document.createElement("input");
      input.type = "file";
      input.accept = "image/*";
      input.multiple = true;
      input.click();

      input.addEventListener("change", function (e) {
        const files = e.target.files;
        if (files.length > 0) {
          showToast(`${files.length} image(s) selected for upload`, "success");
        }
      });
    });
  }
}

function initAdminUsers() {
  console.log("Initializing admin users");
  enableAdminCSS();
}

function initAdminCategories() {
  console.log("Initializing admin categories");
  enableAdminCSS();
}

function initAdminSettings() {
  console.log("Initializing admin settings");
  enableAdminCSS();
}

function initAdminReports() {
  console.log("Initializing admin reports");
  enableAdminCSS();
}

function initAdminOrderDetail(params) {
  console.log("Initializing order detail:", params.id);
  enableAdminCSS();

  // Load order details based on params.id
  if (params.id) {
    console.log(`Loading order #${params.id} details`);
    // Here you would typically fetch order details from an API
  }
}

// Global event listeners
function initGlobalEvents() {
  // Favorite buttons
  document.querySelectorAll(".favorite-btn").forEach((btn) => {
    if (!btn.hasAttribute("data-listener")) {
      btn.setAttribute("data-listener", "true");

      // Get product ID from the card's link
      const productCard = btn.closest(".product-card");
      const productId = productCard
        ?.querySelector(".btn-see-more")
        ?.href?.match(/single-product\/(\d+)/)?.[1];

      // Check if this product is already in favorites
      const isFavorited = appState.favorites.some((fav) => fav.id == productId);
      const icon = btn.querySelector("i");

      if (isFavorited) {
        btn.classList.add("active");
        icon.className = "fas fa-heart";
      } else {
        btn.classList.remove("active");
        icon.className = "far fa-heart";
      }

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

// Lazy-load Stripe.js
let stripeLibPromise = null;
function loadStripeLibrary() {
  if (window.Stripe) return Promise.resolve();
  if (!stripeLibPromise) {
    stripeLibPromise = new Promise((resolve, reject) => {
      const script = document.createElement("script");
      script.src = "https://js.stripe.com/v3/";
      script.onload = () => resolve();
      script.onerror = () => reject(new Error("Failed to load Stripe.js"));
      document.head.appendChild(script);
    });
  }
  return stripeLibPromise;
}

// Payment page initialization
async function initPaymentPage() {
  console.log("Initializing payment page");

  if (!appState.user) {
    window.location.hash = "login";
    return;
  }

  if (!appState.cart || appState.cart.length === 0) {
    window.location.hash = "cart";
    return;
  }

  const subtotalEl = document.getElementById("subtotal");
  const deliveryFeeEl = document.getElementById("delivery-fee");
  const assemblyFeeEl = document.getElementById("assembly-fee");
  const orderTotalEl = document.getElementById("order-total");
  const totalAmountEl = document.getElementById("total-amount");
  const deliveryOptionEls = document.querySelectorAll(
    "input[name='delivery-option']"
  );
  const assemblyOptionEls = document.querySelectorAll(
    "input[name='assembly-option']"
  );
  const statusBlock = document.getElementById("payment-status");
  const payNowBtn = document.getElementById("pay-now");

  let stripe;
  let elements;
  let cardNumber;
  let cardExpiry;
  let cardCvc;
  let clientSecret;
  let paymentIntentId;
  let orderId;
  let serverTotals = null;
  // Default to no-fee choices; user must opt into paid options
  let deliveryType = "store_pickup";
  let assemblyOption = "package";

  const initialTotals = renderLocalSummary();
  updatePayButton(initialTotals.total);

  function applyTotalsFromServer(totals) {
    if (!totals) return;
    serverTotals = totals;
    // Render with current selection to zero-out free options client-side too
    const rendered = renderLocalSummary();
    if (rendered?.total > 0) updatePayButton(rendered.total);
  }

  async function refreshOrderAndIntent() {
    const orderData = await ensureOrderExists();
    orderId = orderData.order_id;
    applyTotalsFromServer(orderData.totals || null);

    const intent = await createPaymentIntent(orderId);
    clientSecret = intent.client_secret;
    paymentIntentId = intent.payment_intent_id;

    if (typeof intent.amount === "number") {
      applyTotalsFromServer({
        subtotal: serverTotals?.subtotal,
        delivery_total: serverTotals?.delivery_total,
        assembly_total: serverTotals?.assembly_total,
        total_amount: intent.amount,
      });
    }
  }

  try {
    await loadStripeLibrary();
    const config = await getStripeConfig();
    stripe = Stripe(config.publishableKey);
    elements = stripe.elements();

    await refreshOrderAndIntent();

    const style = {
      base: {
        color: "#32325d",
        fontFamily: '"Helvetica Neue", Helvetica, sans-serif',
        fontSmoothing: "antialiased",
        fontSize: "16px",
        "::placeholder": { color: "#aab7c4" },
      },
      invalid: { color: "#fa755a", iconColor: "#fa755a" },
    };

    cardNumber = elements.create("cardNumber", { style });
    cardExpiry = elements.create("cardExpiry", { style });
    cardCvc = elements.create("cardCvc", { style });

    cardNumber.mount("#card-number-element");
    cardExpiry.mount("#card-expiry-element");
    cardCvc.mount("#card-cvc-element");

    [
      { el: cardNumber, target: "card-number-error" },
      { el: cardExpiry, target: "card-expiry-error" },
      { el: cardCvc, target: "card-cvc-error" },
    ].forEach(({ el, target }) => {
      el.on("change", (event) => {
        const displayError = document.getElementById(target);
        if (!displayError) return;
        displayError.textContent = event.error ? event.error.message : "";
      });
    });

    if (payNowBtn) {
      payNowBtn.onclick = () => processPayment();
    }

    // Listen for delivery/pickup changes
    deliveryOptionEls.forEach((el) => {
      el.addEventListener("change", async () => {
        deliveryType = el.value;
        await refreshOrderAndIntent();
      });
    });

    // Listen for assembly option changes
    assemblyOptionEls.forEach((el) => {
      el.addEventListener("change", async () => {
        assemblyOption = el.value;
        await refreshOrderAndIntent();
      });
    });

    const backToCartBtn = document.getElementById("back-to-cart");
    if (backToCartBtn) {
      backToCartBtn.onclick = () => (window.location.hash = "cart");
    }
  } catch (err) {
    console.error("Payment init failed", err);
    showPayError(err.message || "Payment initialization failed");
  }

  function renderLocalSummary() {
    let subtotal = 0;
    appState.cart.forEach((item) => {
      const price =
        parseFloat(String(item.price).replace(/[^0-9.\-]+/g, "")) || 0;
      subtotal += price * (item.quantity || 1);
    });

    if (serverTotals) {
      const baseSubtotal = serverTotals.subtotal ?? subtotal;
      const baseDelivery = serverTotals.delivery_total ?? 0;
      const baseAssembly = serverTotals.assembly_total ?? 0;

      const delivery = deliveryType === "home" ? baseDelivery : 0;
      const assembly = assemblyOption === "worker_assembly" ? baseAssembly : 0;
      const total = baseSubtotal + delivery + assembly;

      if (subtotalEl) subtotalEl.textContent = `$${baseSubtotal.toFixed(2)}`;
      if (deliveryFeeEl) deliveryFeeEl.textContent = `$${delivery.toFixed(2)}`;
      if (assemblyFeeEl) assemblyFeeEl.textContent = `$${assembly.toFixed(2)}`;
      if (orderTotalEl) orderTotalEl.textContent = `$${total.toFixed(2)}`;
      if (totalAmountEl) totalAmountEl.textContent = total.toFixed(2);
      return {
        subtotal: baseSubtotal,
        delivery,
        assembly,
        total,
      };
    }

    const delivery = deliveryType === "home" ? 0 : 0;
    const assembly = assemblyOption === "worker_assembly" ? 0 : 0;
    const total = subtotal + delivery + assembly;

    if (subtotalEl) subtotalEl.textContent = `$${subtotal.toFixed(2)}`;
    if (deliveryFeeEl) deliveryFeeEl.textContent = `$${delivery.toFixed(2)}`;
    if (assemblyFeeEl) assemblyFeeEl.textContent = `$${assembly.toFixed(2)}`;
    if (orderTotalEl) orderTotalEl.textContent = `$${total.toFixed(2)}`;
    if (totalAmountEl) totalAmountEl.textContent = total.toFixed(2);
    return { subtotal, delivery, assembly, total };
  }

  function updatePayButton(total) {
    if (payNowBtn) {
      payNowBtn.innerHTML = `<i class="fas fa-lock"></i> Pay $${total.toFixed(
        2
      )}`;
    }
  }

  function ensureOrderExists() {
    return new Promise((resolve, reject) => {
      const user = appState.user;
      const addressParts = [user.address, user.city, user.postal_code].filter(
        Boolean
      );
      if (addressParts.length === 0) {
        return reject(
          new Error("Please add your address in profile before checkout.")
        );
      }

      const payload = {
        shipping_address: addressParts.join(", "),
        delivery_type: deliveryType,
        assembly_option: assemblyOption,
        items: appState.cart.map((item) => ({
          product_id: parseInt(item.id, 10) || item.id,
          quantity: item.quantity || 1,
        })),
      };

      RestClient.post(
        "orders/from-cart",
        payload,
        (res) => {
          if (res?.success && res?.order_id) {
            resolve({
              order_id: res.order_id,
              totals: res.totals || null,
            });
          } else {
            reject(
              new Error(res?.error || res?.message || "Could not create order")
            );
          }
        },
        (err) => {
          reject(
            new Error(
              err?.responseJSON?.error ||
                err?.responseText ||
                "Could not create order"
            )
          );
        }
      );
    });
  }

  function getStripeConfig() {
    return new Promise((resolve, reject) => {
      RestClient.get(
        "stripe/config",
        (res) => resolve(res),
        (err) =>
          reject(
            new Error(
              err?.responseJSON?.error ||
                err?.responseText ||
                "Failed to load Stripe config"
            )
          )
      );
    });
  }

  function createPaymentIntent(orderId) {
    return new Promise((resolve, reject) => {
      RestClient.post(
        "stripe/create-payment-intent",
        { order_id: orderId },
        (res) => {
          if (res?.success && res?.data?.client_secret) {
            resolve(res.data);
          } else {
            reject(
              new Error(
                res?.error || res?.message || "Failed to create payment intent"
              )
            );
          }
        },
        (err) => {
          reject(
            new Error(
              err?.responseJSON?.error ||
                err?.responseText ||
                "Failed to create payment intent"
            )
          );
        }
      );
    });
  }

  async function processPayment() {
    if (!stripe || !clientSecret) return;
    if (payNowBtn) {
      payNowBtn.disabled = true;
      payNowBtn.textContent = "Processing...";
    }
    if (statusBlock) statusBlock.style.display = "block";

    try {
      const cardholderName =
        document.getElementById("cardholder-name")?.value ||
        appState.user.full_name ||
        "Customer";
      const result = await stripe.confirmCardPayment(clientSecret, {
        payment_method: {
          card: cardNumber,
          billing_details: {
            name: cardholderName,
            email: appState.user.email,
          },
        },
      });

      if (result.error) {
        throw new Error(result.error.message || "Payment failed");
      }

      const paymentIntent = result.paymentIntent;
      paymentIntentId = paymentIntent.id;

      await new Promise((resolve, reject) => {
        RestClient.post(
          "stripe/confirm-payment",
          {
            payment_intent_id: paymentIntent.id,
            payment_method_id: paymentIntent.payment_method,
          },
          (res) => {
            if (res?.success) return resolve();
            reject(new Error(res?.error || "Could not finalize payment"));
          },
          (err) => {
            reject(
              new Error(
                err?.responseJSON?.error ||
                  err?.responseText ||
                  "Could not finalize payment"
              )
            );
          }
        );
      });

      appState.cart = [];
      localStorage.removeItem("zimCart");
      updateCartBadge();
      showPaySuccess(paymentIntent.status, orderId);
    } catch (err) {
      console.error("Payment error", err);
      showPayError(err.message || "Payment failed");
    } finally {
      if (statusBlock) statusBlock.style.display = "none";
      if (payNowBtn) {
        payNowBtn.disabled = false;
        updatePayButton(renderLocalSummary().total);
      }
    }
  }

  function showPaySuccess(status, orderIdValue) {
    const successModal = document.getElementById("success-modal");
    if (successModal) successModal.style.display = "flex";
    showToast(
      "Payment received. We'll process your order soon.",
      "success",
      4000
    );

    const orderNumEl = document.getElementById("order-number");
    const payTotalEl = document.getElementById("payment-total");
    const payDateEl = document.getElementById("payment-date");
    const payMethodEl = document.getElementById("payment-method");

    if (orderNumEl && orderIdValue) orderNumEl.textContent = `#${orderIdValue}`;
    if (payTotalEl && orderTotalEl)
      payTotalEl.textContent = orderTotalEl.textContent;
    if (payDateEl) payDateEl.textContent = new Date().toLocaleString();
    if (payMethodEl) payMethodEl.textContent = `Card (${status})`;

    const continueBtn = document.getElementById("continue-shopping");
    if (continueBtn) {
      continueBtn.onclick = () => {
        successModal.style.display = "none";
        window.location.hash = "home";
      };
    }

    const viewOrderBtn = document.getElementById("view-order-details");
    if (viewOrderBtn) {
      viewOrderBtn.onclick = () => {
        successModal.style.display = "none";
        window.location.hash = "orders";
      };
    }
  }

  function showPayError(message) {
    const errorModal = document.getElementById("error-modal");
    const errorMessageEl = document.getElementById("error-message");
    if (errorMessageEl)
      errorMessageEl.textContent = message || "Payment failed";
    if (errorModal) errorModal.style.display = "flex";
    showToast(message || "Payment failed", "error", 4000);

    const tryAgainBtn = document.getElementById("try-again");
    if (tryAgainBtn) {
      tryAgainBtn.onclick = () => {
        errorModal.style.display = "none";
      };
    }

    const closeErrorBtn = document.getElementById("close-error");
    if (closeErrorBtn) {
      closeErrorBtn.onclick = () => {
        errorModal.style.display = "none";
      };
    }

    if (window.toastr) toastr.error(message);
  }
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

  if (pagePath.startsWith("admin-order-detail/")) {
    const parts = pagePath.split("/");
    page = "admin-order-detail";
    params.id = parts[1] || "";
  }

  return { page, params };
}

// Favorites page initialization
function initFavoritesPage() {
  console.log("Initializing favorites page");

  if (!appState.user) {
    window.location.hash = "login";
    return;
  }

  // Load favorites items
  loadFavoritesItems();
}

// Load favorites items
function loadFavoritesItems() {
  const favoritesContainer = document.getElementById("favoriteItems");
  if (!favoritesContainer) return;

  if (appState.favorites.length === 0) {
    favoritesContainer.innerHTML = `
      <div style="text-align:center; padding:3rem; color:#5d4037;">
        <i class="fas fa-heart" style="font-size:3rem; color:#e8dfc8; margin-bottom:1rem;"></i>
        <h3 style="color:#800020; margin-bottom:0.5rem;">No favorites yet</h3>
        <p style="margin-bottom:1.5rem;">Add some products to your favorites list!</p>
        <a href="#products" style="display:inline-block; padding:0.8rem 1.5rem; background:#800020; color:white; text-decoration:none; border-radius:4px;">
          Browse Products
        </a>
      </div>
    `;
    return;
  }

  // Display favorites as a grid
  favoritesContainer.innerHTML = `
    <div class="products-grid">
      ${appState.favorites
        .map(
          (item) => `
        <div class="product-card">
          <img src="${item.image}" alt="${item.name}" class="product-image" />
          <div class="product-content">
            <h3 class="product-title">${item.name}</h3>
            <div class="product-price">${item.price}</div>
            <div class="product-actions">
              <button onclick="removeFromFavorites('${item.id}')" style="width:100%; padding:0.6rem; background-color:#800020; color:white; border:none; border-radius:4px; cursor:pointer;">
                <i class="fas fa-trash"></i> Remove
              </button>
            </div>
          </div>
        </div>
      `
        )
        .join("")}
    </div>
  `;

  // Reinitialize favorite buttons
  initFavoriteButtons();
}

// Add to favorites
function addToFavorites(productId, productName, productPrice, productImage) {
  if (!productId) return;

  // Check if product already exists in favorites
  const existingIndex = appState.favorites.findIndex(
    (item) => item.id === productId
  );

  // Only add if it doesn't already exist (prevent duplicates)
  if (existingIndex === -1) {
    appState.favorites.push({
      id: productId,
      name: productName,
      price: productPrice,
      image: productImage,
      addedAt: new Date().toISOString(),
    });

    // Deduplicate before saving (extra safety)
    appState.favorites = deduplicateFavorites(appState.favorites);

    // Update storage
    localStorage.setItem("zimFavorites", JSON.stringify(appState.favorites));

    // Update all favorite buttons for this product on the current page
    updateFavoriteButtonsForProduct(productId, true);
  }
}

// Update favorite buttons for a specific product
function updateFavoriteButtonsForProduct(productId, isFavorited) {
  document.querySelectorAll(".favorite-btn").forEach((btn) => {
    const productCard = btn.closest(".product-card");
    const link = productCard?.querySelector(".btn-see-more");
    const btnProductId = link?.href?.match(/single-product\/(\d+)/)?.[1];

    if (btnProductId == productId) {
      const icon = btn.querySelector("i");
      if (isFavorited) {
        icon.className = "fas fa-heart";
        btn.classList.add("active");
      } else {
        icon.className = "far fa-heart";
        btn.classList.remove("active");
      }
    }
  });
}

// Remove from favorites
function removeFromFavorites(productId) {
  // Remove from favorites array
  appState.favorites = appState.favorites.filter(
    (item) => item.id !== productId
  );

  // Update storage
  localStorage.setItem("zimFavorites", JSON.stringify(appState.favorites));

  // Reload favorites page if currently viewing it
  if (window.location.hash.includes("favorite")) {
    loadFavoritesItems();
  }

  // Update favorite button state for this product
  updateFavoriteButtonsForProduct(productId, false);
}

// Main router function
function router() {
  // Store previous hash for login redirect
  const currentHash = window.location.hash;
  if (
    currentHash &&
    !currentHash.includes("login") &&
    !currentHash.includes("register") &&
    !currentHash.includes("admin-")
  ) {
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
window.goToRegister = goToRegister;
window.logout = logout;
