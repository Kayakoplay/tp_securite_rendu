<?php
$title = 'Produit';
require_once 'header.php';

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
    echo '<div class="err">Produit introuvable.</div>';
    require_once 'footer.php';
    exit;
}

function csrf_product_token(int $productId): string {
    if (empty($_SESSION['csrf_product'][$productId])) {
        $_SESSION['csrf_product'][$productId] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_product'][$productId];
}

$csrf = csrf_product_token($id);

$stmt = db()->prepare("SELECT p.*, u.username as seller, u.email as seller_email
    FROM products p JOIN users u ON p.seller_id = u.id
    WHERE p.id = :id");
$stmt->execute(['id' => $id]);
$product = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$product) {
    echo '<div class="err">Produit introuvable.</div>';
    require_once 'footer.php';
    exit;
}

$ok = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $me) {
    $postedToken = $_POST['csrf'] ?? '';
    if (empty($_SESSION['csrf_product'][$id]) || !hash_equals($_SESSION['csrf_product'][$id], $postedToken)) {
        $error = "Session expirée, merci de recharger la page.";
    } else {
        $action = $_POST['action'] ?? '';

        if ($action === 'buy') {
            $qty = max(1, (int)($_POST['qty'] ?? 1));
            $coupon = trim((string)($_POST['coupon'] ?? ''));
            $total = (float)$product['price'] * $qty;

            $couponRow = null;
            if ($coupon !== '') {
                $couponStmt = db()->prepare("SELECT * FROM coupons WHERE code = :code AND used = 0");
                $couponStmt->execute(['code' => $coupon]);
                $couponRow = $couponStmt->fetch(PDO::FETCH_ASSOC);
                if ($couponRow) {
                    $discount = max(0, min(100, (int)$couponRow['discount']));
                    $total = $total * (1 - $discount / 100);
                }
            }

            if ($qty < 1 || $qty > (int)$product['stock']) {
                $error = "Quantité invalide.";
            } elseif ((float)$me['balance'] < $total) {
                $error = "Solde insuffisant.";
            } else {
                $pdo = db();
                $pdo->beginTransaction();
                try {
                    $balanceUpdate = $pdo->prepare("UPDATE users SET balance = balance - :total WHERE id = :id AND balance >= :total");
                    $balanceUpdate->execute(['total' => $total, 'id' => $me['id']]);

                    $stockUpdate = $pdo->prepare("UPDATE products SET stock = stock - :qty WHERE id = :pid AND stock >= :qty");
                    $stockUpdate->execute(['qty' => $qty, 'pid' => $id]);

                    if ($balanceUpdate->rowCount() === 0 || $stockUpdate->rowCount() === 0) {
                        $pdo->rollBack();
                        $error = "Commande impossible (stock/solde).";
                    } else {
                        $orderIns = $pdo->prepare("INSERT INTO orders (user_id,product_id,quantity,total) VALUES (:uid,:pid,:qty,:total)");
                        $orderIns->execute(['uid' => $me['id'], 'pid' => $id, 'qty' => $qty, 'total' => $total]);

                        if ($couponRow) {
                            $markCoupon = $pdo->prepare("UPDATE coupons SET used = used + 1 WHERE id = :id AND used = 0");
                            $markCoupon->execute(['id' => $couponRow['id']]);
                        }

                        $pdo->commit();
                        $ok = "Commande passée ! Total : " . number_format($total, 2) . "€";
                        $me = current_user();

                        unset($_SESSION['csrf_product'][$id]);
                        $csrf = csrf_product_token($id);
                        $stmt->execute(['id' => $id]);
                        $product = $stmt->fetch(PDO::FETCH_ASSOC);
                    }
                } catch (Throwable $e) {
                    $pdo->rollBack();
                    $error = "Une erreur est survenue.";
                }
            }
        }

        if ($action === 'review') {
            $content = trim((string)($_POST['content'] ?? ''));
            $rating = (int)($_POST['rating'] ?? 5);
            if ($content === '') {
                $error = "Votre avis est vide.";
            } elseif ($rating < 1 || $rating > 5) {
                $error = "Note invalide.";
            } else {
                $stmtRev = db()->prepare("INSERT INTO reviews (product_id,user_id,content,rating) VALUES (:pid,:uid,:content,:rating)");
                $stmtRev->execute(['pid' => $id, 'uid' => $me['id'], 'content' => $content, 'rating' => $rating]);
                unset($_SESSION['csrf_product'][$id]);
                header("Location: product.php?id=" . $id);
                exit;
            }
        }
    }
}

