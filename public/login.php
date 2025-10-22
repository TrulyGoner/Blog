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
        
        $errors = validate_user_data(['login' => $login, 'password' => $password]);
        
        if (empty($errors)) {
            $result = $auth->login($login, $password);
            if ($result['success']) {
                flash_message($result['message'], 'success');
                redirect('index.php');
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
    <title>Вход - <?php echo SITE_NAME; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-gray-50 min-h-screen flex items-center justify-center">
    <div class="max-w-md w-full space-y-8">
        <div>
            <div class="mx-auto h-12 w-12 flex items-center justify-center rounded-full bg-blue-100">
                <i class="fas fa-sign-in-alt text-blue-600 text-xl"></i>
            </div>
            <h2 class="mt-6 text-center text-3xl font-extrabold text-gray-900">
                Вход в аккаунт
            </h2>
            <p class="mt-2 text-center text-sm text-gray-600">
                Или
                <a href="register.php" class="font-medium text-blue-600 hover:text-blue-500">
                    создайте новый аккаунт
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
            
            <div class="rounded-md shadow-sm -space-y-px">
                <div>
                    <label for="login" class="sr-only">Логин</label>
                    <input id="login" name="login" type="text" required 
                           class="appearance-none rounded-none relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 rounded-t-md focus:outline-none focus:ring-blue-500 focus:border-blue-500 focus:z-10 sm:text-sm" 
                           placeholder="Логин" value="<?php echo htmlspecialchars($_POST['login'] ?? ''); ?>">
                </div>
                <div>
                    <label for="password" class="sr-only">Пароль</label>
                    <input id="password" name="password" type="password" required 
                           class="appearance-none rounded-none relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 rounded-b-md focus:outline-none focus:ring-blue-500 focus:border-blue-500 focus:z-10 sm:text-sm" 
                           placeholder="Пароль">
                </div>
            </div>

            <div>
                <button type="submit" 
                        class="group relative w-full flex justify-center py-2 px-4 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                    <span class="absolute left-0 inset-y-0 flex items-center pl-3">
                        <i class="fas fa-lock text-blue-500 group-hover:text-blue-400"></i>
                    </span>
                    Войти
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
