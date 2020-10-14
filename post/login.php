<?php
session_start();
require('dbconnect.php');

if (($_COOKIE['email'] ?? '') != '') {
  $_POST['email'] = $_COOKIE['email'];
  $_POST['password'] = $_COOKIE['password'];
  $_POST['save'] = 'on';
}

//入力内容チェック

if (!empty($_POST)) {
  if ($_POST['email'] != '' && $_POST['password'] != '') {

    $login = $db->prepare('SELECT * FROM members WHERE email=? AND password=?');
    $login->execute(array($_POST['email'], sha1($_POST['password'])));
    $member = $login->fetch();

    if ($member) {
      if (($_POST['save'] ?? '') == 'on') {
        setcookie('email', $_POST['email'], time() + 60 * 60 * 24 * 14);
        setcookie('password', $_POST['password'], time() + 60 * 60 * 24 * 14);
      }
      $_SESSION['id'] = $member['id'];
      $_SESSION['time'] = time();
      header('Location:index.php');
      exit;
    } else {
      $error['login'] = 'failed';
    }
  } else {
    $error['login'] = 'blank';
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

  <link rel="stylesheet" href="style.css" />
</head>

<body>
  <div id="wrap">
    <div id="head">
      <h1>ログインする</h1>
    </div>
    <div id="content">
      <div id="lead"></div>
      <p>メールアドレスとパスワードを記入してログインしてください。</p>
      <p>入会手続きがまだの方はこちらからどうぞ</p>
      <p>&raquo;<a href="join/index.php">入会手続きをする</a></p>
      <form action="login.php" method="post" enctype="multipart/form-data">
        <dl>
          <dt>メールアドレス</dt>
          <dd><input type="text" name="email" size="35" maxlength="255" value="<?php echo htmlspecialchars(($_POST['email'] ?? ''), ENT_QUOTES); ?>"></dd>
          <dt>パスワード</dt>
          <dd><input type="password" name="password" size="10" maxlength="20" value="<?php echo htmlspecialchars(($_POST['password'] ?? ''), ENT_QUOTES); ?>"></dd>
          <?php if (($error['login'] ?? '') == 'blank') : ?><p class="error">*メールアドレスとパスワードを入力して下さい。
            </p><?php elseif (($error['login'] ?? '') == 'failed') : ?><p class="error">ログインに失敗しました。入力内容をご確認下さい。</p><?php endif; ?>
          <dt>ログイン状態の記録</dt>
          <dd><input type="checkbox" name="save" value="on"><label for="save">次回からは自動的にログインする</label></dd>
        </dl>
        <div><input type="submit" value="ログインする"></div>
      </form>
    </div>

  </div>
</body>

</html>
