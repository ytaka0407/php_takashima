<?php
session_start();
require('dbconnect.php');

//ログインチェック
if (isset($_SESSION['id']) && $_SESSION['time'] + 3600 > time()) {
    $_SESSION['time'] = time();
} else {
    header('Location:login.php');
    exit;
}

$member['id'] = $_SESSION['id'];

//$_POSTに入っているidがログインユーザーのIDと一致していなければ一覧画面へ戻る。
if (!($_POST['id'] ?? '') === $member['id']) {
    header('Location:index.php');
    exit;
}
//$_POST['rt_post_id']は数字のみ。
if (!is_numeric($_POST['rt_post_id'])) {
    header('Location:index.php');
    exit;
}

//$_POST['message']に値がある場合、リツイートスイッチTRUEもしくは新規にリツイート
//ログインユーザーとretweet_post_idの組み合わせがあるか確認
//ある場合、再リツイートとしてmessageの中身を更新してswitchTRUEにする。
//ない場合は新規投稿。
if (($_POST['message'] ?? '') !== '') {
    $checkquery = $db->prepare('SELECT * FROM posts WHERE member_id=? AND retweet_post_id=?');
    $checkquery->bindParam(1, $member['id'], PDO::PARAM_INT);
    $checkquery->bindParam(2, $_REQUEST['rt_post_id'], PDO::PARAM_INT);
    $checkquery->execute();
    $result = $checkquery->fetch();
    if ($result) {
        if ($result['switch'] === '0') {
            $changeaction = $db->prepare('UPDATE posts SET message=?, switch=TRUE WHERE id=?');
            $changeaction->execute(array($_REQUEST['message'], $result['id']));
            header('Location:index.php');
            exit;
        } else {
            header('Location:already_retweeted.php');
            exit;
        }
    } else {
        $posts = $db->prepare('INSERT INTO posts SET message=?, member_id=?, retweet_post_id=?, created=NOW()');
        $posts->execute(array($_POST['message'], $member['id'], ($_POST['rt_post_id'] ?? '')));
        header('Location:index.php');
        exit;
    }
}
