<?php require 'db.php'; $u = uid();
$b = q('SELECT * FROM books WHERE id=? AND user_id=?', [$_GET['id'] ?? 0, $u])->fetch();
if (!$b || $b['source'] !== 'epub' || !$b['file_path']) { http_response_code(404); die('Book not found'); } ?>
<!DOCTYPE html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title><?= htmlspecialchars($b['title']) ?></title><link rel="stylesheet" href="assets/style.css"></head>
<body class="reader"><header><a href="index.php" class="ghost">← Library</a><b><?= htmlspecialchars($b['title']) ?></b><span id="pct"><?= (int)$b['progress'] ?>%</span></header>
<div class="rwrap"><button id="prev">‹</button><div id="viewer"></div><button id="next">›</button></div>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/epub.js/0.3.93/epub.min.js"></script>
<script>
const id = <?= (int)$b['id'] ?>, saved = <?= json_encode($b['cfi']) ?>;
const book = ePub('uploads/<?= htmlspecialchars($b['file_path']) ?>');
const rend = book.renderTo('viewer', {width: '100%', height: '100%', flow: 'paginated'});
rend.display(saved || undefined);
book.ready.then(() => book.locations.generate(1000));
rend.on('relocated', loc => {
  if (!book.locations.length()) return;
  const p = Math.round(book.locations.percentageFromCfi(loc.start.cfi) * 100);
  document.getElementById('pct').textContent = p + '%';
  const f = new FormData(); f.append('action', 'progress'); f.append('csrf', <?= json_encode($_SESSION['csrf']) ?>); f.append('id', id); f.append('progress', p); f.append('cfi', loc.start.cfi);
  fetch('api.php', {method: 'POST', body: f});
});
prev.onclick = () => rend.prev(); next.onclick = () => rend.next();
addEventListener('keydown', e => { if (e.key === 'ArrowLeft') rend.prev(); if (e.key === 'ArrowRight') rend.next(); });
</script></body></html>
