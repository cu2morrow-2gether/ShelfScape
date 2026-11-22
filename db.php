<?php
require_once __DIR__ . '/config.php';
session_start();
$_SESSION['csrf'] ??= bin2hex(random_bytes(16)); // anti-CSRF token
$pdo = new PDO('mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4', DB_USER, DB_PASS,
  [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]);

// Run a prepared query and return the statement.
function q($sql, $params = []) { global $pdo; $s = $pdo->prepare($sql); $s->execute($params); return $s; }
// Current user id, or redirect to login.
function uid() { if (empty($_SESSION['uid'])) { header('Location: login.php'); exit; } return (int)$_SESSION['uid']; }
