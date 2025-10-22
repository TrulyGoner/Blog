<?php
require_once '../src/config.php';
require_once '../src/db.php';
require_once '../src/helpers.php';
require_once '../src/auth.php';
require_once '../src/post.php';

header('Content-Type: application/json');

$auth = new Auth();
$post = new Post();

if (!is_logged_in()) {
    echo json_encode(['success' => false, 'message' => 'Необходимо войти в систему']);
    exit;
}

$auth->check_session();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Неверный метод запроса']);
    exit;
}

if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
    echo json_encode(['success' => false, 'message' => 'Ошибка безопасности']);
    exit;
}

$action = $_POST['action'] ?? '';
$post_id = (int)($_POST['post_id'] ?? 0);
$reaction_type = $_POST['reaction_type'] ?? '';

if (!$post_id) {
    echo json_encode(['success' => false, 'message' => 'Неверный ID поста']);
    exit;
}

$current_user = get_current_user_data();

$post_data = $post->get_by_id($post_id);
if (!$post_data) {
    echo json_encode(['success' => false, 'message' => 'Пост не найден']);
    exit;
}

if ($action === 'toggle_reaction') {
    if (!in_array($reaction_type, ['like', 'dislike'])) {
        echo json_encode(['success' => false, 'message' => 'Неверный тип реакции']);
        exit;
    }
    
    $current_reaction = $post->get_user_reaction($post_id, $current_user['id']);
    
    if ($current_reaction === $reaction_type) {
        $result = $post->remove_reaction($post_id, $current_user['id']);
    } else {
        $result = $post->add_reaction($post_id, $current_user['id'], $reaction_type);
    }
    
    if ($result['success']) {
        $updated_post = $post->get_by_id($post_id);
        $new_reaction = $post->get_user_reaction($post_id, $current_user['id']);
        
        echo json_encode([
            'success' => true,
            'likes' => $updated_post['likes'],
            'dislikes' => $updated_post['dislikes'],
            'user_reaction' => $new_reaction
        ]);
    } else {
        echo json_encode($result);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Неверное действие']);
}
