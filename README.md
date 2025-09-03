# Laravel SSO Monorepo

This repository contains two Laravel applications for Single Sign-On (SSO) implementation:

- **client-application/**: The client app that integrates with the SSO server.
- **server-application/**: The SSO server handling authentication and token management.

## Structure

```
laravel-sso/
├── client-application/
├── server-application/
```

## Getting Started

### Prerequisites
- PHP >= 8.0
- Composer
- Node.js & npm
- Laravel CLI

### Installation

1. Clone the repository:
   ```sh
   git clone https://github.com/mohidul31/laravel-sso.git
   cd laravel-sso
   ```
2. Install dependencies for both applications:
   ```sh
   cd client-application
   composer install
   npm install
   cd ../server-application
   composer install
   npm install
   ```
3. Copy `.env.example` to `.env` and configure environment variables for both apps.
4. Generate application keys:
   ```sh
   php artisan key:generate
   ```
5. Run migrations:
   ```sh
   php artisan migrate
   ```

## Usage

- Start the development server for each application:
  ```sh
  php artisan serve
  ```
- Access the client and server apps via their respective URLs.

## License

This project is licensed under the MIT License.
