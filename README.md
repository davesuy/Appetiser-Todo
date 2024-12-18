# Task Management Application

This is a Task Management application built with Laravel and Vue.js. It allows users to manage tasks with features like creating, updating, deleting, archiving, and restoring tasks. The application also supports user authentication and tagging of tasks.

## Prerequisites

- PHP 8.0 or higher
- Composer
- Node.js and npm
- MySQL or any other supported database

## Installation

1. **Clone the repository:**

    ```bash
    git clone https://github.com/your-username/task-management-app.git
    cd task-management-app
    ```

2. **Install PHP dependencies:**

    ```bash
    composer install
    ```

3. **Install JavaScript dependencies:**

    ```bash
    npm install
    ```

4. **Copy the `.env.example` file to `.env` and configure your environment variables:**

    ```bash
    cp .env.example .env
    ```

5. **Generate the application key:**

    ```bash
    php artisan key:generate
    ```

6. **Run the database migrations:**

    ```bash
    php artisan migrate
    ```

7. **Run the database seeders (optional):**

    ```bash
    php artisan db:seed
    ```

8. **Build the frontend assets:**

    ```bash
    npm run dev
    ```

## Running the Application

1. **Start the development server:**

    ```bash
    php artisan serve
    ```

2. **Access the application in your browser:**

    ```
    http://127.0.0.1:8000
    ```

## API Documentation

This application uses Swagger for API documentation. To view the API documentation:

1. **Generate the Swagger documentation:**

    ```bash
    php artisan l5-swagger:generate
    ```

2. **Access the API documentation in your browser:**

    ```
    http://127.0.0.1:8000/api/documentation
    ```

## Clearing Cache

If you encounter any issues, try clearing the cache:

```bash
php artisan config:cache
php artisan route:cache
php artisan cache:clear
