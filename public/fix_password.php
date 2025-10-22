<?php
require_once '../src/config.php';
require_once '../src/db.php';

$db = getDB();

$login = 'admin';
$password = 'admin123';
$password_hash = password_hash($password, PASSWORD_DEFAULT);

echo "<h2>Исправление пароля тестового пользователя</h2>";
echo "<p>Генерируем хеш для пароля: <strong>$password</strong></p>";
echo "<p>Хеш: <code>$password_hash</code></p>";

try {
    $stmt = $db->prepare("UPDATE users SET password_hash = ? WHERE login = ?");
    $result = $stmt->execute([$password_hash, $login]);
    
    if ($result) {
        echo "<p style='color: green;'>✓ Пароль успешно обновлен для пользователя: <strong>$login</strong></p>";
        echo "<p style='color: green;'>Теперь можно войти с паролем: <strong>$password</strong></p>";
    } else {
        echo "<p style='color: red;'>✗ Ошибка при обновлении пароля</p>";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>Ошибка: " . $e->getMessage() . "</p>";
}

$stmt = $db->prepare("SELECT login, password_hash FROM users WHERE login = ?");
$stmt->execute([$login]);
$user = $stmt->fetch();

if ($user) {
    echo "<h3>Проверка пароля:</h3>";
    if (password_verify($password, $user['password_hash'])) {
        echo "<p style='color: green;'>✓ Пароль работает корректно!</p>";
    } else {
        echo "<p style='color: red;'>✗ Пароль не работает</p>";
    }
} else {
    echo "<p style='color: red;'>Пользователь не найден</p>";
}

echo "<p><a href='login.php'>Перейти к странице входа</a></p>";
?>
