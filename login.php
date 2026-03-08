<?php
$title = 'Connexion';
require_once 'header.php';

$error = '';

function csrf_login_token(): string {
    if (empty($_SESSION['csrf_login'])) {
        $_SESSION['csrf_login'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_login'];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $postedToken = $_POST['csrf'] ?? '';
    if (empty($_SESSION['csrf_login']) || !hash_equals($_SESSION['csrf_login'], $postedToken)) {
        $error = "Session expirée, merci de recharger la page.";
    } else {
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';

        $stmt = db()->prepare('SELECT id, password FROM users WHERE username = :username');
        $stmt->execute(['username' => $username]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        $authUserId = null;
        if ($user) {
            $hash = $user['password'];
            if (password_verify($password, $hash)) {
                $authUserId = $user['id'];
            } elseif ($hash === md5($password)) {
                // Compatibilité avec les mots de passe existants (seeds), puis migration vers password_hash
                $authUserId = $user['id'];
                $newHash = password_hash($password, PASSWORD_DEFAULT);
                $update = db()->prepare('UPDATE users SET password = :password WHERE id = :id');
                $update->execute(['password' => $newHash, 'id' => $user['id']]);
            }
        }

        if ($authUserId) {
            session_regenerate_id(true);
            $_SESSION['uid'] = $authUserId;
            unset($_SESSION['csrf_login']);
            header('Location: index.php');
            exit;
        } else {
            $error = "Identifiants incorrects.";
        }
    }
}

$csrf = csrf_login_token();
?>

<div class="card" style="max-width:400px;margin:0 auto">
  <h1>🔑 Connexion</h1>
  <?php if ($error): ?><div class="err"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></div><?php endif; ?>
  <form method="POST">
    <input type="hidden" name="csrf" value="<?php echo htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8'); ?>">
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
