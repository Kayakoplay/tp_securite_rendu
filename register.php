<?php
$title = 'Inscription';
require_once 'header.php';

$error = $ok = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $u = trim($_POST['username'] ?? '');
    $p = $_POST['password'] ?? '';
    $e = trim($_POST['email'] ?? '');
    if ($u && $p && $e) {
        try {
            db()->prepare("INSERT INTO users (username,password,email) VALUES (?,?,?)")
               ->execute([$u, md5($p), $e]);
            $ok = "Compte créé ! <a href='login.php'>Se connecter</a>";
        } catch (Exception $ex) {
            $error = "Ce nom d'utilisateur est déjà pris.";
        }
    } else {
        $error = "Tous les champs sont obligatoires.";
    }
}
?>
<div class="card" style="max-width:400px;margin:0 auto">
  <h1>📋 Inscription</h1>
  <?php if ($error): ?><div class="err"><?= $error ?></div><?php endif; ?>
  <?php if ($ok):    ?><div class="ok"><?= $ok ?></div><?php endif; ?>
  <form method="POST">
    <label style="font-size:13px">Nom d'utilisateur</label>
    <input type="text" name="username">
    <label style="font-size:13px">Email</label>
    <input type="email" name="email">
    <label style="font-size:13px">Mot de passe</label>
    <input type="password" name="password">
    <button class="btn" style="width:100%" type="submit">Créer mon compte</button>
  </form>
</div>
<?php require_once 'footer.php'; ?>
