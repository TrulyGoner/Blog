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

$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$page = max(1, $page);

$posts_data = $post->get_list($page);
$posts = $posts_data['posts'];
$total_pages = $posts_data['pages'];
$current_page = $posts_data['current_page'];

$recent_comments = $comment->get_recent(5);

$current_user = get_current_user_data();
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo SITE_NAME; ?></title>
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
                    <?php if ($current_user): ?>
                        <a href="create_post.php" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition">
                            <i class="fas fa-plus mr-1"></i>Новый пост
                        </a>
                        <div class="relative group">
                            <button class="flex items-center text-gray-700 hover:text-blue-600">
                                <i class="fas fa-user mr-1"></i><?php echo htmlspecialchars($current_user['login']); ?>
                                <i class="fas fa-chevron-down ml-1"></i>
                            </button>
                            <div class="absolute right-0 mt-2 w-48 bg-white rounded-lg shadow-lg opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all duration-200">
                                <a href="profile.php" class="block px-4 py-2 text-gray-700 hover:bg-gray-100">
                                    <i class="fas fa-user mr-2"></i>Профиль
                                </a>
                                <a href="logout.php" class="block px-4 py-2 text-gray-700 hover:bg-gray-100">
                                    <i class="fas fa-sign-out-alt mr-2"></i>Выход
                                </a>
                            </div>
                        </div>
                    <?php else: ?>
                        <a href="login.php" class="text-gray-700 hover:text-blue-600">
                            <i class="fas fa-sign-in-alt mr-1"></i>Вход
                        </a>
                        <a href="register.php" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition">
                            <i class="fas fa-user-plus mr-1"></i>Регистрация
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </nav>

        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div class="grid grid-cols-1 lg:grid-cols-4 gap-8">
            <div class="lg:col-span-3">
                <?php
                $flash = get_flash_message();
                if ($flash):
                ?>
                <div class="mb-6 p-4 rounded-lg <?php echo $flash['type'] === 'error' ? 'bg-red-100 text-red-700' : 'bg-green-100 text-green-700'; ?>">
                    <?php echo htmlspecialchars($flash['message']); ?>
                </div>
                <?php endif; ?>

                <div class="mb-8">
                    <h1 class="text-3xl font-bold text-gray-900">Последние посты</h1>
                    <p class="text-gray-600 mt-2">Добро пожаловать в наш блог!</p>
                </div>
                <?php if (empty($posts)): ?>
                    <div class="text-center py-12">
                        <i class="fas fa-newspaper text-6xl text-gray-300 mb-4"></i>
                        <h3 class="text-xl font-semibold text-gray-500 mb-2">Пока нет постов</h3>
                        <p class="text-gray-400">Станьте первым, кто опубликует интересную статью!</p>
                    </div>
                <?php else: ?>
                    <div class="space-y-6">
                        <?php foreach ($posts as $post_item): ?>
                            <article class="bg-white rounded-lg shadow-md hover:shadow-lg transition-shadow duration-200">
                                <div class="p-6">
                                    <div class="flex items-center justify-between mb-4">
                                        <div class="flex items-center space-x-3">
                                            <div class="w-10 h-10 bg-blue-100 rounded-full flex items-center justify-center">
                                                <i class="fas fa-user text-blue-600"></i>
                                            </div>
                                            <div>
                                                <p class="font-semibold text-gray-900"><?php echo htmlspecialchars($post_item['author_login']); ?></p>
                                                <p class="text-sm text-gray-500"><?php echo format_date_relative($post_item['created_at']); ?></p>
                                            </div>
                                        </div>
                                        <?php if ($current_user && $current_user['id'] == $post_item['user_id']): ?>
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
                                        <?php endif; ?>
                                    </div>
                                    
                                    <h2 class="text-2xl font-bold text-gray-900 mb-3">
                                        <a href="post.php?id=<?php echo $post_item['id']; ?>" 
                                           class="hover:text-blue-600 transition-colors">
                                            <?php echo htmlspecialchars($post_item['title']); ?>
                                        </a>
                                    </h2>
                                    
                                    <p class="text-gray-700 mb-4 leading-relaxed">
                                        <?php echo htmlspecialchars(truncate_text($post_item['content'], 300)); ?>
                                    </p>
                                    
                                    <div class="flex items-center justify-between">
                                        <div class="flex items-center space-x-6 text-sm text-gray-500">
                                            <span class="flex items-center">
                                                <i class="fas fa-eye mr-1"></i>
                                                <?php echo $post_item['views']; ?> просмотров
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
                                        
                                        <a href="post.php?id=<?php echo $post_item['id']; ?>" 
                                           class="text-blue-600 hover:text-blue-800 font-medium">
                                            Читать далее →
                                        </a>
                                    </div>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    </div>

                    <?php if ($total_pages > 1): ?>
                        <div class="mt-8 flex justify-center">
                            <nav class="flex space-x-2">
                                <?php
                                $pagination = get_pagination($current_page, $total_pages, 'index.php');
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

            <div class="lg:col-span-1">
                <div class="space-y-6">
                    <div class="bg-white rounded-lg shadow-md p-6">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">
                            <i class="fas fa-comments mr-2"></i>Последние комментарии
                        </h3>
                        <?php if (empty($recent_comments)): ?>
                            <p class="text-gray-500 text-sm">Пока нет комментариев</p>
                        <?php else: ?>
                            <div class="space-y-3">
                                <?php foreach ($recent_comments as $comment_item): ?>
                                    <div class="border-l-4 border-blue-200 pl-3">
                                        <p class="text-sm text-gray-600">
                                            <a href="post.php?id=<?php echo $comment_item['post_id']; ?>" 
                                               class="text-blue-600 hover:text-blue-800">
                                                <?php echo htmlspecialchars(truncate_text($comment_item['text'], 50)); ?>
                                            </a>
                                        </p>
                                        <p class="text-xs text-gray-400 mt-1">
                                            <?php echo htmlspecialchars($comment_item['author_login']); ?> • 
                                            <?php echo format_date_relative($comment_item['created_at']); ?>
                                        </p>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="bg-white rounded-lg shadow-md p-6">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">
                            <i class="fas fa-chart-bar mr-2"></i>Статистика
                        </h3>
                        <div class="space-y-2 text-sm">
                            <div class="flex justify-between">
                                <span class="text-gray-600">Всего постов:</span>
                                <span class="font-semibold"><?php echo $posts_data['total']; ?></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        function confirmDelete(message) {
            return confirm(message || 'Вы уверены?');
        }
    </script>
</body>
</html>