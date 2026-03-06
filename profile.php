<?php
$title = 'Profil';
require_once 'header.php';
$me = require_login();

$uid = $_GET['uid'] ?? $me['id'];
$db  = db();

$user = $db->query("SELECT * FROM users WHERE id = $uid")->fetch(PDO::FETCH_ASSOC);
if (!$user) { echo '<div class="err">Utilisateur introuvable.</div>'; require_once 'footer.php'; exit; }

$ok = $error = '';
$is_own = ($me['id'] == $uid);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $is_own) {
    $action = $_POST['action'] ?? '';

    if ($action === 'update') {
        $bio   = $_POST['bio']   ?? '';
        $email = $_POST['email'] ?? '';
        $db->query("UPDATE users SET bio='$bio', email='$email' WHERE id=" . $me['id']);
        $ok   = "Profil mis à jour.";
        $user = $db->query("SELECT * FROM users WHERE id = $uid")->fetch(PDO::FETCH_ASSOC);
    }

    if ($action === 'password') {
        $np = $_POST['new_password'] ?? '';
        if (strlen($np) >= 4) {
            $db->query("UPDATE users SET password='" . md5($np) . "' WHERE id=" . $me['id']);
            $ok = "Mot de passe modifié.";
        } else {
            $error = "Mot de passe trop court.";
        }
    }

    if ($action === 'delete') {
        $db->query("DELETE FROM users WHERE id=" . $me['id']);
        session_destroy();
        header('Location: index.php'); exit;
    }
}

$orders = $db->query(
    "SELECT o.*, p.name as product_name FROM orders o
     JOIN products p ON o.product_id = p.id
     WHERE o.user_id = $uid ORDER BY o.created_at DESC"
)->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="card">
  <h1>👤 Profil de <?= htmlspecialchars($user['username']) ?></h1>
  <?php if ($ok): ?><div class="ok"><?= $ok ?></div><?php endif; ?>
  <?php if ($error): ?><div class="err"><?= $error ?></div><?php endif; ?>

  <p><strong>Email :</strong> <?= htmlspecialchars($user['email']) ?></p>
  <p><strong>Rôle :</strong> <?= htmlspecialchars($user['role']) ?></p>
  <p><strong>Solde :</strong> <?= number_format($user['balance'],2) ?> €</p>
  <p><strong>Bio :</strong></p>
  <div style="background:#f8f8f8;padding:10px;border-radius:5px;margin-top:6px">
    <?= $user['bio'] ?: '<em style="color:#aaa">Aucune bio.</em>' ?>
  </div>
</div>

<?php if ($is_own): ?>
<div class="card">
  <h2>✏️ Modifier mon profil</h2>
  <form method="POST">
    <input type="hidden" name="action" value="update">
    <label style="font-size:13px">Email</label>
    <input type="email" name="email" value="<?= htmlspecialchars($user['email']) ?>">
    <label style="font-size:13px">Bio</label>
    <textarea name="bio"><?= htmlspecialchars($user['bio']) ?></textarea>
    <button class="btn" type="submit">💾 Enregistrer</button>
  </form>
  <hr>
  <h2>🔑 Changer le mot de passe</h2>
  <form method="POST">
    <input type="hidden" name="action" value="password">
    <input type="password" name="new_password" placeholder="Nouveau mot de passe">
    <button class="btn" type="submit">Modifier</button>
  </form>
  <hr>
  <h2 style="color:#c0392b">⚠️ Zone dangereuse</h2>
  <form method="POST" onsubmit="return confirm('Supprimer votre compte ?')">
    <input type="hidden" name="action" value="delete">
    <button class="btn btn-red" type="submit">🗑 Supprimer mon compte</button>
  </form>
</div>
<?php endif; ?>

<div class="card">
  <h2>📦 Commandes (<?= count($orders) ?>)</h2>
  <?php if ($orders): ?>
  <table>
    <tr><th>Produit</th><th>Qté</th><th>Total</th><th>Date</th></tr>
    <?php foreach ($orders as $o): ?>
    <tr>
      <td><?= htmlspecialchars($o['product_name']) ?></td>
      <td><?= $o['quantity'] ?></td>
      <td><?= number_format($o['total'],2) ?> €</td>
      <td><?= $o['created_at'] ?></td>
    </tr>
    <?php endforeach; ?>
  </table>
  <?php else: ?>
    <p style="color:#888">Aucune commande.</p>
  <?php endif; ?>
</div>
<?php require_once 'footer.php'; ?>
