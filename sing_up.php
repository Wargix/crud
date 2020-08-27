<?php
session_start();
// перенаправление на страницу профиля при активной сессии
if (isset($_SESSION['user']['login'])) {
    header('Location: user.php?user=' . $_SESSION['user']['login']);
}

require_once 'includes/db.php';
require_once 'includes/validate.php';

if (isset($_POST['add-user'])) {     // проверка нажатия кнопки РЕГИСТРАЦИЯ

    // переносим данные формы в массив
    $form_data = [
        'full_name' => $_POST['full_name'],
        'login' => $_POST['login'],
        'email' => $_POST['email'],
        'password' => $_POST['password']
    ];

// Validation
    // Проверка совпадения введённых паролей
    if ($form_data['password'] !== $_POST['password_confirm']) exit('Введённые пароли не совпали.');

    // пропускаем массив через функцию очистки
    $form_data = clean($form_data); // clean() locate in validate.php

    // проверка на пустые значения
    if(empty($form_data['full_name']) OR empty($form_data['login']) OR empty($form_data['email']) OR empty($form_data['password'])) {
        exit('Заполните все значения.');
    }

    // валидация эл. почты
    $email_validate = filter_var($form_data['email'], FILTER_VALIDATE_EMAIL);


    // проверка длинны данных
    if (!check_length($form_data['full_name'], 2, 255)) {
        exit('Name long must be between 2 and 255 characters.');
    }
    if (!check_length($form_data['login'], 2, 64)) {
        exit('Login long must be between 2 and 64 characters.');
    }
    if (!check_length($form_data['password'], 2, 64)) {
        exit('Password long must be between 2 and 255 characters.');
    }
    if (!$email_validate) {
        exit('Enter correct e-mail.');
    }
// Validation end

    // Finding matches in DB
    try {
        // Preparation
        $stmt = $pdo->prepare(SQL_LOGIN);                         // prepare — Подготавливает SQL-запрос к выполнению
        $stmt->bindParam(':login', $form_data['login']); // bindParam — Привязывает значение переменной к параметру SQL-запроса
        $result = $stmt->execute();                                       // execute — выполняет подготовленный запрос и возвращает результат
        $user_count = $stmt->rowCount();

        // Check
        if ($user_count > 0 ) {                                            // если логин не найден
            exit('Пользователь с таким логином уже существует!');          // завершаем работу скрипта
        }


        // солёное хеширование пароля
        try {
            $form_data['password'] = password_hash($form_data['password'], PASSWORD_DEFAULT);
        } catch (Exception $e) {
            echo 'HASH ERROR: ' . $e->getMessage();
        }

        // генерация проверочного кода
        $verify_code = md5(random_bytes(20));
        $form_data['verify_code'] = $verify_code;


        // Добавить данные в БД
        $stmt = $pdo->prepare(SQL_INSERT_USER); // подготавливаем запрос с данными
        $stmt->execute(array_values($form_data));        // и отправляем его на выполнение MySQL серверу


        // открыть сессию
        $_SESSION['user'] = $form_data;                  // ... и сохраняем данные пользователя в сессию


        // отправка письма подтверждения
        try {
            $verify_url = $_SERVER['HTTP_HOST'] . '/email_verify.php?' . $verify_code;
            $to = $form_data['email'];
            $subject = 'Подтверждение регистрации';
            $message = 'Для активации вашего аккаунта нажмите на ссылку — ' . $verify_url;
            $headers = 'From: webmaster@goodman.com' . "\r\n" . 'Reply-To: webmaster@example.com';
            $mail_result = mail($to, $subject, $message, $headers);
        } catch (Exception $e) {
            echo 'EMAIL VERIFICATION ERROR:';
        }

        // проверка отправки письма
        if ($mail_result) {
            // перенаправить на список пользователей
            header('Location: ./user.php?user=' . $_SESSION['user']['login']);
        } else echo 'Ошибка отправки письма с кодом подтверждения.';


    } catch (PDOException $e) {
        echo 'PDO ERROR: ' . $e->getMessage();
    } catch (Exception $e) {
        echo 'OTHER EXCEPTION: ' . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Новый пользователь</title>
    <?php //include_once 'includes/statistics.html' ?>
    <?php include_once 'includes/menu.html' ?>

<div class="mdl-grid">
    <div class="mdl-cell mdl-cell--12-col">
        <h1>Новый пользователь</h1>
    </div>
</div>

<div class="mdl-grid">
    <div class="mdl-cell mdl-cell--12-col">
        <button class="mdl-button mdl-js-button mdl-button--raised mdl-button--colored" type="submit" form="add_user" name="add-user">Зарегистрировать</button>
        <button class="mdl-button mdl-js-button mdl-button--raised" type="reset" form="add_user">Очистить</button>
<!--        <button class="mdl-button mdl-js-button mdl-button--raised" type="submit" form="add_user" name="abort">Отмена</button>-->
        <a class="mdl-button mdl-js-button mdl-button--raised" href="index.php">Отмена</a>
    </div>
</div>
<article class="mdl-grid main-content">
    <div class="mdl-cell mdl-cell--12-col">

        <form action="<?= htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="post" id="add_user" enctype="multipart/form-data">
            <div class="mdl-textfield mdl-js-textfield mdl-textfield--floating-label">
                <input class="mdl-textfield__input" type="text" id="full_name" name="full_name" required>
                <label class="mdl-textfield__label" for="full_name">Имя</label>
            </div>
            <br>
            <div class="mdl-textfield mdl-js-textfield mdl-textfield--floating-label">
                <input class="mdl-textfield__input" type="text" id="login" name="login" required>
                <label class="mdl-textfield__label" for="login">Логин</label>
            </div>
            <br>
            <div class="mdl-textfield mdl-js-textfield mdl-textfield--floating-label">
                <input class="mdl-textfield__input" type="email" id="email" name="email" required>
                <label class="mdl-textfield__label" for="email">Почта</label>
            </div>
            <br>
            <div class="mdl-textfield mdl-js-textfield mdl-textfield--floating-label">
                <input class="mdl-textfield__input" type="password" id="password" name="password" required>
                <label class="mdl-textfield__label" for="password">Пароль</label>
            </div>
            <br>
            <div class="mdl-textfield mdl-js-textfield mdl-textfield--floating-label">
                <input class="mdl-textfield__input" type="password" id="password_confirm" name="password_confirm" required>
                <label class="mdl-textfield__label" for="password_confirm">Подтверждение пароля</label>
            </div>
            <br>
        </form>
    </div>
</article>
<?php include_once 'includes/footer.html' ?>