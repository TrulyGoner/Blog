<?php
require_once 'config.php';
require_once 'db.php';
require_once 'helpers.php';

class Comment {
    private $db;
    
    public function __construct() {
        $this->db = getDB();
    }
    
    public function create($post_id, $user_id, $text) {
        try {
            $stmt = $this->db->prepare("INSERT INTO comments (post_id, user_id, text) VALUES (?, ?, ?)");
            $stmt->execute([$post_id, $user_id, $text]);
            return ['success' => true, 'id' => $this->db->lastInsertId()];
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Ошибка при создании комментария'];
        }
    }
    
    public function delete($comment_id, $user_id) {
        $stmt = $this->db->prepare("SELECT id FROM comments WHERE id = ? AND user_id = ?");
        $stmt->execute([$comment_id, $user_id]);
        
        if (!$stmt->fetch()) {
            return ['success' => false, 'message' => 'Комментарий не найден или у вас нет прав на его удаление'];
        }
        
        try {
            $stmt = $this->db->prepare("DELETE FROM comments WHERE id = ? AND user_id = ?");
            $stmt->execute([$comment_id, $user_id]);
            return ['success' => true];
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Ошибка при удалении комментария'];
        }
    }
    
    public function get_by_post($post_id, $page = 1, $limit = COMMENTS_PER_PAGE) {
        $offset = ($page - 1) * $limit;
        
        $stmt = $this->db->prepare("
            SELECT c.*, u.login as author_login 
            FROM comments c 
            JOIN users u ON c.user_id = u.id 
            WHERE c.post_id = ?
            ORDER BY c.created_at ASC 
            LIMIT ? OFFSET ?
        ");
        $stmt->execute([$post_id, $limit, $offset]);
        $comments = $stmt->fetchAll();
        
        $stmt = $this->db->prepare("SELECT COUNT(*) as total FROM comments WHERE post_id = ?");
        $stmt->execute([$post_id]);
        $total = $stmt->fetch()['total'];
        
        return [
            'comments' => $comments,
            'total' => $total,
            'pages' => ceil($total / $limit),
            'current_page' => $page
        ];
    }
    
    public function get_by_user($user_id, $page = 1, $limit = COMMENTS_PER_PAGE) {
        $offset = ($page - 1) * $limit;
        
        $stmt = $this->db->prepare("
            SELECT c.*, p.title as post_title, p.id as post_id
            FROM comments c 
            JOIN posts p ON c.post_id = p.id 
            WHERE c.user_id = ?
            ORDER BY c.created_at DESC 
            LIMIT ? OFFSET ?
        ");
        $stmt->execute([$user_id, $limit, $offset]);
        $comments = $stmt->fetchAll();
        
        $stmt = $this->db->prepare("SELECT COUNT(*) as total FROM comments WHERE user_id = ?");
        $stmt->execute([$user_id]);
        $total = $stmt->fetch()['total'];
        
        return [
            'comments' => $comments,
            'total' => $total,
            'pages' => ceil($total / $limit),
            'current_page' => $page
        ];
    }
    
    public function get_recent($limit = 10) {
        $stmt = $this->db->prepare("
            SELECT c.*, u.login as author_login, p.title as post_title, p.id as post_id
            FROM comments c 
            JOIN users u ON c.user_id = u.id 
            JOIN posts p ON c.post_id = p.id 
            ORDER BY c.created_at DESC 
            LIMIT ?
        ");
        $stmt->execute([$limit]);
        return $stmt->fetchAll();
    }
}
