# DesiDukaan - Data-Driven E-Commerce & Analytics Platform

A responsive multi-vendor web platform built with **PHP, MySQL, HTML5, CSS, JavaScript, and Python Data Science Engines** (Item-Based Collaborative Filtering, NLP Sentiment Analysis, RFM Customer Segmentation, and Demand Forecasting).

## 🤖 Data Science & Machine Learning Features

- **Item-Based Collaborative Filtering**: Personalized product recommendation engine using Cosine Similarity on user interaction vectors (`views`, `cart`, `wishlist`, `purchases`).
- **NLP Review Sentiment Analysis**: Rule-based & TF-IDF term frequency polarity classifier scoring customer review text (`-1.0` to `+1.0`).
- **Customer RFM Segmentation**: Recency-Frequency-Monetary (RFM) clustering model categorizing buyers into `VIP`, `Loyal`, `Potential Loyalist`, and `At Risk`.
- **Sales & Demand Trend Forecasting**: Time-series moving average model projecting upcoming vendor revenue and product demand.
- **Automated CSV Dataset Exporters**: Export e-commerce interaction tables into clean CSV datasets (`products_dataset.csv`, `reviews_sentiment_dataset.csv`, `customer_rfm_segments.csv`) for EDA in Jupyter Notebooks.

## Features

### 🎨 Design
- **Brand Colors**: Crimson (#DC143C) primary color with white background
- **Responsive Design**: Works perfectly on mobile, tablet, and desktop
- **Animated Splash Screen**: Beautiful logo animation with Sindhi slogan
- **Modern UI**: Clean, professional interface with smooth transitions

### 🌐 Multi-language Support
- **English/Sindhi Toggle**: Manual language switching without page reload
- **Complete Translation**: All UI elements translated to both languages
- **Sindhi Typography**: Proper Sindhi text rendering and display

### 👥 User Management
- **Customer Registration**: Simple registration for buyers
- **Seller Registration**: Enhanced registration with CNIC and mobile validation
- **Role-based Access**: Secure authentication with proper role restrictions
- **Session Management**: Secure session handling with database storage

### 🏪 Shop Management
- **Shop Creation**: Comprehensive shop setup with document uploads
- **Admin Approval**: Complete admin review and approval workflow
- **File Uploads**: CNIC picture and shop logo upload functionality
- **Status Tracking**: Real-time shop approval status updates

### 👨‍💼 Admin Panel
- **Shop Requests**: Review and approve/reject shop creation requests
- **Document Verification**: View uploaded CNIC and logo images
- **Admin Logging**: Track all admin actions and decisions
- **Statistics Dashboard**: Overview of platform metrics

## Installation

### Prerequisites
- XAMPP (or similar PHP/MySQL environment)
- PHP 7.4 or higher
- MySQL 5.7 or higher

### Setup Instructions

1. **Clone/Download** the project to your XAMPP htdocs directory:
   ```
   c:\xampp\htdocs\DesiDukaan\
   ```

2. **Start XAMPP** services:
   - Apache
   - MySQL

3. **Setup Database** (Choose one method):

   **Method A: Quick Reset (Recommended)**
   - Open browser and go to: `http://localhost/DesiDukaan/reset_database.php`
   - This will create a clean database with all tables and admin user

   **Method B: Manual phpMyAdmin**
   - Open phpMyAdmin (http://localhost/phpmyadmin)
   - Import the database schema from `database/setup_clean.sql`

   **Method C: Setup Instructions Page**
   - Open `setup_instructions.html` in your browser for detailed steps

4. **Access Application**:
   - Open browser and go to: `http://localhost/DesiDukaan`

## Default Admin Account

- **Email**: admin@desidukaan.com
- **Password**: password

**⚠️ Important**: Change the admin password in production!

## File Structure

```
DesiDukaan/
├── assets/
│   ├── css/
│   │   └── style.css          # Main stylesheet
│   └── js/
│       └── main.js            # JavaScript functionality
├── auth/
│   ├── login.php              # Login page
│   ├── logout.php             # Logout handler
│   ├── register-customer.php  # Customer registration
│   ├── register-seller.php    # Seller registration
│   └── unauthorized.php       # Unauthorized access page
├── config/
│   ├── config.php             # Main configuration
│   ├── database.php           # Database connection
│   ├── languages.php          # Translation strings
│   └── set_language.php       # Language switching endpoint
├── customer/
│   ├── dashboard.php          # Customer dashboard
│   └── profile.php            # Customer profile (placeholder)
├── seller/
│   ├── create-shop.php        # Shop creation form
│   ├── dashboard.php          # Seller dashboard
│   ├── shop-pending.php       # Shop approval status
│   ├── products.php           # Products management (placeholder)
│   └── orders.php             # Orders management (placeholder)
├── admin/
│   ├── dashboard.php          # Admin dashboard
│   └── shop-details.php       # Shop review and approval
├── database/
│   └── schema.sql             # Database schema
├── uploads/                   # File uploads directory
│   ├── cnic/                  # CNIC pictures
│   ├── logos/                 # Shop logos
│   └── products/              # Product images (future)
├── index.php                  # Splash screen
├── user-type-selection.php    # User role selection
└── README.md                  # This file
```

## User Flow

### 1. Splash Screen
- Animated logo with Sindhi slogan
- Auto-transitions to user type selection after 3 seconds

### 2. User Type Selection
- Choose between Customer or Seller registration
- Language toggle available

### 3. Customer Flow
- Simple registration (username, email, password)
- Login and access customer dashboard
- Browse products (placeholder grid)

### 4. Seller Flow
- Enhanced registration (username, CNIC, mobile, email, password)
- Create shop with detailed information and document uploads
- Wait for admin approval
- Access seller dashboard after approval

### 5. Admin Flow
- Login with admin credentials
- Review pending shop requests
- View uploaded documents
- Approve or reject with reasons
- Track all actions in admin logs

## Security Features

- **Password Hashing**: Secure password storage using PHP's password_hash()
- **Input Sanitization**: All user inputs sanitized and validated
- **SQL Injection Prevention**: Prepared statements for all database queries
- **File Upload Security**: Restricted file types and secure upload handling
- **Session Security**: Secure session management with database storage
- **Role-based Access**: Proper authentication and authorization checks

## Responsive Design

- **Mobile First**: Optimized for mobile devices
- **Tablet Support**: Perfect display on tablets
- **Desktop Enhanced**: Full desktop experience
- **Touch Friendly**: Large buttons and touch targets
- **Flexible Grid**: CSS Grid and Flexbox for layouts

## Language Support

The application supports both English and Sindhi languages:

- **English**: Default language
- **Sindhi**: Complete translation with proper Unicode support
- **Toggle**: Instant language switching without page reload
- **Persistent**: Language preference saved in session

## Future Enhancements

- Product catalog and inventory management
- Shopping cart and checkout process
- Order processing and tracking
- Payment gateway integration
- Customer reviews and ratings
- Advanced search and filtering
- Email notifications
- Mobile app development

## Support

For technical support or questions about DesiDukaan, please contact the development team.

## License

This project is developed for DesiDukaan startup. All rights reserved.
