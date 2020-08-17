<?php
// Logout function
if (isset($_REQUEST['logout'])) {
    $_SESSION = array();
    unset($_COOKIE[session_name()]);
    session_destroy();
    header('Location: singin.php?left');
}

// проверяем что сессия пренадлежит пользователю
$loginPageAddress = substr($_SERVER['SCRIPT_NAME'], -10, 10);            // читаем последние 10 символов URL (singin.php)
if ($loginPageAddress !== 'singin.php' and $loginPageAddress !== 'singup.php') {  // проверяем что мы не на странице авторизации или регистрации
    if (empty($_SESSION['user']['id'])) header('Location: singin.php?empty_session');     // Поверка наличия сессии
    try {                                                                           // ищем совпадение данных сессии и БД
        $stmt = $pdo->prepare(SQL_LOGIN);
        $stmt->bindParam(':login', $_SESSION['user']['login']);
        $result = $stmt->execute();
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        echo '=== SESSION EXCEPTION ===  ' . $e->getMessage();
    }
    if ($user) {                                                                    // если пользователь не найден
        if ($_SESSION['user']['password'] === $user['password']) {                  // если пароли не сопадают, то ...
            // всё ок
        } else header('Location: singin.php?log-err');                        // отправляем на авторизацию
    }
}
