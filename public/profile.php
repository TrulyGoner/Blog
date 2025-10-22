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

require_login();
$auth->check_session();

$current_user = get_current_user_data();

$stats = $auth->get_user_stats($current_user['id']);

$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$page = max(1, $page);
$posts_data = $post->get_by_user($current_user['id'], $page);
$user_posts = $posts_data['posts'];
$total_pages = $posts_data['pages'];
$current_page = $posts_data['current_page'];

$comments_data = $comment->get_by_user($current_user['id'], 1, 10);
$user_comments = $comments_data['comments'];
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Профиль - <?php echo SITE_NAME; ?></title>
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
                    <a href="create_post.php" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition">
                        <i class="fas fa-plus mr-1"></i>Новый пост
                    </a>
                    <div class="flex items-center text-gray-700">
                        <i class="fas fa-user mr-1"></i><?php echo htmlspecialchars($current_user['login']); ?>
                    </div>
                </div>
            </div>
        </div>
    </nav>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- Основной контент -->
            <div class="lg:col-span-2">
                <div class="mb-8">
                    <h1 class="text-3xl font-bold text-gray-900">Мой профиль</h1>
                    <p class="text-gray-600 mt-2">Управляйте своими постами и отслеживайте статистику</p>
                </div>

                <div class="bg-white rounded-lg shadow-md mb-8">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <h2 class="text-xl font-semibold text-gray-900">
                            <i class="fas fa-newspaper mr-2"></i>Мои посты (<?php echo $posts_data['total']; ?>)
                        </h2>
                    </div>
                    
                    <div class="p-6">
                        <?php if (empty($user_posts)): ?>
                            <div class="text-center py-8">
                                <i class="fas fa-newspaper text-4xl text-gray-300 mb-4"></i>
                                <h3 class="text-lg font-semibold text-gray-500 mb-2">У вас пока нет постов</h3>
                                <p class="text-gray-400 mb-4">Создайте свой первый пост и поделитесь мыслями с читателями!</p>
                                <a href="create_post.php" class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                                    <i class="fas fa-plus mr-2"></i>Создать пост
                                </a>
                            </div>
                        <?php else: ?>
                            <div class="space-y-4">
                                <?php foreach ($user_posts as $post_item): ?>
                                    <div class="border border-gray-200 rounded-lg p-4 hover:bg-gray-50">
                                        <div class="flex items-center justify-between mb-2">
                                            <h3 class="text-lg font-semibold text-gray-900">
                                                <a href="post.php?id=<?php echo $post_item['id']; ?>" 
                                                   class="hover:text-blue-600">
                                                    <?php echo htmlspecialchars($post_item['title']); ?>
                                                </a>
                                            </h3>
                                            <div class="flex space-x-2">
                                                <a href="edit_post.php?id=<?php echo $post_item['id']; ?>" 
                                                   class="text-blue-600 hover:text-blue-800">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <a href="delete_post.php?id=<?php echo $post_item['id']; ?>" 
                                                   class="text-red-600 hover:text-red-800"
                                                   onclick="return confirm('Вы уверены, что хотите удалить этот пост?')">
                                                    <i class="fas fa-trash"></i>
                                                </a>
                                            </div>
                                        </div>
                                        
                                        <p class="text-gray-600 mb-3">
                                            <?php echo htmlspecialchars(truncate_text($post_item['content'], 150)); ?>
                                        </p>
                                        
                                        <div class="flex items-center justify-between text-sm text-gray-500">
                                            <div class="flex items-center space-x-4">
                                                <span class="flex items-center">
                                                    <i class="fas fa-calendar mr-1"></i>
                                                    <?php echo format_date($post_item['created_at']); ?>
                                                </span>
                                                <span class="flex items-center">
                                                    <i class="fas fa-eye mr-1"></i>
                                                    <?php echo $post_item['views']; ?>
                                                </span>
                                                <span class="flex items-center">
                                                    <i class="fas fa-thumbs-up mr-1"></i>
                                                    <?php echo $post_item['likes']; ?>
                                                </span>
                                                <span class="flex items-center">
                                                    <i class="fas fa-thumbs-down mr-1"></i>
                                                    <?php echo $post_item['dislikes']; ?>
                                                </span>
                                            </div>
                                            
                                            <?php if ($post_item['updated_at'] != $post_item['created_at']): ?>
                                                <span class="text-xs text-gray-400">
                                                    Обновлен: <?php echo format_date_relative($post_item['updated_at']); ?>
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            
                            <?php if ($total_pages > 1): ?>
                                <div class="mt-6 flex justify-center">
                                    <nav class="flex space-x-2">
                                        <?php
                                        $pagination = get_pagination($current_page, $total_pages, 'profile.php');
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

                <div class="bg-white rounded-lg shadow-md">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <h2 class="text-xl font-semibold text-gray-900">
                            <i class="fas fa-comments mr-2"></i>Мои комментарии (<?php echo $comments_data['total']; ?>)
                        </h2>
                    </div>
                    
                    <div class="p-6">
                        <?php if (empty($user_comments)): ?>
                            <div class="text-center py-8">
                                <i class="fas fa-comments text-4xl text-gray-300 mb-4"></i>
                                <p class="text-gray-500">У вас пока нет комментариев</p>
                            </div>
                        <?php else: ?>
                            <div class="space-y-4">
                                <?php foreach ($user_comments as $comment_item): ?>
                                    <div class="border-l-4 border-blue-200 pl-4">
                                        <div class="flex items-center justify-between mb-2">
                                            <div class="flex items-center space-x-2">
                                                <span class="text-sm text-gray-500">
                                                    <?php echo format_date_relative($comment_item['created_at']); ?>
                                                </span>
                                            </div>
                                            <a href="delete_comment.php?id=<?php echo $comment_item['id']; ?>&post_id=<?php echo $comment_item['post_id']; ?>" 
                                               class="text-red-600 hover:text-red-800 text-sm"
                                               onclick="return confirm('Вы уверены, что хотите удалить этот комментарий?')">
                                                <i class="fas fa-trash"></i>
                                            </a>
                                        </div>
                                        
                                        <p class="text-gray-700 mb-2">
                                            <a href="post.php?id=<?php echo $comment_item['post_id']; ?>" 
                                               class="text-blue-600 hover:text-blue-800 font-medium">
                                                <?php echo htmlspecialchars($comment_item['post_title']); ?>
                                            </a>
                                        </p>
                                        
                                        <div class="text-gray-600 whitespace-pre-wrap">
                                            <?php echo htmlspecialchars(truncate_text($comment_item['text'], 100)); ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="lg:col-span-1">
                <div class="space-y-6">
                    <div class="bg-white rounded-lg shadow-md p-6">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">
                            <i class="fas fa-user mr-2"></i>Информация
                        </h3>
                        <div class="space-y-3">
                            <div>
                                <span class="text-sm text-gray-600">Логин:</span>
                                <span class="font-medium"><?php echo htmlspecialchars($current_user['login']); ?></span>
                            </div>
                            <div>
                                <span class="text-sm text-gray-600">Дата регистрации:</span>
                                <span class="font-medium"><?php echo format_date($current_user['created_at']); ?></span>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white rounded-lg shadow-md p-6">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">
                            <i class="fas fa-chart-bar mr-2"></i>Статистика постов
                        </h3>
                        <div class="space-y-3">
                            <div class="flex justify-between">
                                <span class="text-gray-600">Всего постов:</span>
                                <span class="font-semibold text-blue-600"><?php echo $stats['post_count']; ?></span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-600">Просмотры:</span>
                                <span class="font-semibold text-green-600"><?php echo number_format($stats['total_views']); ?></span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-600">Лайки:</span>
                                <span class="font-semibold text-blue-600"><?php echo number_format($stats['total_likes']); ?></span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-600">Дизлайки:</span>
                                <span class="font-semibold text-red-600"><?php echo number_format($stats['total_dislikes']); ?></span>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white rounded-lg shadow-md p-6">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">
                            <i class="fas fa-comments mr-2"></i>Комментарии
                        </h3>
                        <div class="space-y-3">
                            <div class="flex justify-between">
                                <span class="text-gray-600">Всего комментариев:</span>
                                <span class="font-semibold text-purple-600"><?php echo $comments_data['total']; ?></span>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white rounded-lg shadow-md p-6">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">
                            <i class="fas fa-bolt mr-2"></i>Быстрые действия
                        </h3>
                        <div class="space-y-3">
                            <a href="create_post.php" 
                               class="block w-full text-center px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition">
                                <i class="fas fa-plus mr-2"></i>Создать пост
                            </a>
                            <a href="index.php" 
                               class="block w-full text-center px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition">
                                <i class="fas fa-home mr-2"></i>На главную
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
