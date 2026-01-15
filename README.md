# E-Commerce Platform

A full-stack e-commerce application built with PHP and vanilla JavaScript, featuring product browsing, shopping cart, favorites, and admin dashboard.

## Tech Stack

- **Backend**: PHP 8+ with Flight microframework
- **Frontend**: Vanilla JavaScript with SPA router
- **Database**: MySQL
- **Payment**: Stripe integration
- **Authentication**: JWT (JSON Web Tokens)
- **Deployment**: Docker containers

## Project Structure

```
backend/
├── api/              # API upload handlers
├── rest/
│   ├── dao/         # Database access objects
│   ├── routes/      # API route definitions
│   └── services/    # Business logic
├── middleware/      # Authentication middleware
├── tests/           # Unit and integration tests
└── config.php       # Configuration

frontend/
├── pages/           # HTML pages (SPA)
├── JS/              # JavaScript (main.js, router.js)
├── css/             # Stylesheets
├── services/        # API client services
├── tests/           # Jest tests
└── utils/           # Helper functions
```

## Features

- **User Management**: Registration, login, profile management
- **Product Catalog**: Browse products by category, search functionality
- **Shopping Cart**: Add/remove items, persistent storage
- **Favorites**: Save products for later
- **Orders**: Place orders and track history
- **Admin Dashboard**: Manage products, categories, users, and orders
- **Payment Processing**: Stripe integration for secure payments
- **Image Upload**: Product image uploads with validation

## Getting Started

### Prerequisites
- PHP 8.0+
- MySQL 5.7+
- Node.js 14+ (for frontend testing)

### Backend Setup
```bash
cd backend
composer install
php -S localhost:8000
```

### Frontend Setup
```bash
cd frontend
npm install
npm test
```

### Docker Setup
```bash
docker build -t ecommerce-backend ./backend
docker build -t ecommerce-frontend ./frontend
docker-compose up
```

## Configuration

1. Update `backend/config.php` with your JWT secret and database credentials
2. Configure Stripe keys in `backend/stripe.php`
3. Set upload directories with proper permissions

## Testing

### Backend Tests
```bash
cd backend
./vendor/bin/phpunit
```

### Frontend Tests
```bash
cd frontend
npm test
```

## API Endpoints

- `POST /api/auth/register` - User registration
- `POST /api/auth/login` - User login
- `GET /api/products` - List products
- `POST /api/cart/add` - Add to cart
- `POST /api/orders` - Create order
- `GET /api/uploads/products/{filename}` - Serve product images

