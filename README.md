# 📖 Shelfscape — Your Digital Library

A cozy, customizable virtual bookshelf. HTML/CSS/JS + PHP + MySQL + epub.js.

## Features
- Multiple shelves, books shown as covers (or colored spines), drag-and-drop rearranging (even between shelves)
- Add books by **EPUB upload**, **online link**, or **manual entry**; edit/delete anytime
- Built-in EPUB reader (epub.js) with saved reading progress; statuses: Want to Read / Currently Reading / Finished
- Genre filters + search, horizontal & vertical scrolling
- Themes, backgrounds, accent colors saved per user; optional 3D tilt (mouse / phone gyroscope)
- User accounts (hashed passwords), prepared SQL statements, validated uploads

## Run locally (XAMPP / any PHP 8 + MySQL)
1. Copy this folder into `htdocs/shelfscape`.
2. Import `database.sql` in phpMyAdmin.
3. Edit `config.php` if your DB user/password differ.
4. Open `http://localhost/shelfscape/` → **Create account**.

## Files
| File | Purpose |
|---|---|
| `index.php` | Dashboard page |
| `assets/app.js` / `style.css` | All frontend logic / styling |
| `api.php` | JSON endpoint (books, shelves, prefs, uploads) |
| `reader.php` | EPUB reader |
| `login.php`, `logout.php`, `db.php`, `config.php`, `database.sql` | Auth + database |

## Ideas to extend
Auto-extract EPUB covers, shelf renaming, CSRF tokens, per-shelf layouts, real plant/lamp artwork.

> GitHub Pages can't run PHP — host on any PHP host (InfinityFree, 000webhost, a VPS) and use GitHub for the code.
