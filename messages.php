<?php
$title = 'Messages';
require_once 'header.php';
$me = require_login();
$db = db();

$ok = $error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $to_name = trim($_POST['to'] ?? '');
    $content = $_POST['content'] ?? '';

    $to = $db->query("SELECT * FROM users WHERE username='$to_name'")->fetch(PDO::FETCH_ASSOC);

    if (!$to) {
        $error = "Utilisateur introuvable.";
    } elseif ($content) {
        $db->prepare("INSERT INTO messages (from_id,to_id,content) VALUES (?,?,?)")
           ->execute([$me['id'], $to['id'], $content]);
        $ok = "Message envoyé à " . htmlspecialchars($to['username']) . ".";
    }
}

$inbox = $db->query(
    "SELECT m.*, u.username as sender FROM messages m
     JOIN users u ON m.from_id = u.id
     WHERE m.to_id = " . $me['id'] . "
     ORDER BY m.created_at DESC"
)->fetchAll(PDO::FETCH_ASSOC);

$sent = $db->query(
    "SELECT m.*, u.username as recipient FROM messages m
     JOIN users u ON m.to_id = u.id
     WHERE m.from_id = " . $me['id'] . "
     ORDER BY m.created_at DESC"
)->fetchAll(PDO::FETCH_ASSOC);
?>
<div class="card">
  <h1>✉️ Messagerie</h1>
  <?php if ($ok): ?><div class="ok"><?= $ok ?></div><?php endif; ?>
  <?php if ($error): ?><div class="err"><?= $error ?></div><?php endif; ?>

  <h2>Nouveau message</h2>
  <form method="POST">
    <label style="font-size:13px">Destinataire (username)</label>
    <input type="text" name="to" placeholder="ex: alice">
    <label style="font-size:13px">Message</label>
    <textarea name="content" placeholder="Votre message..."></textarea>
    <button class="btn" type="submit">📤 Envoyer</button>
  </form>
</div>

<div class="card">
  <h2>📥 Boîte de réception (<?= count($inbox) ?>)</h2>
  <?php foreach ($inbox as $m): ?>
  <div style="border-bottom:1px solid #eee;padding:10px 0">
    <p class="meta"><strong><?= htmlspecialchars($m['sender']) ?></strong> — <?= $m['created_at'] ?></p>
    <div style="margin-top:6px"><?= $m['content'] ?></div>
  </div>
  <?php endforeach; ?>
  <?php if (!$inbox): ?><p style="color:#888">Aucun message reçu.</p><?php endif; ?>
</div>

<div class="card">
  <h2>📤 Messages envoyés (<?= count($sent) ?>)</h2>
  <?php foreach ($sent as $m): ?>
  <div style="border-bottom:1px solid #eee;padding:10px 0">
    <p class="meta">À <strong><?= htmlspecialchars($m['recipient']) ?></strong> — <?= $m['created_at'] ?></p>
    <div style="margin-top:6px"><?= $m['content'] ?></div>
  </div>
  <?php endforeach; ?>
  <?php if (!$sent): ?><p style="color:#888">Aucun message envoyé.</p><?php endif; ?>
</div>
<?php require_once 'footer.php'; ?>
