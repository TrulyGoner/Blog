<?php
define('DB_HOST', 'localhost');
define('DB_NAME', 'blog_db');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

define('CSRF_TOKEN_NAME', 'csrf_token');
define('SESSION_TIMEOUT', 3600);

define('SITE_NAME', 'Мой Блог');
define('SITE_URL', 'http://localhost/blog');

define('POSTS_PER_PAGE', 10);
define('COMMENTS_PER_PAGE', 20);

define('ROOT_PATH', dirname(__DIR__));
define('PUBLIC_PATH', ROOT_PATH . '/public');
define('SRC_PATH', ROOT_PATH . '/src');

spl_autoload_register(function ($class) {
    $file = SRC_PATH . '/' . strtolower($class) . '.php';
    if (file_exists($file)) {
        require_once $file;
    }
});

ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_secure', 0);
session_start();