<?php
// change_name.php — 「自分の名前」を MySQL に UPDATE する最小実装

// 1) セッション確認（Redis）: 未ログインならログイン画面へ
$session_cookie_name = 'session_id';
$session_id = $_COOKIE[$session_cookie_name] ?? base64_encode(random_bytes(64));
if (!isset($_COOKIE[$session_cookie_name])) setcookie($session_cookie_name, $session_id);

$redis = new Redis();
$redis->connect('redis', 6379);
$redis_session_key = "session-" . $session_id;

$session_values = $redis->exists($redis_session_key)
  ? json_decode($redis->get($redis_session_key), true)
  : [];

if (empty($session_values['login_user_id'])) {
  header('Location: ./login.php', true, 302);
  exit;
}

// 2) DB接続
$dbh = new PDO('mysql:host=mysql;dbname=example_db', 'root', '');

// 3) MySQL への更新
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $new_name = isset($_POST['name']) ? trim($_POST['name']) : '';
  if ($new_name === '') {
    header('Location: ./change_name.php?error=1', true, 303);
    exit;
  }

  // UPDATE 文で users.name を自分の id に対して更新
  $update = $dbh->prepare('UPDATE users SET name = :name WHERE id = :id');
  $update->execute([
    ':name' => $new_name,
    ':id'   => $session_values['login_user_id'], // セッションに入っている自分のID
  ]);
  // 二重送信防止 & 完了メッセージ
  header('Location: ./change_name.php?updated=1', true, 303);
  exit;
}

// 表示用に現在のユーザー情報取得
$sel = $dbh->prepare('SELECT * FROM users WHERE id = :id');
$sel->execute([':id' => $session_values['login_user_id']]);
$user = $sel->fetch();

function h($s) { return htmlspecialchars($s ?? '', ENT_QUOTES, 'UTF-8'); }
?>
<!doctype html>
<html lang="ja">
<meta charset="utf-8">
<title>名前変更</title>
<body>
  <h1>名前変更</h1>

  <?php if (!empty($_GET['updated'])): ?>
    <div style="color:green;">名前を更新しました。</div>
  <?php endif; ?>
  <?php if (!empty($_GET['error'])): ?>
    <div style="color:red;">名前を入力してください。</div>
  <?php endif; ?>

  <p>現在の名前：<strong><?= h($user['name']) ?></strong></p>

  <form method="POST">
    <label>新しい名前：
      <input type="text" name="name" value="<?= h($user['name']) ?>">
    </label>
    <button type="submit">更新</button>
  </form>

</body>
</html>