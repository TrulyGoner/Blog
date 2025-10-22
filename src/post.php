<?php
require_once 'config.php';
require_once 'db.php';
require_once 'helpers.php';

class Post {
    private $db;
    
    public function __construct() {
        $this->db = getDB();
    }
    
    public function create($user_id, $title, $content) {
        try {
            $stmt = $this->db->prepare("INSERT INTO posts (user_id, title, content) VALUES (?, ?, ?)");
            $stmt->execute([$user_id, $title, $content]);
            return ['success' => true, 'id' => $this->db->lastInsertId()];
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Ошибка при создании поста'];
        }
    }
    
    public function update($post_id, $user_id, $title, $content) {
        $stmt = $this->db->prepare("SELECT id FROM posts WHERE id = ? AND user_id = ?");
        $stmt->execute([$post_id, $user_id]);
        
        if (!$stmt->fetch()) {
            return ['success' => false, 'message' => 'Пост не найден или у вас нет прав на его редактирование'];
        }
        
        try {
            $stmt = $this->db->prepare("UPDATE posts SET title = ?, content = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ? AND user_id = ?");
            $stmt->execute([$title, $content, $post_id, $user_id]);
            return ['success' => true];
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Ошибка при обновлении поста'];
        }
    }
    
    public function delete($post_id, $user_id) {
        $stmt = $this->db->prepare("SELECT id FROM posts WHERE id = ? AND user_id = ?");
        $stmt->execute([$post_id, $user_id]);
        
        if (!$stmt->fetch()) {
            return ['success' => false, 'message' => 'Пост не найден или у вас нет прав на его удаление'];
        }
        
        try {
            $this->db->beginTransaction();
            
            $stmt = $this->db->prepare("DELETE FROM comments WHERE post_id = ?");
            $stmt->execute([$post_id]);
            
            $stmt = $this->db->prepare("DELETE FROM post_reactions WHERE post_id = ?");
            $stmt->execute([$post_id]);
            
            $stmt = $this->db->prepare("DELETE FROM posts WHERE id = ? AND user_id = ?");
            $stmt->execute([$post_id, $user_id]);
            
            $this->db->commit();
            return ['success' => true];
        } catch (PDOException $e) {
            $this->db->rollback();
            return ['success' => false, 'message' => 'Ошибка при удалении поста'];
        }
    }
    
    public function get_by_id($post_id, $increment_views = false) {
        $stmt = $this->db->prepare("
            SELECT p.*, u.login as author_login 
            FROM posts p 
            JOIN users u ON p.user_id = u.id 
            WHERE p.id = ?
        ");
        $stmt->execute([$post_id]);
        $post = $stmt->fetch();
        
        if ($post && $increment_views) {
            $this->increment_views($post_id);
            $post['views']++;
        }
        
        return $post;
    }
    
    public function get_list($page = 1, $limit = POSTS_PER_PAGE) {
        $offset = ($page - 1) * $limit;
        
        $stmt = $this->db->prepare("
            SELECT p.*, u.login as author_login 
            FROM posts p 
            JOIN users u ON p.user_id = u.id 
            ORDER BY p.created_at DESC 
            LIMIT ? OFFSET ?
        ");
        $stmt->execute([$limit, $offset]);
        $posts = $stmt->fetchAll();
        
        $stmt = $this->db->prepare("SELECT COUNT(*) as total FROM posts");
        $stmt->execute();
        $total = $stmt->fetch()['total'];
        
        return [
            'posts' => $posts,
            'total' => $total,
            'pages' => ceil($total / $limit),
            'current_page' => $page
        ];
    }
    
    public function get_by_user($user_id, $page = 1, $limit = POSTS_PER_PAGE) {
        $offset = ($page - 1) * $limit;
        
        $stmt = $this->db->prepare("
            SELECT p.*, u.login as author_login 
            FROM posts p 
            JOIN users u ON p.user_id = u.id 
            WHERE p.user_id = ?
            ORDER BY p.created_at DESC 
            LIMIT ? OFFSET ?
        ");
        $stmt->execute([$user_id, $limit, $offset]);
        $posts = $stmt->fetchAll();
        
        $stmt = $this->db->prepare("SELECT COUNT(*) as total FROM posts WHERE user_id = ?");
        $stmt->execute([$user_id]);
        $total = $stmt->fetch()['total'];
        
        return [
            'posts' => $posts,
            'total' => $total,
            'pages' => ceil($total / $limit),
            'current_page' => $page
        ];
    }
    
    public function increment_views($post_id) {
        $stmt = $this->db->prepare("UPDATE posts SET views = views + 1 WHERE id = ?");
        $stmt->execute([$post_id]);
    }
    
    public function add_reaction($post_id, $user_id, $reaction_type) {
        try {
            $this->db->beginTransaction();
            
            $stmt = $this->db->prepare("DELETE FROM post_reactions WHERE post_id = ? AND user_id = ?");
            $stmt->execute([$post_id, $user_id]);
            
            $stmt = $this->db->prepare("INSERT INTO post_reactions (post_id, user_id, reaction_type) VALUES (?, ?, ?)");
            $stmt->execute([$post_id, $user_id, $reaction_type]);
            
            $field = $reaction_type === 'like' ? 'likes' : 'dislikes';
            $stmt = $this->db->prepare("UPDATE posts SET $field = $field + 1 WHERE id = ?");
            $stmt->execute([$post_id]);
            
            $this->db->commit();
            return ['success' => true];
        } catch (PDOException $e) {
            $this->db->rollback();
            return ['success' => false, 'message' => 'Ошибка при добавлении реакции'];
        }
    }
    
    public function remove_reaction($post_id, $user_id) {
        try {
            $this->db->beginTransaction();
            
            $stmt = $this->db->prepare("SELECT reaction_type FROM post_reactions WHERE post_id = ? AND user_id = ?");
            $stmt->execute([$post_id, $user_id]);
            $reaction = $stmt->fetch();
            
            if ($reaction) {
                $stmt = $this->db->prepare("DELETE FROM post_reactions WHERE post_id = ? AND user_id = ?");
                $stmt->execute([$post_id, $user_id]);
                
                $field = $reaction['reaction_type'] === 'like' ? 'likes' : 'dislikes';
                $stmt = $this->db->prepare("UPDATE posts SET $field = GREATEST($field - 1, 0) WHERE id = ?");
                $stmt->execute([$post_id]);
            }
            
            $this->db->commit();
            return ['success' => true];
        } catch (PDOException $e) {
            $this->db->rollback();
            return ['success' => false, 'message' => 'Ошибка при удалении реакции'];
        }
    }
    
    public function get_user_reaction($post_id, $user_id) {
        $stmt = $this->db->prepare("SELECT reaction_type FROM post_reactions WHERE post_id = ? AND user_id = ?");
        $stmt->execute([$post_id, $user_id]);
        $result = $stmt->fetch();
        return $result ? $result['reaction_type'] : null;
    }
}
