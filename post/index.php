<?php
session_start();
require('dbconnect.php');

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

//返信の場合、投稿画面に返信元メッセージを表示する
if (isset($_REQUEST['res'])) {
    $response = $db->prepare('SELECT m.name,m.picture,p.* FROM posts p, members m WHERE p.member_id=m.id AND p.id=? ORDER BY p.created DESC');
    $response->execute(array($_REQUEST['res']));
    $table = $response->fetch();
    $reply_message = '@' . $table['name'] . '' . $table['message'];
}

//htmlspecialcharsショートカット
function h($value)
{
    return htmlspecialchars($value, ENT_QUOTES);
}

//本文内URLにリンクを設定
function makelink($value)
{
    return mb_ereg_replace("(https?)(://[[:alnum:]\+\$\;\?\.%,!#~*/:@&=_-]+)", '<a href="\1\2">\1\2</a>', $value);
}

//投稿を取得する
$page = ($_REQUEST['page'] ?? 1);

if (!$page === 1) {
    $page = max($page, 1);
}

//最終ページを取得する
$counts = $db->query('SELECT COUNT(*) AS cnt FROM posts');
$count = $counts->fetch();
$maxpage = ceil($count['cnt'] / 5);
$page = min($page, $maxpage);
$start = ($page - 1) * 5;


//リツイート元情報も含めて取得
//p1・m1は投稿情報
//p2・m2はリツイート元の投稿及び投稿者の情報
//p1を軸に外部結合・表示する投稿に付随するメンバー情報・リツイート元の投稿情報・リツイート元の投稿者情報を取得
$posts = $db->prepare(
    'SELECT p1.id,p1.message,p1.member_id,p1.reply_post_id,p1.retweet_post_id,p1.switch,p1.created,
  m1.name,m1.picture,
  p2.id as ori_id,p2.member_id as ori_member_id,p2.created as ori_created,
  m2.name as ori_name,m2.picture as ori_picture
  FROM posts as p1
  LEFT JOIN members as m1 ON p1.member_id=m1.id
  LEFT JOIN posts as p2 ON p1.retweet_post_id=p2.id
  LEFT JOIN members as m2 ON p2.member_id=m2.id
  WHERE p1.switch=TRUE
  ORDER BY p1.created DESC LIMIT ?,5;'
);
$posts->bindParam(1, $start, PDO::PARAM_INT);
$posts->execute();
$posts = $posts->fetchall();

//いいね回数カウント
$likecounts = $db->query('SELECT message_id, COUNT(*) as count FROM likeactions WHERE switch=TRUE GROUP BY message_id');
$likecounts = $likecounts->fetchall(PDO::FETCH_ASSOC | PDO::FETCH_GROUP);

//ユーザーのいいね履歴取得
$likes = $db->prepare('SELECT message_id FROM likeactions WHERE member_id=? AND switch=TRUE');
$likes->bindParam(1, $member['id'], PDO::PARAM_INT);
$likes->execute();
$like = $likes->fetchall(PDO::FETCH_ASSOC | PDO::FETCH_GROUP);

//IDごとのリツイート回数集計取得
$countquery = $db->prepare(
    'SELECT retweet_post_id as id, count(*) as retweetcount FROM posts WHERE switch=TRUE GROUP BY retweet_post_id'
);
$countquery->execute();
$retweetcount = $countquery->fetchall(PDO::FETCH_ASSOC | PDO::FETCH_GROUP);
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
    <link href="https://use.fontawesome.com/releases/v5.6.1/css/all.css" rel="stylesheet">
</head>

