<?php
require_once '../src/config.php';
require_once '../src/db.php';
require_once '../src/helpers.php';
require_once '../src/auth.php';
require_once '../src/post.php';
require_once '../src/comment.php';

$auth = new Auth();
$post = new Post();
$comment = new Comment();

$auth->check_session();

$post_id = (int)($_GET['id'] ?? 0);
if (!$post_id) {
    flash_message('Пост не найден', 'error');
    redirect('index.php');
}

$post_data = $post->get_by_id($post_id, true);
if (!$post_data) {
    flash_message('Пост не найден', 'error');
    redirect('index.php');
}

$current_user = get_current_user_data();
$user_reaction = $current_user ? $post->get_user_reaction($post_id, $current_user['id']) : null;

$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$page = max(1, $page);
$comments_data = $comment->get_by_post($post_id, $page);
$comments = $comments_data['comments'];
$total_comment_pages = $comments_data['pages'];
$current_comment_page = $comments_data['current_page'];

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_comment') {
    if (!is_logged_in()) {
        $error = 'Необходимо войти в систему для добавления комментариев';
    } elseif (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $error = 'Ошибка безопасности. Попробуйте еще раз.';
    } else {
        $text = sanitize_input($_POST['text'] ?? '');
        $errors = validate_comment_data(['text' => $text]);
        
        if (empty($errors)) {
            $result = $comment->create($post_id, $current_user['id'], $text);
            if ($result['success']) {
                flash_message('Комментарий добавлен!', 'success');
                redirect('post.php?id=' . $post_id);
            } else {
                $error = $result['message'];
            }
        } else {
            $error = implode('<br>', $errors);
        }
    }
}

