# Laravel SSO

This repository contains two Laravel applications for Single Sign-On (SSO) implementation:


- **server-application/**: The SSO server handling authentication and token management.
- **client-application/**: The client app that integrates with the SSO server.

## Structure

```
laravel-sso/
├── server-application/
├── client-application/
```

## Getting Started

### Installation

1. Clone the repository:
   ```sh
   git clone https://github.com/mohidul31/laravel-sso.git
   cd laravel-sso
   ```
2. Install dependencies for both applications:
   ```sh
   cd server-application
   composer install
   npm install
   npm run build
   cp .env.example .env
   php artisan key:generate
   php artisan migrate

   cd ..
   cd client-application
   composer install
   npm install
   npm run build
   cp .env.example .env
   php artisan key:generate
   php artisan migrate
   ```
3. Create 2 DB as per the name on .env file then run the migration in both application.

4. Create a client form SSO server application
  ```sh
  php artisan passport:client
  ```
5. When creating the client in the SSO server application, set the redirect URL(Where should we redirect the request after authorization?) to `http://127.0.0.1:4000/callback`. After running the command, copy the generated Client ID and Client Secret, then paste them into the corresponding fields in your `client-application/.env` file:
  ```env
  OAUTH_CLIENT_ID=your-client-id-here
  OAUTH_CLIENT_SECRET=your-client-secret-here
  ```
## Usage

- Start the development server for SSO Server application:
  ```sh
  php artisan serve --port=9000
  ```
- Start the development server for SSO Client application:
  ```sh
  php artisan serve --port=4000
  ```
- Access the client and server apps via their respective URLs.
  ```sh
  http://127.0.0.1:9000/
  http://127.0.0.1:4000/
  ```

