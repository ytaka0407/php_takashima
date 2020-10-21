<?php
session_start();
require('dbconnect.php');

//htmlspecialcharsショートカット
function h($value)
{
    return htmlspecialchars($value, ENT_QUOTES);
}

//ログイン検査
//セッションが有効かつ$_REQUEST['id']は数字のみ
if ((isset($_SESSION['id'])) && (is_numeric($_REQUEST['id']))) {
    $id = $_REQUEST['id'];
    //投稿検査
    $messages = $db->prepare('SELECT p1.id,p1.member_id,p1.message,p1.retweet_post_id,p2.member_id as ori_memberid,m.name as ori_membername,m.picture as ori_memberpicture
    FROM posts as p1
    LEFT JOIN posts as p2 ON p1.retweet_post_id=p2.id
    LEFT JOIN members as m ON p2.member_id=m.id
    WHERE p1.id=?');
    $messages->execute(array($id));
    $message = $messages->fetch();
    //投稿者IDとログインIDの一致
    if ($message['member_id'] !== $_SESSION['id']) {
        header('Location:login.php');
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>ひとこと掲示板</title>

    <link rel="stylesheet" href="stylesheets/style.css" />
    <link rel="stylesheet" href="stylesheets/styleadd.css" />
</head>

<body>
    <div id="wrap">
        <div id="head">
            <h1>ひとこと掲示板</h1>
        </div>
        <div id="content">
            <p>リツイートを取消します。</p>
            <p>元のツイート</p>
            <div class="msg">
                <p><img src="member_picture/<?php echo (h($message['ori_memberpicture'])); ?>" height="48" width="48" alt="icon">
                    <?php echo (h($message['ori_membername'])); ?>さん
                </p>
                <?php echo htmlspecialchars($message['message'], ENT_QUOTES); ?>
            </div>

            <form action="delete_do.php" method="post">
                <input type="hidden" name="id" value="<?php echo htmlspecialchars($id, ENT_QUOTES); ?>"><br>
                <input type="submit" name="delete" value="取り消す">
            </form><br>
            <p><a href="index.php">戻る</a></p>

        </div>
    </div>
</body>

</html>
