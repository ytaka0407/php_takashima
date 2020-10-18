<?php session_start();
require('dbconnect.php');

//idは必須・数字限定
if (empty($_REQUEST['id']) || (!is_numeric($_REQUEST['id']))) {
    header('Location:index.php');
    exit;
}

//rtidは空欄のままもしくは数字限定
if (!empty($_REQUEST['rtid']) && (!is_numeric($_REQUEST['rtid']))) {
    header('Location:index.php');
    exit;
}

//投稿の情報を取得
$posts = $db->prepare('SELECT m.name,m.picture,p.id,p.message,p.created,p.member_id FROM members m,posts p WHERE p.id=? AND m.id=p.member_id');
$posts->execute(array($_REQUEST['id']));

//リツイート元の投稿の情報を取得
if (($_REQUEST['rtid'] ?? '')) {
    $ori_posts = $db->prepare('SELECT m.name,m.picture,p.id,p.message,p.created,p.member_id FROM members m,posts p WHERE p.id=? AND m.id=p.member_id');
    $ori_posts->execute(array($_REQUEST['rtid']));
    $ori_post = ($ori_posts->fetch() ?? '');
}

//htmlspecialcharsショートカット
function h($value)
{
    return htmlspecialchars($value, ENT_QUOTES);
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
            <p><a href="index.php">一覧に戻る</a></p>
            <?php if ($post = $posts->fetch()) : ?>
                <div class="msg">
                    <img src="member_picture/<?php echo h($post['picture']); ?>" width="48" height="48" alt="<?php echo h($post['name']); ?>"><?php echo h($post['name']); ?>さん
                    <?php if ($ori_post ?? FALSE) : ?>がリツイート
                    <div class="retweettext">
                        <p><img src="member_picture/<?php echo h($ori_post['picture']); ?>" width="48" height="48" alt="<?php echo h($ori_post['name']); ?>"><?php echo h($ori_post['name']); ?>さん</p>
                        <p><?php echo h($ori_post['message']); ?></p>
                        <p><span class="day"><?php echo h($ori_post['created']); ?></span></p>
                    </div>
                <?php else : ?>
                    <p><?php echo h($post['message']); ?><span class="name">(<?php echo h($post['name']); ?>)</span></p>
                    <p><span class="day"><?php echo h($post['created']); ?></span></p>
                <?php endif; ?>
                <!--もし投稿メンバー=ログインメンバーの場合、削除かリツイート取り消しか分岐して表示-->
                <p  class="day">
                <?php if ($_SESSION['id'] === $post['member_id']) : ?>
                    <?php if (($ori_post ?? FALSE)) : ?>
                        [<a href="delete_retweet.php?id=<?php echo h($post['id']); ?>">リツイート取消</a>]
                    <?php else : ?>
                        [<a href="delete.php?id=<?php echo h($post['id']); ?>">削除</a>]
                    <?php endif; ?>
                <?php endif; ?>
                </p>
                </div>
            <?php else : ?>
                <p>その投稿は削除されたか、URLが間違えています。</p>
            <?php endif; ?>
        </div>
    </div>
</body>

</html>
