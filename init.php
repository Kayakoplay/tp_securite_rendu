<?php
define('DB', __DIR__ . '/shop.db');

function db() {
    $pdo = new PDO('sqlite:' . DB);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    return $pdo;
}

function init() {
    $pdo = db();
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS users (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            username TEXT UNIQUE,
            password TEXT,
            email TEXT,
            role TEXT DEFAULT 'user',
            bio TEXT DEFAULT '',
            balance REAL DEFAULT 100.0
        );
        CREATE TABLE IF NOT EXISTS products (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name TEXT,
            description TEXT,
            price REAL,
            stock INTEGER DEFAULT 10,
            seller_id INTEGER
        );
        CREATE TABLE IF NOT EXISTS orders (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER,
            product_id INTEGER,
            quantity INTEGER,
            total REAL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        );
        CREATE TABLE IF NOT EXISTS reviews (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            product_id INTEGER,
            user_id INTEGER,
            content TEXT,
            rating INTEGER,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        );
        CREATE TABLE IF NOT EXISTS messages (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            from_id INTEGER,
            to_id INTEGER,
            content TEXT,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        );
        CREATE TABLE IF NOT EXISTS coupons (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            code TEXT UNIQUE,
            discount REAL,
            used INTEGER DEFAULT 0
        );
    ");

    $users = [
        ['alice', md5('alice123'), 'alice@vulnshop.fr', 'user', 'Acheteuse passionnée.', 500.0],
        ['bob', md5('bob123'), 'bob@vulnshop.fr', 'user', 'Vendeur de gadgets.', 200.0],
        ['admin', md5('admin'), 'admin@vulnshop.fr', 'admin', 'Administrateur.', 9999.0],
    ];
    $s = $pdo->prepare("INSERT OR IGNORE INTO users (username,password,email,role,bio,balance) VALUES (?,?,?,?,?,?)");
    foreach ($users as $u) $s->execute($u);

    $products = [
        [1, 'Clé USB 64Go', 'Clé USB rapide et fiable.', 12.99, 50, 2],
        [2, 'Souris sans fil', 'Ergonomique et précise.', 24.99, 30, 2],
        [3, 'Casque audio', 'Son haute fidélité.', 49.99, 15, 1],
        [4, 'Webcam HD', 'Idéale pour les visioconférences.', 39.99, 20, 1],
    ];
    $s = $pdo->prepare("INSERT OR IGNORE INTO products (id,name,description,price,stock,seller_id) VALUES (?,?,?,?,?,?)");
    foreach ($products as $p) $s->execute($p);

    $pdo->exec("INSERT OR IGNORE INTO coupons (code,discount) VALUES ('PROMO10', 10), ('VIP50', 50)");
}

if (!file_exists(DB)) init();

function current_user() {
    if (empty($_SESSION['uid'])) return null;
    $s = db()->prepare("SELECT * FROM users WHERE id = ?");
    $s->execute([$_SESSION['uid']]);
    return $s->fetch(PDO::FETCH_ASSOC) ?: null;
}

function require_login() {
    $u = current_user();
    if (!$u) { header('Location: login.php'); exit; }
    return $u;
}
