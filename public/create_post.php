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
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $error = 'Ошибка безопасности. Попробуйте еще раз.';
    } else {
        $title = sanitize_input($_POST['title'] ?? '');
        $content = sanitize_input($_POST['content'] ?? '');
        
        $errors = validate_post_data(['title' => $title, 'content' => $content]);
        
        if (empty($errors)) {
            $result = $post->create($current_user['id'], $title, $content);
            if ($result['success']) {
                flash_message('Пост успешно создан!', 'success');
                redirect('post.php?id=' . $result['id']);
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
    <title>Создать пост - <?php echo SITE_NAME; ?></title>
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
                    <div class="flex items-center text-gray-700">
                        <i class="fas fa-user mr-1"></i><?php echo htmlspecialchars($current_user['login']); ?>
                    </div>
                </div>
            </div>
        </div>
    </nav>

    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div class="bg-white rounded-lg shadow-md">
            <div class="px-6 py-4 border-b border-gray-200">
                <h1 class="text-2xl font-bold text-gray-900">
                    <i class="fas fa-plus mr-2"></i>Создать новый пост
                </h1>
            </div>
            
            <form method="POST" class="p-6">
                <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                
                <?php if ($error): ?>
                    <div class="mb-6 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">
                        <i class="fas fa-exclamation-triangle mr-2"></i>
                        <?php echo $error; ?>
                    </div>
                <?php endif; ?>
                
                <div class="space-y-6">
                    <div>
                        <label for="title" class="block text-sm font-medium text-gray-700 mb-2">
                            Заголовок поста
                        </label>
                        <input type="text" id="title" name="title" required
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                               placeholder="Введите заголовок поста"
                               value="<?php echo htmlspecialchars($_POST['title'] ?? ''); ?>">
                    </div>
                    
                    <div>
                        <label for="content" class="block text-sm font-medium text-gray-700 mb-2">
                            Содержание поста
                        </label>
                        <textarea id="content" name="content" rows="15" required
                                  class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                  placeholder="Напишите содержание вашего поста..."><?php echo htmlspecialchars($_POST['content'] ?? ''); ?></textarea>
                    </div>
                    
                    <div class="flex justify-end space-x-4">
                        <a href="index.php" 
                           class="px-6 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-500">
                            Отмена
                        </a>
                        <button type="submit" 
                                class="px-6 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <i class="fas fa-save mr-1"></i>Опубликовать пост
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</body>
</html>
