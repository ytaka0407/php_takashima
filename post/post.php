<?php
session_start();
require('dbconnect.php');

//ログインチェック
if (isset($_SESSION['id']) && $_SESSION['time'] + 3600 > time()) {
    $_SESSION['time'] = time();
}else{
header('Location:login.php');
exit;
}

$member['id']=$_SESSION['id'];

//$_POSTに入っているidがログインユーザーのIDと一致していなければ一覧画面へ戻る。
if (!($_POST['id']??'')===$member['id']) {
    header('Location:index.php');
    exit;
}

//$_POSTになにか投稿されていたら、$_POST['message']の中身を確認して投稿。
//$_POST['reply_post_id']及び$_POST['rt_post_id']は空欄もしくは数字のみ。
if (!empty($_POST)) {
    if(((!$_POST['reply_post_id']==='')&&(!is_numeric($_POST['reply_post_id'])))){
        header('Location:index.php');
        exit;
    }
    if(((!$_POST['rt_post_id']==='')&&(!is_numeric($_POST['rt_post_id'])))){
        header('Location:index.php');
        exit;
    }
    if (($_POST['message'] ?? '') !== '') {
      $posts = $db->prepare('INSERT INTO posts SET message=?, member_id=?, reply_post_id=?, retweet_post_id=?,created=NOW()');
      $posts->execute(array($_POST['message'], $member['id'], ($_POST['reply_post_id'] ?? ''), ($_POST['rt_post_id'] ?? '')));
      header('Location:index.php');
      exit;
    }
  }

