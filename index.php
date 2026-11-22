<?php require 'db.php'; uid(); ?><!DOCTYPE html><html><head><meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1"><title>Shelfscape</title><meta name="csrf" content="<?= $_SESSION['csrf'] ?>">
<link rel="stylesheet" href="assets/style.css"></head>
<body data-bg="window" data-shelf="wood">
<header><div class="logo">📖 <b>Shelfscape</b><small>Your Digital Library ♡</small></div>
  <input id="search" placeholder="Search for books..."><a href="logout.php" class="ghost" title="Log out">Log out</a></header>
<div class="layout">
<nav><a data-view="home" class="on">🏠 Home</a><a data-view="library">📚 My Library</a><a id="addNav">➕ Add Book</a><a data-view="settings">⚙️ Settings</a></nav>
<main><div id="filters"></div>
  <div id="stage"><div id="room"><div id="shelves"></div></div></div></main>
<aside id="custom"><h3>🎨 Customize Your Library</h3>
  <label>Shelf Theme</label><div class="opts" id="o-shelf"></div>
  <label>Background</label><div class="opts" id="o-bg"></div>
  <label>Accent Color</label><div class="opts" id="o-acc"></div>
  <label class="chk"><input type="checkbox" id="tilt"> 3D tilt / parallax</label>
  <button id="save">Save Changes</button><hr>
  <label>Shelves</label><div id="shelfList"></div>
  <div class="row"><input id="newShelf" placeholder="New shelf name"><button id="addShelf">Add</button></div></aside></div>

<dialog id="addDlg"><form id="bookForm"><h3 id="dlgTitle">Add Book</h3><input type="hidden" name="id">
  <select name="source"><option value="manual">✍️ Enter details manually</option><option value="epub">📄 Upload EPUB</option><option value="link">🔗 Add online link</option></select>
  <input type="file" name="epub" accept=".epub" class="src epub"><input name="link_url" placeholder="https://..." class="src link">
  <input name="title" placeholder="Title"><input name="author" placeholder="Author">
  <input name="genre" list="genres" placeholder="Genre"><datalist id="genres"><option>Fantasy<option>Romance<option>Mystery<option>Fiction<option>Classics</datalist>
  <select name="shelf_id"></select><textarea name="description" placeholder="Description"></textarea>
  <input name="cover_url" placeholder="Cover image URL (optional)"><label>or upload cover <input type="file" name="cover" accept="image/*"></label>
  <p class="err" id="formErr"></p><div class="row"><button>Save</button><button type="button" class="ghost" data-close>Cancel</button></div></form></dialog>

<dialog id="viewDlg"><div id="viewBody"></div></dialog>
<script src="assets/app.js"></script></body></html>