$csrf_token = generate_csrf_token();
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($post_data['title']); ?> - <?php echo SITE_NAME; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-gray-50 min-h-screen">
    <nav class="bg-white shadow-lg">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <div class="flex items-center">
                    <a href="index.php" class="text-2xl font-bold text-blue-600">
                        <i class="fas fa-blog mr-2"></i><?php echo SITE_NAME; ?>
                    </a>
                </div>
                <div class="flex items-center space-x-4">
                    <a href="index.php" class="text-gray-700 hover:text-blue-600">
                        <i class="fas fa-arrow-left mr-1"></i>Назад к блогу
                    </a>
                    <?php if ($current_user): ?>
                        <div class="flex items-center text-gray-700">
                            <i class="fas fa-user mr-1"></i><?php echo htmlspecialchars($current_user['login']); ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </nav>

    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <?php
        $flash = get_flash_message();
        if ($flash):
        ?>
        <div class="mb-6 p-4 rounded-lg <?php echo $flash['type'] === 'error' ? 'bg-red-100 text-red-700' : 'bg-green-100 text-green-700'; ?>">
            <?php echo htmlspecialchars($flash['message']); ?>
        </div>
        <?php endif; ?>

        <article class="bg-white rounded-lg shadow-md mb-8">
            <div class="p-8">
                <div class="mb-6">
                    <h1 class="text-3xl font-bold text-gray-900 mb-4"><?php echo htmlspecialchars($post_data['title']); ?></h1>
                    
                    <div class="flex items-center justify-between">
                        <div class="flex items-center space-x-4 text-sm text-gray-500">
                            <div class="flex items-center">
                                <i class="fas fa-user mr-1"></i>
                                <?php echo htmlspecialchars($post_data['author_login']); ?>
                            </div>
                            <div class="flex items-center">
                                <i class="fas fa-calendar mr-1"></i>
                                <?php echo format_date($post_data['created_at']); ?>
                            </div>
                            <?php if ($post_data['updated_at'] != $post_data['created_at']): ?>
                                <div class="flex items-center">
                                    <i class="fas fa-edit mr-1"></i>
                                    Обновлен: <?php echo format_date($post_data['updated_at']); ?>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <?php if ($current_user && $current_user['id'] == $post_data['user_id']): ?>
                            <div class="flex space-x-2">
                                <a href="edit_post.php?id=<?php echo $post_data['id']; ?>" 
                                   class="text-blue-600 hover:text-blue-800 px-3 py-1 rounded">
                                    <i class="fas fa-edit mr-1"></i>Редактировать
                                </a>
                                <a href="delete_post.php?id=<?php echo $post_data['id']; ?>" 
                                   class="text-red-600 hover:text-red-800 px-3 py-1 rounded"
                                   onclick="return confirm('Вы уверены, что хотите удалить этот пост?')">
                                    <i class="fas fa-trash mr-1"></i>Удалить
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="prose max-w-none mb-6">
                    <div class="text-gray-800 leading-relaxed whitespace-pre-wrap"><?php echo htmlspecialchars($post_data['content']); ?></div>
                </div>
                
                <div class="border-t border-gray-200 pt-6">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center space-x-6 text-sm text-gray-500">
                            <span class="flex items-center">
                                <i class="fas fa-eye mr-1"></i>
                                <?php echo $post_data['views']; ?> просмотров
                            </span>
                            <span class="flex items-center">
                                <i class="fas fa-comments mr-1"></i>
                                <?php echo $comments_data['total']; ?> комментариев
                            </span>
                        </div>
                        
                        <?php if ($current_user): ?>
                            <div class="flex items-center space-x-4">
                                <button onclick="toggleReaction('like')" 
                                        class="flex items-center space-x-1 px-3 py-1 rounded <?php echo $user_reaction === 'like' ? 'bg-blue-100 text-blue-600' : 'text-gray-500 hover:text-blue-600'; ?>">
                                    <i class="fas fa-thumbs-up"></i>
                                    <span><?php echo $post_data['likes']; ?></span>
                                </button>
                                <button onclick="toggleReaction('dislike')" 
                                        class="flex items-center space-x-1 px-3 py-1 rounded <?php echo $user_reaction === 'dislike' ? 'bg-red-100 text-red-600' : 'text-gray-500 hover:text-red-600'; ?>">
                                    <i class="fas fa-thumbs-down"></i>
                                    <span><?php echo $post_data['dislikes']; ?></span>
                                </button>
                            </div>
                        <?php else: ?>
                            <div class="flex items-center space-x-4 text-sm text-gray-500">
                                <span class="flex items-center">
                                    <i class="fas fa-thumbs-up mr-1"></i>
                                    <?php echo $post_data['likes']; ?>
                                </span>
                                <span class="flex items-center">
                                    <i class="fas fa-thumbs-down mr-1"></i>
                                    <?php echo $post_data['dislikes']; ?>
                                </span>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </article>

        <div class="bg-white rounded-lg shadow-md">
            <div class="px-6 py-4 border-b border-gray-200">
                <h2 class="text-xl font-semibold text-gray-900">
                    <i class="fas fa-comments mr-2"></i>Комментарии (<?php echo $comments_data['total']; ?>)
                </h2>
            </div>
            
            <?php if ($current_user): ?>
                <div class="p-6 border-b border-gray-200">
                    <form method="POST" class="space-y-4">
                        <input type="hidden" name="action" value="add_comment">
                        <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                        
                        <?php if ($error): ?>
                            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">
                                <i class="fas fa-exclamation-triangle mr-2"></i>
                                <?php echo $error; ?>
                            </div>
                        <?php endif; ?>
                        
                        <div>
                            <label for="text" class="block text-sm font-medium text-gray-700 mb-2">
                                Добавить комментарий
                            </label>
                            <textarea id="text" name="text" rows="3" required
                                      class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                      placeholder="Напишите ваш комментарий..."></textarea>
                        </div>
                        
                        <div class="flex justify-end">
                            <button type="submit" 
                                    class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500">
                                <i class="fas fa-paper-plane mr-1"></i>Отправить
                            </button>
                        </div>
                    </form>
                </div>
            <?php else: ?>
                <div class="p-6 border-b border-gray-200 text-center">
                    <p class="text-gray-500">
                        <a href="login.php" class="text-blue-600 hover:text-blue-800">Войдите в систему</a>
                        для добавления комментариев
                    </p>
                </div>
            <?php endif; ?>
            
            <div class="p-6">
                <?php if (empty($comments)): ?>
                    <div class="text-center py-8">
                        <i class="fas fa-comments text-4xl text-gray-300 mb-4"></i>
                        <p class="text-gray-500">Пока нет комментариев. Станьте первым!</p>
                    </div>
                <?php else: ?>
                    <div class="space-y-6">
                        <?php foreach ($comments as $comment_item): ?>
                            <div class="border-l-4 border-gray-200 pl-4">
                                <div class="flex items-center justify-between mb-2">
                                    <div class="flex items-center space-x-2">
                                        <span class="font-semibold text-gray-900">
                                            <?php echo htmlspecialchars($comment_item['author_login']); ?>
                                        </span>
                                        <span class="text-sm text-gray-500">
                                            <?php echo format_date_relative($comment_item['created_at']); ?>
                                        </span>
                                    </div>
                                    
                                    <?php if ($current_user && $current_user['id'] == $comment_item['user_id']): ?>
                                        <a href="delete_comment.php?id=<?php echo $comment_item['id']; ?>&post_id=<?php echo $post_id; ?>" 
                                           class="text-red-600 hover:text-red-800 text-sm"
                                           onclick="return confirm('Вы уверены, что хотите удалить этот комментарий?')">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="text-gray-700 whitespace-pre-wrap">
                                    <?php echo htmlspecialchars($comment_item['text']); ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <?php if ($total_comment_pages > 1): ?>
                        <div class="mt-6 flex justify-center">
                            <nav class="flex space-x-2">
                                <?php
                                $pagination = get_pagination($current_comment_page, $total_comment_pages, 'post.php?id=' . $post_id);
                                foreach ($pagination as $item):
                                ?>
                                    <a href="<?php echo $item['url']; ?>" 
                                       class="px-3 py-2 rounded-lg <?php echo $item['active'] ? 'bg-blue-600 text-white' : 'bg-white text-gray-700 hover:bg-gray-100'; ?>">
                                        <?php echo $item['text']; ?>
                                    </a>
                                <?php endforeach; ?>
                            </nav>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
        function toggleReaction(type) {
            if (!<?php echo $current_user ? 'true' : 'false'; ?>) {
                alert('Необходимо войти в систему для оценки постов');
                return;
            }
            
            const formData = new FormData();
            formData.append('action', 'toggle_reaction');
            formData.append('post_id', <?php echo $post_id; ?>);
            formData.append('reaction_type', type);
            formData.append('csrf_token', '<?php echo $csrf_token; ?>');
            
            fetch('like_ajax.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    alert(data.message || 'Ошибка при обновлении реакции');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Произошла ошибка при обновлении реакции');
            });
        }
    </script>
</body>
</html>
