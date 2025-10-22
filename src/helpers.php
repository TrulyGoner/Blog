<?php
require_once 'config.php';
function sanitize_input($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    return $data;
}

function generate_csrf_token() {
    if (!isset($_SESSION[CSRF_TOKEN_NAME])) {
        $_SESSION[CSRF_TOKEN_NAME] = bin2hex(random_bytes(32));
    }
    return $_SESSION[CSRF_TOKEN_NAME];
}

function verify_csrf_token($token) {
    return isset($_SESSION[CSRF_TOKEN_NAME]) && hash_equals($_SESSION[CSRF_TOKEN_NAME], $token);
}

function is_logged_in() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

function get_current_user_data() {
    if (!is_logged_in()) {
        return null;
    }
    
    $db = getDB();
    $stmt = $db->prepare("SELECT id, login, created_at FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    return $stmt->fetch();
}

function require_login() {
    if (!is_logged_in()) {
        header('Location: login.php');
        exit;
    }
}

function redirect($url) {
    header("Location: $url");
    exit;
}

function format_date($date) {
    return date('d.m.Y H:i', strtotime($date));
}

function format_date_relative($date) {
    $time = time() - strtotime($date);
    
    if ($time < 60) return 'только что';
    if ($time < 3600) return floor($time/60) . ' мин. назад';
    if ($time < 86400) return floor($time/3600) . ' ч. назад';
    if ($time < 2592000) return floor($time/86400) . ' дн. назад';
    
    return format_date($date);
}

function truncate_text($text, $length = 200) {
    if (strlen($text) <= $length) {
        return $text;
    }
    return substr($text, 0, $length) . '...';
}

function get_pagination($current_page, $total_pages, $base_url) {
    $pagination = [];
    
    if ($current_page > 1) {
        $pagination[] = [
            'page' => $current_page - 1,
            'text' => '← Назад',
            'url' => $base_url . '?page=' . ($current_page - 1),
            'active' => false
        ];
    }
    
    $start = max(1, $current_page - 2);
    $end = min($total_pages, $current_page + 2);
    
    for ($i = $start; $i <= $end; $i++) {
        $pagination[] = [
            'page' => $i,
            'text' => $i,
            'url' => $base_url . '?page=' . $i,
            'active' => $i == $current_page
        ];
    }
    
    if ($current_page < $total_pages) {
        $pagination[] = [
            'page' => $current_page + 1,
            'text' => 'Вперед →',
            'url' => $base_url . '?page=' . ($current_page + 1),
            'active' => false
        ];
    }
    
    return $pagination;
}

function flash_message($message, $type = 'info') {
    $_SESSION['flash_message'] = $message;
    $_SESSION['flash_type'] = $type;
}

function get_flash_message() {
    if (isset($_SESSION['flash_message'])) {
        $message = $_SESSION['flash_message'];
        $type = $_SESSION['flash_type'] ?? 'info';
        unset($_SESSION['flash_message'], $_SESSION['flash_type']);
        return ['message' => $message, 'type' => $type];
    }
    return null;
}

function validate_post_data($data) {
    $errors = [];
    
    if (empty($data['title'])) {
        $errors[] = 'Заголовок обязателен';
    } elseif (strlen($data['title']) > 255) {
        $errors[] = 'Заголовок слишком длинный';
    }
    
    if (empty($data['content'])) {
        $errors[] = 'Содержание обязательно';
    }
    
    return $errors;
}

function validate_comment_data($data) {
    $errors = [];
    
    if (empty($data['text'])) {
        $errors[] = 'Текст комментария обязателен';
    } elseif (strlen($data['text']) > 1000) {
        $errors[] = 'Комментарий слишком длинный';
    }
    
    return $errors;
}

function validate_user_data($data) {
    $errors = [];
    
    if (empty($data['login'])) {
        $errors[] = 'Логин обязателен';
    } elseif (strlen($data['login']) < 3 || strlen($data['login']) > 50) {
        $errors[] = 'Логин должен быть от 3 до 50 символов';
    } elseif (!preg_match('/^[a-zA-Z0-9_]+$/', $data['login'])) {
        $errors[] = 'Логин может содержать только буквы, цифры и подчеркивания';
    }
    
    if (empty($data['password'])) {
        $errors[] = 'Пароль обязателен';
    } elseif (strlen($data['password']) < 6) {
        $errors[] = 'Пароль должен быть не менее 6 символов';
    }
    
    if (isset($data['password_confirm']) && $data['password'] !== $data['password_confirm']) {
        $errors[] = 'Пароли не совпадают';
    }
    
    return $errors;
}