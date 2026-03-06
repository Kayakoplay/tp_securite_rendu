<?php
$title = 'Administration';
require_once 'header.php';
$me = require_login();

if ($me['role'] !== 'admin') {
    echo '<div class="err">Accès réservé aux administrateurs.</div>';
}

$db  = db();
$ok  = $error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'delete_user') {
        $uid = $_POST['uid'] ?? 0;
        $db->query("DELETE FROM users WHERE id=$uid");
        $ok = "Utilisateur supprimé.";
    }

    if ($action === 'set_role') {
        $uid  = $_POST['uid'] ?? 0;
        $role = $_POST['role'] ?? 'user';
        $db->query("UPDATE users SET role='$role' WHERE id=$uid");
        $ok = "Rôle mis à jour.";
    }

    if ($action === 'delete_product') {
        $pid = $_POST['pid'] ?? 0;
        $db->query("DELETE FROM products WHERE id=$pid");
        $ok = "Produit supprimé.";
    }

    if ($action === 'delete_review') {
        $rid = $_POST['rid'] ?? 0;
        $db->query("DELETE FROM reviews WHERE id=$rid");
        $ok = "Avis supprimé.";
    }

    if ($action === 'add_balance') {
        $uid    = $_POST['uid'] ?? 0;
        $amount = floatval($_POST['amount'] ?? 0);
        $db->query("UPDATE users SET balance=balance+$amount WHERE id=$uid");
        $ok = "Solde modifié.";
    }
}

$users    = $db->query("SELECT * FROM users")->fetchAll(PDO::FETCH_ASSOC);
$products = $db->query("SELECT * FROM products")->fetchAll(PDO::FETCH_ASSOC);
$orders   = $db->query("SELECT o.*, u.username, p.name as product_name FROM orders o JOIN users u ON o.user_id=u.id JOIN products p ON o.product_id=p.id ORDER BY o.created_at DESC")->fetchAll(PDO::FETCH_ASSOC);
$reviews  = $db->query("SELECT r.*, u.username, p.name as product_name FROM reviews r JOIN users u ON r.user_id=u.id JOIN products p ON r.product_id=p.id ORDER BY r.created_at DESC")->fetchAll(PDO::FETCH_ASSOC);
?>

<?php if ($ok): ?><div class="ok"><?= $ok ?></div><?php endif; ?>

<div class="card">
  <h1>🔧 Administration</h1>

  <h2>👥 Utilisateurs</h2>
  <table>
    <tr><th>ID</th><th>Username</th><th>Email</th><th>Rôle</th><th>Solde</th><th>Mot de passe (MD5)</th><th>Actions</th></tr>
    <?php foreach ($users as $u): ?>
    <tr>
      <td><?= $u['id'] ?></td>
      <td><a href="profile.php?uid=<?= $u['id'] ?>"><?= htmlspecialchars($u['username']) ?></a></td>
      <td><?= htmlspecialchars($u['email']) ?></td>
      <td><?= htmlspecialchars($u['role']) ?></td>
      <td><?= number_format($u['balance'],2) ?>€</td>
      <td style="font-size:11px;color:#999"><?= $u['password'] ?></td>
      <td style="display:flex;gap:6px;flex-wrap:wrap">
        <form method="POST" style="margin:0;display:flex;gap:4px">
          <input type="hidden" name="uid" value="<?= $u['id'] ?>">
          <select name="role" style="padding:3px;font-size:12px;width:auto;margin:0">
            <option <?= $u['role']==='user'?'selected':'' ?>>user</option>
            <option <?= $u['role']==='admin'?'selected':'' ?>>admin</option>
          </select>
          <button class="btn btn-sm btn-blue" name="action" value="set_role" type="submit">Rôle</button>
        </form>
        <form method="POST" style="margin:0;display:flex;gap:4px">
          <input type="hidden" name="uid" value="<?= $u['id'] ?>">
          <input type="number" name="amount" placeholder="€" style="width:60px;padding:3px;font-size:12px;margin:0">
          <button class="btn btn-sm btn-green" name="action" value="add_balance" type="submit">+Solde</button>
        </form>
        <form method="POST" style="margin:0" onsubmit="return confirm('Supprimer ?')">
          <input type="hidden" name="uid" value="<?= $u['id'] ?>">
          <button class="btn btn-sm btn-red" name="action" value="delete_user" type="submit">🗑</button>
        </form>
      </td>
    </tr>
    <?php endforeach; ?>
  </table>
</div>

<div class="card">
  <h2>📦 Produits</h2>
  <table>
    <tr><th>ID</th><th>Nom</th><th>Prix</th><th>Stock</th><th>Action</th></tr>
    <?php foreach ($products as $p): ?>
    <tr>
      <td><?= $p['id'] ?></td>
      <td><?= htmlspecialchars($p['name']) ?></td>
      <td><?= number_format($p['price'],2) ?>€</td>
      <td><?= $p['stock'] ?></td>
      <td>
        <form method="POST" style="margin:0" onsubmit="return confirm('Supprimer ?')">
          <input type="hidden" name="pid" value="<?= $p['id'] ?>">
          <button class="btn btn-sm btn-red" name="action" value="delete_product" type="submit">🗑</button>
        </form>
      </td>
    </tr>
    <?php endforeach; ?>
  </table>
</div>

<div class="card">
  <h2>💬 Avis récents</h2>
  <table>
    <tr><th>Produit</th><th>Auteur</th><th>Contenu</th><th>Note</th><th>Action</th></tr>
    <?php foreach ($reviews as $rv): ?>
    <tr>
      <td><?= htmlspecialchars($rv['product_name']) ?></td>
      <td><?= htmlspecialchars($rv['username']) ?></td>
      <td><?= $rv['content'] ?></td>
      <td><?= $rv['rating'] ?>/5</td>
      <td>
        <form method="POST" style="margin:0">
          <input type="hidden" name="rid" value="<?= $rv['id'] ?>">
          <button class="btn btn-sm btn-red" name="action" value="delete_review" type="submit">🗑</button>
        </form>
      </td>
    </tr>
    <?php endforeach; ?>
  </table>
</div>

<div class="card">
  <h2>🛍️ Commandes</h2>
  <table>
    <tr><th>ID</th><th>Client</th><th>Produit</th><th>Qté</th><th>Total</th><th>Date</th></tr>
    <?php foreach ($orders as $o): ?>
    <tr>
      <td><?= $o['id'] ?></td>
      <td><?= htmlspecialchars($o['username']) ?></td>
      <td><?= htmlspecialchars($o['product_name']) ?></td>
      <td><?= $o['quantity'] ?></td>
      <td><?= number_format($o['total'],2) ?>€</td>
      <td><?= $o['created_at'] ?></td>
    </tr>
    <?php endforeach; ?>
  </table>
</div>
<?php require_once 'footer.php'; ?>
