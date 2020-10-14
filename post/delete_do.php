<?php
session_start();
require('dbconnect.php');

//ログイン検査
if (isset($_SESSION['id'])) {
    $id = $_REQUEST['id'];

    //投稿検査
    $messages = $db->prepare('SELECT * FROM posts WHERE id=?');
    $messages->execute(array($id));
    $message = $messages->fetch();
    //投稿者IDとログインIDの一致
    if ($message['member_id'] == $_SESSION['id']) {
        $del = $db->prepare('UPDATE posts SET switch=0 WHERE id=?');
        $del->execute(array($id));
    }
} else {
    header('Location:login.php');
    exit;
}

?>
<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>ひとこと掲示板</title>

    <link rel="stylesheet" href="style.css" />
</head>

<body>
    <div id="wrap">
        <div id="head">
            <h1>ひとこと掲示板</h1>
        </div>
        <div id="content">
            <p>投稿を削除しました。</p>
            <a href="index.php">一覧へ戻る</a>

        </div>
    </div>
</body>

</html>
