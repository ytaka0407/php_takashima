<?php
session_start();
require('dbconnect.php');

if (isset($_SESSION['id']) && $_SESSION['time'] + 3600 > time()) {
  $_SESSION['time'] = time();

  $members = $db->prepare('SELECT * FROM members WHERE id=?');
  $members->execute(array($_SESSION['id']));
  $member = $members->fetch();
} else {
  header('Location:login.php');
  exit;
}

if (!empty($_POST)) {
  if ($_POST['message'] != '') {
    $posts = $db->prepare('INSERT INTO posts SET message=?, member_id=?, reply_post_id=?,created=NOW()');
    $posts->execute(array($_POST['message'], $member['id'], $_POST['reply_post_id']));
    header('Location:index.php');
    exit;
  }
}

//返信の場合
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
$page = $_REQUEST['page'];

if ($page == '') {
  $page = 1;
} else {
  $page = max($page, 1);
}

//最終ページを取得する
$counts = $db->query('SELECT COUNT(*) AS cnt FROM posts');
$count = $counts->fetch();
$maxpage = ceil($count['cnt'] / 5);
$page = min($page, $maxpage);
$start = ($page - 1) * 5;


$posts = $db->prepare('SELECT m.name,m.picture,p.* FROM posts p, members m WHERE p.member_id=m.id ORDER BY p.created DESC LIMIT ?,5');
$posts->bindParam(1, $start, PDO::PARAM_INT);
$posts->execute();

?>
<!DOCTYPE html>
<html lang="ja">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta http-equiv="X-UA-Compatible" content="ie=edge">
  <title>ひとこと掲示板</title>
  <link rel="stylesheet" href="style.css" />
  <link href="https://use.fontawesome.com/releases/v5.6.1/css/all.css" rel="stylesheet">
</head>

<body>
  <div id="wrap">
    <div id="head">
      <h1>ひとこと掲示板</h1>
    </div>
    <div id="content">
      <div style="text-align:right"><a href="logout.php">ログアウト</a></div>
      <form action="" method="post">
        <dl>
          <dt><img src="member_picture/<?php echo h($member['picture']); ?>" height="48" width="48"><?php echo h($member['name']); ?>さん メッセージをどうぞ</dt>
          <dd><textarea name="message" cols="50" rows="5"><?php echo h($reply_message); ?></textarea>
            <input type="hidden" name="reply_post_id" value="<?php h($_REQUEST['res']); ?>">
          </dd>
        </dl>
        <input type="submit" value="投稿する">
      </form>
      <?php
      foreach ($posts as $post) : ?>
        <div class="msg">
          <img src="member_picture/<?php echo h($post['picture']); ?>" height="48" width="48">
          <p class="msgtext"><?php echo makelink(h($post['message'])); ?><span class="name">(<?php echo h($post['name']); ?>)</span>[<a href="index.php?res=<?php echo h($post['id']); ?>">Re:</a>]
      <!--いいねボタン部分-->
            <form action="" method="post">
              <button class="heart" type="submit" name="like" value="change">
                <i class="fas fa-heart icon-font" style="color:#f1071a"></i>
              </button>
            </form>
          </p>
          <p class="day"><a href="view.php?id=<?php echo h($post['id']); ?>"><?php echo h($post['created']); ?></a>
            <?php if ($post['reply_post_id'] > 0) : ?>
              <a href="view.php?id=<?php echo h($post['reply_post_id']); ?>">返信元のメッセージ</a>
            <?php endif; ?>
            <?php if ($_SESSION['id'] == $post['member_id']) : ?>
              [<a href="delete.php?id=<?php echo h($post['id']); ?>" style="color:F33;">削除</a>]
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
