<?php
require_once 'src/config.php';
require_once 'src/db.php';

$db = getDB();

$login = 'admin';
$password = 'admin123';
$password_hash = password_hash($password, PASSWORD_DEFAULT);

echo "Генерируем хеш для пароля: $password\n";
echo "Хеш: $password_hash\n\n";

try {
    $stmt = $db->prepare("UPDATE users SET password_hash = ? WHERE login = ?");
    $result = $stmt->execute([$password_hash, $login]);
    
    if ($result) {
        echo "Пароль успешно обновлен для пользователя: $login\n";
        echo "Теперь можно войти с паролем: $password\n";
    } else {
        echo "Ошибка при обновлении пароля\n";
    }
} catch (Exception $e) {
    echo "Ошибка: " . $e->getMessage() . "\n";
}

$stmt = $db->prepare("SELECT login, password_hash FROM users WHERE login = ?");
$stmt->execute([$login]);
$user = $stmt->fetch();

if ($user) {
    echo "\nПроверяем пароль...\n";
    if (password_verify($password, $user['password_hash'])) {
        echo "✓ Пароль работает корректно!\n";
    } else {
        echo "✗ Пароль не работает\n";
    }
} else {
    echo "Пользователь не найден\n";
}
?>
