# Laravel Project

This is a Laravel-based web application built with modern tools and best practices.

---

## Tech Stack

- **Backend:** Laravel 11  
- **PHP Version:** 8.2  
- **Database:** MySQL 9  
- **Frontend Build Tool:** Node.js 22  
- **CSS Framework:** bootstrap 5  

---

## Prerequisites

Make sure you have the following installed on your system:

- PHP >= 8.2  
- Composer  
- Node.js >= 22  
- npm  
- MySQL >= 9  

---

## Installation & Setup

Follow the steps below to set up the project locally:

### 1. Clone the repository
```bash
git clone <repository-url>
cd <project-folder>

composer install
npm install
cp .env.example .env

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=your_database_name
DB_USERNAME=your_database_user
DB_PASSWORD=your_database_password

# Run thise comand
php artisan key:generate
php artisan migrate
php artisan db:seed
npm run dev
php artisan serve


The application will be available at:
http://127.0.0.1:8000
# electricity
