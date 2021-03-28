<?php
session_start();
require('dbconnect.php');

//htmlspecialcharsショートカット
function h($value)
{
    return htmlspecialchars($value, ENT_QUOTES);
}

//ログインしているのかチェック
if (isset($_SESSION['id']) && $_SESSION['time'] + 3600 > time()) {
    $_SESSION['time'] = time();
    $members = $db->prepare('SELECT * FROM members WHERE id=?');
    $members->execute(array($_SESSION['id']));
    $member = $members->fetch();
} else {
    header('Location:login.php');
    exit;
}

//$_REQUEST['id']はリツイートの元の投稿。数字のみ
if (is_numeric($_REQUEST['id'])) {
    $id = $_REQUEST['id'];

    //ユーザーのリツイート履歴取得。
    $retweetposts = $db->prepare('SELECT retweet_post_id,id as postid FROM posts WHERE member_id=? AND switch=1 AND retweet_post_id<>0');
    $retweetposts->bindParam(1, $member['id'], PDO::PARAM_INT);
    $retweetposts->execute();
    $retweetlist = [];
    foreach ($retweetposts as $retweetitem) {
        array_push($retweetlist, $retweetitem['retweet_post_id']);
    };

    //取消対象の投稿idをリツイート対象としている投稿が、ユーザーのリツイート履歴に存在するかチェック
    //該当のない場合はリツイート取消する権限のある履歴がないので一覧画面へ
    if (!in_array($id, $retweetlist)) {
        header('Location:index.php');
        exit;
    }

    //該当する履歴が合った場合、再度ユーザーのリツイート履歴を取得し、最終的に取り消したいリツイートidを$deleteidとして確定させる
    $retweetposts->bindParam(1, $member['id'], PDO::PARAM_INT);
    $retweetposts->execute();
    $retweetpost = $retweetposts->fetchall(PDO::FETCH_ASSOC | PDO::FETCH_GROUP);
    $deleteid = $retweetpost[$id][0]['postid'];

    //詳細な投稿内容を取得
    $messages = $db->prepare('SELECT p1.id,p1.member_id,p1.message,p1.retweet_post_id,p2.member_id as ori_memberid,m.name as ori_membername,m.picture as ori_memberpicture
    FROM posts as p1
    LEFT JOIN posts as p2 ON p1.retweet_post_id=p2.id
    LEFT JOIN members as m ON p2.member_id=m.id
    WHERE p1.id=? AND p1.switch=TRUE');
    $messages->execute(array($deleteid));
    $message = $messages->fetch();
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
                <input type="hidden" name="id" value="<?php echo htmlspecialchars($deleteid, ENT_QUOTES); ?>"><br>
                <input type="submit" name="delete" value="取り消す">
            </form><br>
            <p><a href="index.php">戻る</a></p>

        </div>
    </div>
</body>

</html>
