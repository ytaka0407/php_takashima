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

//いいねに入っているidがログインユーザーのIDと一致していなければ一覧画面へ戻る。
if (!($_POST['id'] ?? '') === $member['id']) {
    header('Location:index.php');
    exit;
}

//POST内容チェック
//配列キーmsgid,idが数値、配列キーlikeがchangeの場合のみSQL文実行。idについては上でチェック済み
//likeの中身も下でチェックするので
//msgidのみチェック実施
if (!is_numeric(($_POST['msgid'] ?? ''))) {
    header('Location:index.php');
    exit;
}

//新規にいいねされた時には情報をデータベースに追加。2回め以降の場合はいいねのswitchを切り替え
if (($_POST['like'] ?? '') === 'change') {
    $check = $db->prepare('SELECT COUNT(*) as count,id, switch FROM likeactions WHERE member_id=? AND message_id=?');
    $check->bindParam(1, $member['id'], PDO::PARAM_INT);
    $check->bindParam(2, $_POST['msgid'], PDO::PARAM_INT);
    $check->execute();
    $result = $check->fetch();

    if (!$result['count']) {
        $newaction = $db->prepare('INSERT INTO likeactions SET member_id=?,message_id=?,switch=1,created=NOW()');
        $newaction->bindParam(1, $member['id'], PDO::PARAM_INT);
        $newaction->bindParam(2, $_POST['msgid'], PDO::PARAM_INT);
        $newaction->execute();
    } else {
        //取得データのswitchがTRUEの場合はFALSE、そうでなければTRUEにUPDATE
        $switch = ($result['switch'] !== '1');
        $changeaction = $db->prepare('UPDATE likeactions SET switch=? WHERE id=?');
        $changeaction->bindParam(1, $switch, PDO::PARAM_INT);
        $changeaction->bindParam(2, $result['id'], PDO::PARAM_INT);
        $changeaction->execute();
    }
}
header("Location:index.php");
exit;
