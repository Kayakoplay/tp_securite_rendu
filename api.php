<?php
require_once __DIR__ . '/init.php';
header('Content-Type: application/json');

$action = $_GET['action'] ?? $_POST['action'] ?? '';
$db     = db();

if ($action === 'search') {
    $q    = $_GET['q'] ?? '';
    $rows = $db->query("SELECT * FROM products WHERE name LIKE '%$q%'")->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode($rows);
    exit;
}

if ($action === 'user') {
    $id   = $_GET['id'] ?? 0;
    $user = $db->query("SELECT * FROM users WHERE id=$id")->fetch(PDO::FETCH_ASSOC);
    echo json_encode($user);
    exit;
}

if ($action === 'users') {
    $rows = $db->query("SELECT * FROM users")->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode($rows);
    exit;
}

if ($action === 'orders') {
    $uid  = $_GET['uid'] ?? 0;
    $rows = $db->query("SELECT * FROM orders WHERE user_id=$uid")->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode($rows);
    exit;
}

if ($action === 'transfer' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $from   = intval($_POST['from_id'] ?? 0);
    $to     = intval($_POST['to_id'] ?? 0);
    $amount = floatval($_POST['amount'] ?? 0);
    $db->query("UPDATE users SET balance=balance-$amount WHERE id=$from");
    $db->query("UPDATE users SET balance=balance+$amount WHERE id=$to");
    echo json_encode(['status' => 'ok', 'transferred' => $amount]);
    exit;
}

if ($action === 'delete_all_reviews') {
    $pid = $_GET['pid'] ?? 0;
    $db->query("DELETE FROM reviews WHERE product_id=$pid");
    echo json_encode(['status' => 'ok']);
    exit;
}

if ($action === 'raw_query') {
    $sql  = $_GET['sql'] ?? '';
    $rows = $db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode($rows);
    exit;
}

echo json_encode(['error' => 'Action inconnue', 'actions' => ['search','user','users','orders','transfer','delete_all_reviews','raw_query']]);
