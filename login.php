<?php
$title = 'Connexion';
require_once 'header.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    $query = "SELECT * FROM users WHERE username='$username' AND password='" . md5($password) . "'";
    $user  = db()->query($query)->fetch(PDO::FETCH_ASSOC);

    if ($user) {
        $_SESSION['uid'] = $user['id'];
        header('Location: index.php');
        exit;
    } else {
        $error = "Identifiants incorrects.";
    }
}
?>
<div class="card" style="max-width:400px;margin:0 auto">
  <h1>🔑 Connexion</h1>
  <?php if ($error): ?><div class="err"><?= $error ?></div><?php endif; ?>
  <form method="POST">
    <label style="font-size:13px">Nom d'utilisateur</label>
    <input type="text" name="username">
    <label style="font-size:13px">Mot de passe</label>
    <input type="password" name="password">
    <button class="btn" style="width:100%" type="submit">Se connecter</button>
  </form>
  <hr>
  <p style="font-size:13px;color:#888;text-align:center">
    Pas de compte ? <a href="register.php">S'inscrire</a>
  </p>
  <p style="font-size:11px;color:#bbb;margin-top:8px;text-align:center">
    alice/alice123 — bob/bob123 — admin/admin
  </p>
</div>
<?php require_once 'footer.php'; ?>