<body>
    <div id="wrap">
        <div id="head">
            <h1>ひとこと掲示板</h1>
        </div>
        <div id="content">
            <div style="text-align:right"><a href="logout.php">ログアウト</a></div>
            <form class="postform" action="post.php" method="post">
                <dl>
                    <dt><img src="member_picture/<?php echo h($member['picture']); ?>" height="48" width="48" alt="icon"><?php echo h($member['name']); ?>さん メッセージをどうぞ</dt>
                    <dd><textarea name="message" cols="50" rows="5"><?php echo h(($reply_message ?? '')); ?></textarea>
                        <input type="hidden" name="reply_post_id" value=<?php echo (h(($_REQUEST['res'] ?? ''))); ?>>
                    </dd>
                </dl>
                <input type="submit" value="投稿する">
            </form>
            <?php
            foreach ($posts as $post) : ?>
                <div class="msg">
                    <!--リツイートの場合の表示-->
                    <?php if ($post['retweet_post_id']) : ?>
                        <p>
                            <img src="member_picture/<?php echo h($post['picture']); ?>" height="48" width="48" alt="icon"><?php echo h($post['name']); ?>さんがリツイートしました。
                        </p>
                        <div class="retweettext">
                            <p>
                                <img src="member_picture/<?php echo h($post['ori_picture']); ?>" height="48" width="48" alt="icon"><?php echo h($post['ori_name']); ?>さんのツイート
                            </p>
                            <?php echo makelink(h($post['message'])); ?>
                            <div>
                                <form style="display:inline" class="likeform" action="likeaction.php" method="post">
                                    <button class="heart" type="submit" name="like" value="change" style="outline:none">
                                        <i class="fas fa-heart icon-font" <?php if (isset($like[$post['retweet_post_id']])) : ?>style="color:#f1071a" <?php endif; ?>></i>
                                    </button>
                                    <input type="hidden" name="msgid" value="<?php echo h($post['retweet_post_id']); ?>">
                                    <input type="hidden" name="id" value="<?php echo h($member['id']); ?>">
                                </form>
                                <?php $likecount = isset($likecounts[$post['retweet_post_id']][0]['count']) ? ($likecounts[$post['retweet_post_id']][0]['count']) : 0;
                                echo ($likecount); ?>
                                <!--リツイートボタン-->
                                <form class="retweet" action="retweet_post.php" method="post">
                                    <input type="hidden" name="id" value="<?php echo h($member['id']); ?>">
                                    <input type="hidden" name="rt_post_id" value="<?php echo h($post['retweet_post_id']); ?>">
                                    <input type="hidden" name="message" value="<?php echo h($post['message']) ?>">
                                    <input class="retweet_button" type="submit" value="retweet">
                                </form>
                                <!--元のツイートのリツイート回数表示。集計データが存在しない場合は０-->
                                <?php echo (h($retweetcount[$post['retweet_post_id']][0]['retweetcount'] ?? 0)); ?>
                            </div>
                            <p class="day"><a href="view.php?id=<?php echo h($post['retweet_post_id']); ?>"><?php echo h($post['ori_created']); ?></a></p>
                        </div>
                        <!--リツイートではない場合の表示-->
                    <?php else : ?>
                        <p>
                            <img src="member_picture/<?php echo h($post['picture']); ?>" height="48" width="48" alt="icon">
                        </p>
                        <p class="msgtext"><?php echo makelink(h($post['message'])); ?><span class="name">(<?php echo h($post['name']); ?>)</span>

                            <!--いいねボタン部分-->
                            <!--いいねボタン-->
                            <form class="likeform" action="likeaction.php" method="post">
                                <button class="heart" type="submit" name="like" value="change" style="outline:none">
                                    <i class="fas fa-heart icon-font" <?php if (isset($like[$post['id']])) : ?>style="color:#f1071a" <?php endif; ?>></i>
                                </button>
                                <input type="hidden" name="msgid" value="<?php echo h($post['id']); ?>">
                                <input type="hidden" name="id" value="<?php echo h($member['id']); ?>">
                            </form>
                            <!--いいねカウント-->
                            <?php $likecount = isset($likecounts[$post['id']][0]['count']) ? ($likecounts[$post['id']][0]['count']) : 0;
                            echo ($likecount);
                            ?>
                            <!--リツイート-->
                            <form class="retweet" action="retweet_post.php" method="post">
                                <input type="hidden" name="id" value="<?php echo h($member['id']); ?>">
                                <input type="hidden" name="rt_post_id" value="<?php echo h($post['id']); ?>">
                                <input type="hidden" name="message" value="<?php echo h($post['message']) ?>">
                                <input class="retweet_button" type="submit" value="retweet">
                            </form>
                            <!--リツイート回数表示-->
                            <?php echo (h($retweetcount[$post['id']][0]['retweetcount'] ?? 0)); ?>
                            [<a href="index.php?res=<?php echo h($post['id']); ?>">Re:</a>]
                        </p>
                    <?php endif; ?>
                    <p class="day"><a href="view.php?id=<?php echo h($post['id']); ?>&rtid=<?php echo h(($post['retweet_post_id'] ?? '')); ?>"><?php echo h($post['created']); ?></a>
                        <?php if (($post['reply_post_id'] ?? 0) > 0) : ?>
                            <a href="view.php?id=<?php echo h($post['reply_post_id']); ?>">返信元のメッセージ</a>
                        <?php endif; ?>
                        <?php if ($_SESSION['id'] === $post['member_id']) : ?>
                            <?php if (($post['retweet_post_id'] ?? '')) : ?>
                                [<a href="delete_retweet.php?id=<?php echo h($post['id']); ?>">リツイート取消</a>]
                            <?php else : ?>
                                [<a href="delete.php?id=<?php echo h($post['id']); ?>">削除</a>]
                            <?php endif; ?>
                        <?php endif; ?>
                    </p>
                </div>
            <?php endforeach; ?>
            <ul class="paging">
                <?php if ($page > 1) : ?>
                    <li><a href="index.php?page=<?php echo ($page - 1); ?>">前のページへ</a></li>
                <?php else : ?>
                    <li>前のページへ</li>
                <?php endif; ?>
                <li>
                    (<?php echo ($page); ?>/<?php echo ($maxpage); ?>)<?php if ($page < $maxpage) : ?>
                </li>
                <li><a href="index.php?page=<?php echo ($page + 1); ?>">次のページへ</a></li>
            <?php else : ?>
                <li>次のページへ</li>
            <?php endif; ?>
            </ul>
        </div>
    </div>
</body>

</html>
