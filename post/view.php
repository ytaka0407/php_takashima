<?php session_start();
require('dbconnect.php');

if (empty($_REQUEST['id'])) {
    header('Location:index.php');
    exit;
}

$posts = $db->prepare('SELECT m.name,m.picture,p.* FROM members m,posts p WHERE p.id=? AND m.id=p.member_id');
$posts->execute(array($_REQUEST['id']));

//htmlspecialcharsショートカット
function h($value){
  return htmlspecialchars($value,ENT_QUOTES);
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
        <p><a href="index.php">一覧に戻る</a></p>
            <?php if ($post=$posts->fetch()):?>
                <div class="msg">
                    <img src="member_picture/<?php echo h($post['picture']); ?>" width="48" height="48" alt="<?php echo h($post['name']); ?>">
                    <p><?php echo h($post['message']); ?><span class="name">(<?php echo h($post['name']); ?>)</span></p>
                    <p><span class="day"><?php echo h($post['created']); ?></span></p>
                    <?php if ($_SESSION['id'] == $post['member_id']) : ?>
              [<a href="delete.php?id=<?php echo h($post['id']); ?>" style="color:F33;">削除</a>]
            <?php endif; ?>
                </div>
            <?php else : ?>
                <p>その投稿は削除されたか、URLが間違えています。</p>
            <?php endif; ?>
        </div>
    </div>
</body>

</html>
