<?php session_start();
require('../dbconnect.php');

if (!empty($_POST)) {
  //エラー確認
  //名前が入っているか確認
  if ($_POST['name'] == '') {
    $error['name'] = 'blank';
  }
  //メールアドレスが入っているか確認
  if ($_POST['email'] == '') {
    $error['email'] = 'blank';
  }
  //パスワードが入っているか確認
  if ($_POST['password'] == '') {
    $error['password'] = 'blank';
    //パスワードが4文字以上か確認
  } elseif (strlen($_POST['password']) < 4) {
    $error['password'] = 'length';
  }

  //画像があるかチェック
  $filename = $_FILES['image']['name'];
  if (!empty($filename)) {
    $ext = substr($filename, -3);
    if ($ext != 'jpg' && $ext != 'gif' && $ext != 'JPG') {
      $error['image'] = 'type';
    }
  }

  if (empty($error)) {
    $member = $db->prepare('SELECT COUNT(*)as cnt FROM members WHERE email=?');
    $member->execute(array($_POST['email']));
    $record = $member->fetch();
    if ($record['cnt'] > 0) {
      $error['email'] = 'duplicate';
    }
  }

  if (empty($error)) {
    if ($_FILES['image']['name'] != '') {
      $image = date('YmdHis') . $_FILES['image']['name'];
      move_uploaded_file($_FILES['image']['tmp_name'], '../member_picture/' . $image);
    } else {
      $image = "dammy.jpg";
    }
    $_SESSION['join'] = $_POST;
    $_SESSION['join']['image'] = $image;
    header('Location:check.php');
    exit;
  }
}

if ($_REQUEST['action'] == 'rewrite') {
  $_POST = $_SESSION['join'];
  $error['rewrite'] = 'true';
}

?>
<!DOCTYPE html>
<html lang="ja">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta http-equiv="X-UA-Compatible" content="ie=edge">
  <title>ひとこと掲示板</title>

  <link rel="stylesheet" href="../style.css" />
</head>

<body>
  <div id="wrap">
    <div id="head">
      <h1>会員登録</h1>
    </div>
    <div id="content">
      <p>次のフォームに必要事項をご記入下さい。</p>
      <form action="" method="post" enctype="multipart/form-data">
        <dl>
          <dt>ニックネーム<span class="required">必須</span></dt>
          <dd><input type="text" name="name" size="35" maxlength="255" value="<?php echo htmlspecialchars($_POST['name'], ENT_QUOTES); ?>"></dd>
          <?php if ($error['name'] == 'blank') : ?>
            <p class="error">ニックネームを入力して下さい</p>
          <?php endif; ?>
          <dt>メールアドレス<span class="required">必須</span></dt>
          <dd><input type="text" name="email" size="35" maxlength="255" value="<?php echo htmlspecialchars($_POST['email'], ENT_QUOTES); ?>"></dd>
          <?php if ($error['email'] == 'blank') : ?>
            <p class="error">メールアドレスを入力して下さい</p>
          <?php elseif ($error['email'] == 'duplicate') : ?>
            <p class="error">指定されたメールアドレスは既に登録されています。</p>
          <?php endif; ?>
          <dt>パスワード<span class="required">必須</span></dt>
          <dd><input type="password" name="password" size="10" maxlength="20" value="<?php echo htmlspecialchars($_POST['password'], ENT_QUOTES); ?>"></dd>
          <?php if ($error['password'] == 'blank') : ?>
            <p class="error">パスワードを入力して下さい</p>
          <?php elseif ($error['password'] == 'length') : ?>
            <p class="error">パスワードは４文字以上で入力して下さい。</p>
          <?php endif; ?>
          <dt>写真など</dt>
          <dd><input type="file" name="image" size="35"></dd>
          <?php if ($error['image'] == 'type') : ?>
            <p class="error">画像は拡張子が.jpgか.JPGか.gifのファイルを指定して下さい。</p>
          <?php endif; ?>
          <?php if (!empty($error)) : ?>
            <p class="error">恐れ入りますが、画像を改めて指定して下さい</p>
          <?php endif; ?>
        </dl>
        <div><input type="submit" value="入力内容を確認する"></div>
      </form>
    </div>

  </div>
</body>

</html>
