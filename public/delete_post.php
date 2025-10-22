<?php
require_once '../src/config.php';
require_once '../src/db.php';
require_once '../src/helpers.php';
require_once '../src/auth.php';
require_once '../src/post.php';

$auth = new Auth();
$post = new Post();

require_login();
$auth->check_session();

$current_user = get_current_user_data();

$post_id = (int)($_GET['id'] ?? 0);
if (!$post_id) {
    flash_message('Пост не найден', 'error');
    redirect('index.php');
}

$post_data = $post->get_by_id($post_id);
if (!$post_data || $post_data['user_id'] != $current_user['id']) {
    flash_message('Пост не найден или у вас нет прав на его удаление', 'error');
    redirect('index.php');
}

$result = $post->delete($post_id, $current_user['id']);
if ($result['success']) {
    flash_message('Пост успешно удален!', 'success');
} else {
    flash_message($result['message'], 'error');
}

redirect('index.php');