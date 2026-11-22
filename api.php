<?php
// One JSON endpoint for everything the dashboard does. Every query is scoped to the logged-in user.
require 'db.php';
header('Content-Type: application/json');
if (empty($_SESSION['uid'])) { http_response_code(401); die('{"error":"Please log in"}'); }
$u = (int)$_SESSION['uid'];
if (!hash_equals($_SESSION['csrf'], $_POST['csrf'] ?? '')) { http_response_code(403); die('{"error":"Bad token, reload the page"}'); }
function out($d) { echo json_encode($d); exit; }
function fail($m) { http_response_code(400); out(['error' => $m]); }
function myShelf($id, $u) { if (!q('SELECT id FROM shelves WHERE id=? AND user_id=?', [$id, $u])->fetch()) fail('Shelf not found'); return (int)$id; }
function saveFile($f, $exts, $max, $check) {
  if ($f['error'] !== UPLOAD_ERR_OK) fail('Upload failed (file too big?)');
  $ext = strtolower(pathinfo($f['name'], PATHINFO_EXTENSION));
  if (!in_array($ext, $exts)) fail('Allowed file types: ' . implode(', ', $exts));
  if ($f['size'] > $max) fail('File too large (max ' . ($max / 1048576) . 'MB)');
  if (!$check($f['tmp_name'])) fail('File content is not valid');
  $name = bin2hex(random_bytes(8)) . '.' . $ext;
  move_uploaded_file($f['tmp_name'], __DIR__ . "/uploads/$name");
  return $name;
}
// Best-effort: find the cover image inside an EPUB (a zip) and save it to uploads/.
function epubCover($path) {
  if (!class_exists('ZipArchive')) return null;
  $z = new ZipArchive; if ($z->open($path) !== true) return null;
  $c = @simplexml_load_string((string)$z->getFromName('META-INF/container.xml')); if (!$c) return null;
  $opf = (string)$c->rootfiles->rootfile['full-path'];
  $x = @simplexml_load_string((string)$z->getFromName($opf)); if (!$x) return null;
  $dir = dirname($opf) === '.' ? '' : dirname($opf) . '/';
  foreach ($x->manifest->item as $i) {
    $isImg = str_starts_with((string)$i['media-type'], 'image/');
    if ($isImg && (str_contains((string)$i['properties'], 'cover-image') || stripos((string)$i['id'], 'cover') !== false)) {
      $img = $z->getFromName($dir . urldecode((string)$i['href']));
      if ($img) { $n = 'uploads/' . bin2hex(random_bytes(8)) . '.jpg'; file_put_contents(__DIR__ . "/$n", $img); return $n; }
    }
  }
  return null;
}
$a = $_POST['action'] ?? '';
switch ($a) {
  case 'list':
    out(['shelves' => q('SELECT * FROM shelves WHERE user_id=? ORDER BY id', [$u])->fetchAll(),
         'books' => q('SELECT * FROM books WHERE user_id=? ORDER BY position,id', [$u])->fetchAll(),
         'prefs' => q('SELECT * FROM prefs WHERE user_id=?', [$u])->fetch()]);
  case 'shelf_add':
    $n = trim($_POST['name'] ?? ''); if ($n === '') fail('Name your shelf');
    q('INSERT INTO shelves(user_id,name) VALUES(?,?)', [$u, mb_substr($n, 0, 60)]); out(['ok' => 1]);
  case 'shelf_rename':
    $id = myShelf($_POST['id'] ?? 0, $u); $n = trim($_POST['name'] ?? ''); if ($n === '') fail('Name your shelf');
    q('UPDATE shelves SET name=? WHERE id=?', [mb_substr($n, 0, 60), $id]); out(['ok' => 1]);
  case 'shelf_delete':
    $id = myShelf($_POST['id'] ?? 0, $u);
    if (q('SELECT COUNT(*) c FROM shelves WHERE user_id=?', [$u])->fetch()['c'] < 2) fail('Keep at least one shelf');
    q('DELETE FROM shelves WHERE id=?', [$id]); out(['ok' => 1]);
  case 'book_save':
    $id = (int)($_POST['id'] ?? 0); $title = trim($_POST['title'] ?? ''); $src = $_POST['source'] ?? 'manual';
    if (!in_array($src, ['epub', 'link', 'manual'])) fail('Bad source');
    $shelf = myShelf($_POST['shelf_id'] ?? 0, $u);
    $file = null; $link = null; $cover = trim($_POST['cover_url'] ?? '') ?: null;
    if ($src === 'epub' && !empty($_FILES['epub']['name'])) {
      $file = saveFile($_FILES['epub'], ['epub'], 20 * 1048576, fn($p) => file_get_contents($p, false, null, 0, 2) === 'PK');
      if ($title === '') $title = pathinfo($_FILES['epub']['name'], PATHINFO_FILENAME);
    }
    if ($src === 'epub' && !$file && !$id) fail('Choose an EPUB file');
    if ($src === 'link') { $link = trim($_POST['link_url'] ?? ''); if (!preg_match('#^https?://#i', $link) || !filter_var($link, FILTER_VALIDATE_URL)) fail('Enter a valid http(s) link'); }
    if (!empty($_FILES['cover']['name'])) {
      $c = saveFile($_FILES['cover'], ['jpg', 'jpeg', 'png', 'webp'], 3 * 1048576, fn($p) => (bool)getimagesize($p));
      $cover = "uploads/$c";
    }
    if (!$cover && $file) $cover = epubCover(__DIR__ . "/uploads/$file");
    if ($title === '') fail('Title is required');
    $f = [$title, trim($_POST['author'] ?? ''), trim($_POST['genre'] ?? '') ?: 'Fiction', trim($_POST['description'] ?? ''), $cover, $src, $link, $shelf];
    if ($id) {
      q('UPDATE books SET title=?,author=?,genre=?,description=?,cover_url=?,source=?,link_url=?,shelf_id=?,file_path=COALESCE(?,file_path) WHERE id=? AND user_id=?', [...$f, $file, $id, $u]);
    } else {
      q('INSERT INTO books(title,author,genre,description,cover_url,source,link_url,shelf_id,file_path,user_id,position) VALUES(?,?,?,?,?,?,?,?,?,?,?)', [...$f, $file, $u, time()]);
    }
    out(['ok' => 1]);
  case 'book_delete':
    $b = q('SELECT * FROM books WHERE id=? AND user_id=?', [$_POST['id'] ?? 0, $u])->fetch();
    if ($b) { foreach ([$b['file_path'], $b['cover_url']] as $p) { $p = basename((string)$p); if ($p && is_file(__DIR__ . "/uploads/$p")) unlink(__DIR__ . "/uploads/$p"); }
      q('DELETE FROM books WHERE id=?', [$b['id']]); }
    out(['ok' => 1]);
  case 'book_status':
    if (!in_array($_POST['status'] ?? '', ['want', 'reading', 'finished'])) fail('Bad status');
    q('UPDATE books SET status=? WHERE id=? AND user_id=?', [$_POST['status'], $_POST['id'] ?? 0, $u]); out(['ok' => 1]);
  case 'progress':
    $p = max(0, min(100, (int)($_POST['progress'] ?? 0)));
    q("UPDATE books SET progress=?, cfi=?, status=IF(?>=99,'finished',IF(?>0,'reading',status)) WHERE id=? AND user_id=?",
      [$p, $_POST['cfi'] ?? null, $p, $p, $_POST['id'] ?? 0, $u]); out(['ok' => 1]);
  case 'reorder': // ids = comma list in new order, all placed on shelf_id
    $shelf = myShelf($_POST['shelf_id'] ?? 0, $u);
    foreach (explode(',', $_POST['ids'] ?? '') as $i => $bid)
      q('UPDATE books SET position=?, shelf_id=? WHERE id=? AND user_id=?', [$i, $shelf, (int)$bid, $u]);
    out(['ok' => 1]);
  case 'prefs_save':
    $themes = ['wood', 'pink', 'dark', 'light']; $bgs = ['window', 'library', 'night', 'clouds'];
    $t = $_POST['shelf_theme'] ?? ''; $b = $_POST['background'] ?? ''; $c = $_POST['accent'] ?? '';
    if (!in_array($t, $themes) || !in_array($b, $bgs) || !preg_match('/^#[0-9a-f]{6}$/i', $c)) fail('Invalid preferences');
    q('INSERT INTO prefs(user_id,shelf_theme,background,accent,tilt) VALUES(?,?,?,?,?) ON DUPLICATE KEY UPDATE shelf_theme=VALUES(shelf_theme),background=VALUES(background),accent=VALUES(accent),tilt=VALUES(tilt)',
      [$u, $t, $b, $c, (int)!empty($_POST['tilt'])]); out(['ok' => 1]);
}
fail('Unknown action');