$reviewsStmt = db()->prepare("SELECT r.*, u.username FROM reviews r
     JOIN users u ON r.user_id = u.id
     WHERE r.product_id = :pid ORDER BY r.created_at DESC");
$reviewsStmt->execute(['pid' => $id]);
$reviews = $reviewsStmt->fetchAll(PDO::FETCH_ASSOC);

$avg = count($reviews) ? round(array_sum(array_column($reviews,'rating')) / count($reviews),1) : null;
?>

<div class="card">
  <h1><?php echo htmlspecialchars($product['name'], ENT_QUOTES, 'UTF-8'); ?></h1>
  <p style="color:#666;margin-bottom:12px"><?php echo htmlspecialchars($product['description'], ENT_QUOTES, 'UTF-8'); ?></p>
  <div class="price"><?php echo number_format($product['price'],2); ?> €</div>
  <p class="meta" style="margin:8px 0">
    Stock : <?php echo (int)$product['stock']; ?> —
    Vendeur : <?php echo htmlspecialchars($product['seller'], ENT_QUOTES, 'UTF-8'); ?> (<?php echo htmlspecialchars($product['seller_email'], ENT_QUOTES, 'UTF-8'); ?>) —
    <?php if ($avg): ?><span class="stars"><?php echo str_repeat('★',(int)$avg); ?></span> <?php echo $avg; ?>/5<?php endif; ?>
  </p>

  <?php if ($ok): ?><div class="ok"><?php echo htmlspecialchars($ok, ENT_QUOTES, 'UTF-8'); ?></div><?php endif; ?>
  <?php if ($error): ?><div class="err"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></div><?php endif; ?>

  <?php if ($me): ?>
  <form method="POST" style="margin-top:14px">
    <input type="hidden" name="csrf" value="<?php echo htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8'); ?>">
    <div style="display:flex;gap:10px;align-items:center">
      <input type="number" name="qty" value="1" min="1" max="<?php echo (int)$product['stock']; ?>" style="width:80px;margin:0">
      <input type="text" name="coupon" placeholder="Code promo" style="width:160px;margin:0">
      <button class="btn btn-green" name="action" value="buy" type="submit">🛒 Acheter</button>
    </div>
  </form>
  <?php endif; ?>
</div>

<div class="card">
  <h2>⭐ Avis clients (<?php echo count($reviews); ?>)</h2>

  <?php foreach ($reviews as $rv): ?>
  <div style="border-bottom:1px solid #eee;padding:10px 0">
    <p class="meta">
      <strong><?php echo htmlspecialchars($rv['username'], ENT_QUOTES, 'UTF-8'); ?></strong> —
      <span class="stars"><?php echo str_repeat('★',(int)$rv['rating']); ?></span> —
      <?php echo htmlspecialchars($rv['created_at'], ENT_QUOTES, 'UTF-8'); ?>
    </p>
    <div style="margin-top:6px"><?php echo nl2br(htmlspecialchars($rv['content'], ENT_QUOTES, 'UTF-8')); ?></div>
  </div>
  <?php endforeach; ?>

  <?php if ($me): ?>
  <form method="POST" style="margin-top:16px">
    <input type="hidden" name="csrf" value="<?php echo htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8'); ?>">
    <label style="font-size:13px">Note</label>
    <select name="rating" style="width:auto;margin-bottom:10px">
      <?php for($i=5;$i>=1;$i--): ?>
        <option value="<?php echo $i; ?>"><?php echo str_repeat('★',$i); ?></option>
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
