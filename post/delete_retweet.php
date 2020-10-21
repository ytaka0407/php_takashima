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

//セッションが有効かつ$_REQUEST['id']は数字のみ
if (is_numeric($_REQUEST['id'])) {
    $id = $_REQUEST['id'];
    //投稿検査
    $messages = $db->prepare('SELECT p1.id,p1.member_id,p1.message,p1.retweet_post_id,p2.member_id as ori_memberid,m.name as ori_membername,m.picture as ori_memberpicture
    FROM posts as p1
    LEFT JOIN posts as p2 ON p1.retweet_post_id=p2.id
    LEFT JOIN members as m ON p2.member_id=m.id
    WHERE p1.id=? AND p1.switch=TRUE');
    $messages->execute(array($id));
    $message = $messages->fetch();
    //投稿者IDとログインIDの一致
    if ($message['member_id'] !== $_SESSION['id']) {
        header('Location:login.php');
        exit;
    }
}

//取得した結果のori_post_idが存在しない場合(URLパラメータの$_REQUEST['id']リツイート投稿ではない場合)
//セルフリツイートか判定
if (!$message['retweet_post_id']) {
    //ユーザーのリツイート履歴取得。取消対象の投稿idをリツイートしたリツイート投稿idを取得(セルフリツイートならこのidが取り消す投稿id)
    $retweetposts = $db->prepare('SELECT retweet_post_id,id as postid FROM posts WHERE member_id=? AND switch=1 AND retweet_post_id<>0');
    $retweetposts->bindParam(1, $message['member_id'], PDO::PARAM_INT);
    $retweetposts->execute();
    $retweetlist = $retweetposts->fetchall(PDO::FETCH_ASSOC | PDO::FETCH_GROUP);
    $id = ($retweetlist[$message['id']][0]['postid'] ?? '');
    //ユーザーのリツイート履歴にidが該当なかった場合、一覧へ戻る。
    if ($id === '') {
        header('Location:index.php');
        exit;
        //該当があった場合（セルフリツイートだった場合）元の投稿情報にユーザー情報をそのままコピー
    } else {
        $message['ori_memberid'] = $member['id'];
        $message['ori_membername'] = $member['name'];
        $message['ori_memberpicture'] = $member['picture'];
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
