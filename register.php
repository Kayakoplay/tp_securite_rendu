<?php
$title = 'Inscription';
require_once 'header.php';

$error = '';
$ok = '';
$u = '';
$e = '';

function csrf_register_token(): string {
    if (empty($_SESSION['csrf_register'])) {
        $_SESSION['csrf_register'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_register'];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $postedToken = $_POST['csrf'] ?? '';
    if (empty($_SESSION['csrf_register']) || !hash_equals($_SESSION['csrf_register'], $postedToken)) {
        $error = "Session expirée, merci de recharger la page.";
    } else {
        $u = trim($_POST['username'] ?? '');
        $p = $_POST['password'] ?? '';
        $e = trim($_POST['email'] ?? '');

        if (!$u || !$p || !$e) {
            $error = "Tous les champs sont obligatoires.";
        } elseif (!filter_var($e, FILTER_VALIDATE_EMAIL)) {
            $error = "Email invalide.";
        } else {
            try {
                $stmt = db()->prepare('INSERT INTO users (username,password,email) VALUES (:username,:password,:email)');
                $stmt->execute([
                    'username' => $u,
                    'password' => password_hash($p, PASSWORD_DEFAULT),
                    'email' => $e,
                ]);
                unset($_SESSION['csrf_register']);
                $ok = "Compte créé ! Vous pouvez maintenant vous connecter.";
            } catch (PDOException $ex) {
                $error = "Ce nom d'utilisateur ou email est déjà pris.";
            } catch (Exception $ex) {
                $error = "Une erreur est survenue.";
            }
        }
    }
}

$csrf = csrf_register_token();
?>

<div class="card" style="max-width:400px;margin:0 auto">
  <h1>📋 Inscription</h1>

  <?php if ($error): ?><div class="err"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></div><?php endif; ?>
  <?php if ($ok): ?><div class="ok"><?php echo htmlspecialchars($ok, ENT_QUOTES, 'UTF-8'); ?></div><?php endif; ?>

  <form method="POST">
    <input type="hidden" name="csrf" value="<?php echo htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8'); ?>">

    <label style="font-size:13px">Nom d'utilisateur</label>
    <input type="text" name="username" value="<?php echo htmlspecialchars($u, ENT_QUOTES, 'UTF-8'); ?>">

    <label style="font-size:13px">Email</label>
    <input type="email" name="email" value="<?php echo htmlspecialchars($e, ENT_QUOTES, 'UTF-8'); ?>">

    <label style="font-size:13px">Mot de passe</label>
    <input type="password" name="password">

    <button class="btn" style="width:100%" type="submit">S'inscrire</button>
  </form>

  <hr>
  <p style="font-size:13px;color:#888;text-align:center">
    Vous avez déjà un compte ? <a href="login.php">Se connecter</a>
  </p>
</div>

<?php require_once 'footer.php'; ?>
