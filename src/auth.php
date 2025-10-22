<?php
require_once 'config.php';
require_once 'db.php';
require_once 'helpers.php';

class Auth {
    private $db;
    
    public function __construct() {
        $this->db = getDB();
    }
    
    public function register($login, $password) {
        $stmt = $this->db->prepare("SELECT id FROM users WHERE login = ?");
        $stmt->execute([$login]);
        
        if ($stmt->fetch()) {
            return ['success' => false, 'message' => 'Пользователь с таким логином уже существует'];
        }
        
        $password_hash = password_hash($password, PASSWORD_DEFAULT);
        
        try {
            $stmt = $this->db->prepare("INSERT INTO users (login, password_hash) VALUES (?, ?)");
            $stmt->execute([$login, $password_hash]);
            
            return ['success' => true, 'message' => 'Регистрация успешна'];
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Ошибка при регистрации'];
        }
    }
    
    public function login($login, $password) {
        $stmt = $this->db->prepare("SELECT id, password_hash FROM users WHERE login = ?");
        $stmt->execute([$login]);
        $user = $stmt->fetch();
        
        if ($user && password_verify($password, $user['password_hash'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['login_time'] = time();
            return ['success' => true, 'message' => 'Вход выполнен успешно'];
        }
        
        return ['success' => false, 'message' => 'Неверный логин или пароль'];
    }
    
    public function logout() {
        session_destroy();
        return ['success' => true, 'message' => 'Выход выполнен успешно'];
    }
    
    public function check_session() {
        if (!is_logged_in()) {
            return false;
        }
        
        if (isset($_SESSION['login_time']) && (time() - $_SESSION['login_time']) > SESSION_TIMEOUT) {
            $this->logout();
            return false;
        }
        
        $_SESSION['login_time'] = time();
        return true;
    }
    
    public function get_user_stats($user_id) {
        $stats = [];
        
        $stmt = $this->db->prepare("SELECT COUNT(*) as post_count FROM posts WHERE user_id = ?");
        $stmt->execute([$user_id]);
        $stats['post_count'] = $stmt->fetch()['post_count'];
        
        $stmt = $this->db->prepare("SELECT SUM(views) as total_views FROM posts WHERE user_id = ?");
        $stmt->execute([$user_id]);
        $stats['total_views'] = $stmt->fetch()['total_views'] ?? 0;
        
        $stmt = $this->db->prepare("SELECT SUM(likes) as total_likes FROM posts WHERE user_id = ?");
        $stmt->execute([$user_id]);
        $stats['total_likes'] = $stmt->fetch()['total_likes'] ?? 0;
        
        $stmt = $this->db->prepare("SELECT SUM(dislikes) as total_dislikes FROM posts WHERE user_id = ?");
        $stmt->execute([$user_id]);
        $stats['total_dislikes'] = $stmt->fetch()['total_dislikes'] ?? 0;
        
        return $stats;
    }
}