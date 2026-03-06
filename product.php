<?php
$title = 'Produit';
require_once 'header.php';

$id = $_GET['id'] ?? 0;

$product = db()->query(
    "SELECT p.*, u.username as seller, u.email as seller_email
     FROM products p JOIN users u ON p.seller_id = u.id
     WHERE p.id = $id"
)->fetch(PDO::FETCH_ASSOC);

if (!$product) {
    echo '<div class="err">Produit introuvable.</div>';
    require_once 'footer.php'; exit;
}

$ok = $error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $me) {
    $action = $_POST['action'] ?? '';

    if ($action === 'buy') {
        $qty     = intval($_POST['qty'] ?? 1);
        $coupon  = trim($_POST['coupon'] ?? '');
        $total   = $product['price'] * $qty;

        if ($coupon !== '') {
            $c = db()->query("SELECT * FROM coupons WHERE code='$coupon' AND used=0")->fetch(PDO::FETCH_ASSOC);
            if ($c) {
                $total = $total * (1 - $c['discount'] / 100);
                db()->query("UPDATE coupons SET used=used+1 WHERE code='$coupon'");
            }
        }

        if ($qty < 1 || $qty > $product['stock']) {
            $error = "Quantité invalide.";
        } elseif ($me['balance'] < $total) {
            $error = "Solde insuffisant.";
        } else {
            db()->prepare("INSERT INTO orders (user_id,product_id,quantity,total) VALUES (?,?,?,?)")
               ->execute([$me['id'], $id, $qty, $total]);
            db()->query("UPDATE users SET balance=balance-$total WHERE id=" . $me['id']);
            db()->query("UPDATE products SET stock=stock-$qty WHERE id=$id");
            $ok = "Commande passée ! Total : " . number_format($total,2) . "€";
            $me = current_user();
        }
    }

    if ($action === 'review') {
        $content = $_POST['content'] ?? '';
        $rating  = intval($_POST['rating'] ?? 5);
        if ($content) {
            db()->prepare("INSERT INTO reviews (product_id,user_id,content,rating) VALUES (?,?,?,?)")
               ->execute([$id, $me['id'], $content, $rating]);
            header("Location: product.php?id=$id"); exit;
        }
    }
}

$reviews = db()->query(
    "SELECT r.*, u.username FROM reviews r
     JOIN users u ON r.user_id = u.id
     WHERE r.product_id = $id ORDER BY r.created_at DESC"
)->fetchAll(PDO::FETCH_ASSOC);

$avg = count($reviews) ? round(array_sum(array_column($reviews,'rating')) / count($reviews),1) : null;
?>

<div class="card">
  <h1><?= htmlspecialchars($product['name']) ?></h1>
  <p style="color:#666;margin-bottom:12px"><?= htmlspecialchars($product['description']) ?></p>
  <div class="price"><?= number_format($product['price'],2) ?> €</div>
  <p class="meta" style="margin:8px 0">
    Stock : <?= $product['stock'] ?> — 
    Vendeur : <?= htmlspecialchars($product['seller']) ?> (<?= $product['seller_email'] ?>) —
    <?php if ($avg): ?><span class="stars"><?= str_repeat('★',(int)$avg) ?></span> <?= $avg ?>/5<?php endif; ?>
  </p>

  <?php if ($ok): ?><div class="ok"><?= $ok ?></div><?php endif; ?>
  <?php if ($error): ?><div class="err"><?= $error ?></div><?php endif; ?>

  <?php if ($me): ?>
  <form method="POST" style="margin-top:14px">
    <div style="display:flex;gap:10px;align-items:center">
      <input type="number" name="qty" value="1" min="1" max="<?= $product['stock'] ?>" style="width:80px;margin:0">
      <input type="text" name="coupon" placeholder="Code promo" style="width:160px;margin:0">
      <button class="btn btn-green" name="action" value="buy" type="submit">🛒 Acheter</button>
    </div>
  </form>
  <?php endif; ?>
</div>

<div class="card">
  <h2>⭐ Avis clients (<?= count($reviews) ?>)</h2>

  <?php foreach ($reviews as $rv): ?>
  <div style="border-bottom:1px solid #eee;padding:10px 0">
    <p class="meta">
      <strong><?= htmlspecialchars($rv['username']) ?></strong> —
      <span class="stars"><?= str_repeat('★',$rv['rating']) ?></span> —
      <?= $rv['created_at'] ?>
    </p>
    <div style="margin-top:6px"><?= $rv['content'] ?></div>
  </div>
  <?php endforeach; ?>

  <?php if ($me): ?>
  <form method="POST" style="margin-top:16px">
    <label style="font-size:13px">Note</label>
    <select name="rating" style="width:auto;margin-bottom:10px">
      <?php for($i=5;$i>=1;$i--): ?>
        <option value="<?= $i ?>"><?= str_repeat('★',$i) ?></option>
      <?php endfor; ?>
    </select>
    <textarea name="content" placeholder="Votre avis..."></textarea>
    <button class="btn" name="action" value="review" type="submit">Publier</button>
  </form>
  <?php else: ?>
    <p style="color:#888;font-size:13px;margin-top:10px"><a href="login.php">Connectez-vous</a> pour laisser un avis.</p>
  <?php endif; ?>
</div>
<?php require_once 'footer.php'; ?>
