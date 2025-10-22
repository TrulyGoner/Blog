<?php
require_once '../src/config.php';
require_once '../src/db.php';
require_once '../src/helpers.php';
require_once '../src/auth.php';
require_once '../src/comment.php';

$auth = new Auth();
$comment = new Comment();

require_login();
$auth->check_session();

$current_user = get_current_user_data();

$comment_id = (int)($_GET['id'] ?? 0);
if (!$comment_id) {
    flash_message('Комментарий не найден', 'error');
    redirect('index.php');
}

$post_id = (int)($_GET['post_id'] ?? 0);

$result = $comment->delete($comment_id, $current_user['id']);
if ($result['success']) {
    flash_message('Комментарий успешно удален!', 'success');
} else {
    flash_message($result['message'], 'error');
}

if ($post_id) {
    redirect('post.php?id=' . $post_id);
} else {
    redirect('index.php');
}
