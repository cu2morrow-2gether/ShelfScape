<?php
require 'db.php';
$err = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $name = trim($_POST['username'] ?? ''); $pass = $_POST['password'] ?? '';
  if (isset($_POST['register'])) {
    if (!preg_match('/^\w{3,40}$/', $name) || strlen($pass) < 6) $err = 'Username: 3-40 letters/numbers. Password: 6+ characters.';
    elseif (q('SELECT id FROM users WHERE username=?', [$name])->fetch()) $err = 'Username taken.';
    else {
      q('INSERT INTO users(username,password_hash) VALUES(?,?)', [$name, password_hash($pass, PASSWORD_DEFAULT)]);
      $id = $pdo->lastInsertId();
      q('INSERT INTO shelves(user_id,name) VALUES(?,?)', [$id, 'My First Shelf']);
      q('INSERT INTO prefs(user_id) VALUES(?)', [$id]);
      $_SESSION['uid'] = $id; header('Location: index.php'); exit;
    }
  } else {
    $u = q('SELECT * FROM users WHERE username=?', [$name])->fetch();
    if ($u && password_verify($pass, $u['password_hash'])) { $_SESSION['uid'] = $u['id']; header('Location: index.php'); exit; }
    $err = 'Wrong username or password.';
  }
}
?><!DOCTYPE html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Shelfscape</title><link rel="stylesheet" href="assets/style.css"></head>
<body data-bg="window" class="login"><form method="post" class="card">
<h1>📖 Shelfscape</h1><p>Your Digital Library ♡</p>
<?php if ($err) echo '<p class="err">' . htmlspecialchars($err) . '</p>'; ?>
<input name="username" placeholder="Username" required><input name="password" type="password" placeholder="Password" required>
<button>Log in</button><button name="register" value="1" class="ghost">Create account</button></form></body></html>
