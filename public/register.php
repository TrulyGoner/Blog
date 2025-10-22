<?php
require_once '../src/config.php';
require_once '../src/db.php';
require_once '../src/helpers.php';
require_once '../src/auth.php';

$auth = new Auth();

if (is_logged_in()) {
    redirect('index.php');
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $error = 'Ошибка безопасности. Попробуйте еще раз.';
    } else {
        $login = sanitize_input($_POST['login'] ?? '');
        $password = $_POST['password'] ?? '';
        $password_confirm = $_POST['password_confirm'] ?? '';
        
        $errors = validate_user_data([
            'login' => $login,
            'password' => $password,
            'password_confirm' => $password_confirm
        ]);
        
        if (empty($errors)) {
            $result = $auth->register($login, $password);
            if ($result['success']) {
                flash_message($result['message'], 'success');
                redirect('login.php');
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
    <title>Регистрация - <?php echo SITE_NAME; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-gray-50 min-h-screen flex items-center justify-center">
    <div class="max-w-md w-full space-y-8">
        <div>
            <div class="mx-auto h-12 w-12 flex items-center justify-center rounded-full bg-blue-100">
                <i class="fas fa-user-plus text-blue-600 text-xl"></i>
            </div>
            <h2 class="mt-6 text-center text-3xl font-extrabold text-gray-900">
                Создать аккаунт
            </h2>
            <p class="mt-2 text-center text-sm text-gray-600">
                Или
                <a href="login.php" class="font-medium text-blue-600 hover:text-blue-500">
                    войдите в существующий
                </a>
            </p>
        </div>
        
        <form class="mt-8 space-y-6" method="POST">
            <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
            
            <?php if ($error): ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">
                    <i class="fas fa-exclamation-triangle mr-2"></i>
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>
            
            <div class="space-y-4">
                <div>
                    <label for="login" class="block text-sm font-medium text-gray-700">Логин</label>
                    <input id="login" name="login" type="text" required 
                           class="mt-1 appearance-none relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm" 
                           placeholder="Введите логин" value="<?php echo htmlspecialchars($_POST['login'] ?? ''); ?>">
                    <p class="mt-1 text-xs text-gray-500">От 3 до 50 символов, только буквы, цифры и подчеркивания</p>
                </div>
                
                <div>
                    <label for="password" class="block text-sm font-medium text-gray-700">Пароль</label>
                    <input id="password" name="password" type="password" required 
                           class="mt-1 appearance-none relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm" 
                           placeholder="Введите пароль">
                    <p class="mt-1 text-xs text-gray-500">Минимум 6 символов</p>
                </div>
                
                <div>
                    <label for="password_confirm" class="block text-sm font-medium text-gray-700">Подтверждение пароля</label>
                    <input id="password_confirm" name="password_confirm" type="password" required 
                           class="mt-1 appearance-none relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm" 
                           placeholder="Подтвердите пароль">
                </div>
            </div>

            <div>
                <button type="submit" 
                        class="group relative w-full flex justify-center py-2 px-4 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                    <span class="absolute left-0 inset-y-0 flex items-center pl-3">
                        <i class="fas fa-user-plus text-blue-500 group-hover:text-blue-400"></i>
                    </span>
                    Зарегистрироваться
                </button>
            </div>
            
            <div class="text-center">
                <a href="index.php" class="text-sm text-gray-600 hover:text-blue-500">
                    <i class="fas fa-arrow-left mr-1"></i>Назад к блогу
                </a>
            </div>
        </form>
    </div>
</body>
</html>
