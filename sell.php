<?php
$title = 'Vendre';
require_once 'header.php';
$me = require_login();

$ok = $error = '';
$db = db();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add') {
        $name  = $_POST['name'] ?? '';
        $desc  = $_POST['description'] ?? '';
        $price = floatval($_POST['price'] ?? 0);
        $stock = intval($_POST['stock'] ?? 0);

        if ($name && $price > 0) {
            if (isset($_FILES['image']) && $_FILES['image']['error'] === 0) {
                $filename = $_FILES['image']['name'];
                move_uploaded_file($_FILES['image']['tmp_name'], __DIR__ . '/uploads/' . $filename);
            }
            $db->prepare("INSERT INTO products (name,description,price,stock,seller_id) VALUES (?,?,?,?,?)")
               ->execute([$name, $desc, $price, $stock, $me['id']]);
            $ok = "Produit ajouté !";
        } else {
            $error = "Nom et prix obligatoires.";
        }
    }

    if ($action === 'delete') {
        $pid = $_POST['pid'] ?? 0;
        $db->query("DELETE FROM products WHERE id=$pid");
        $ok = "Produit supprimé.";
    }

    if ($action === 'update_price') {
        $pid      = $_POST['pid'] ?? 0;
        $newprice = $_POST['new_price'] ?? 0;
        $db->query("UPDATE products SET price=$newprice WHERE id=$pid");
        $ok = "Prix mis à jour.";
    }
}

$my_products = $db->query(
    "SELECT * FROM products WHERE seller_id = " . $me['id']
)->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="card">
  <h1>🏪 Espace vendeur</h1>
  <?php if ($ok): ?><div class="ok"><?= $ok ?></div><?php endif; ?>
  <?php if ($error): ?><div class="err"><?= $error ?></div><?php endif; ?>

  <h2>Ajouter un produit</h2>
  <form method="POST" enctype="multipart/form-data">
    <input type="hidden" name="action" value="add">
    <label style="font-size:13px">Nom du produit</label>
    <input type="text" name="name" placeholder="Ex: Clé USB 128Go">
    <label style="font-size:13px">Description</label>
    <textarea name="description" placeholder="Décrivez votre produit..."></textarea>
    <label style="font-size:13px">Prix (€)</label>
    <input type="number" name="price" step="0.01" min="0">
    <label style="font-size:13px">Stock initial</label>
    <input type="number" name="stock" min="0" value="10">
    <label style="font-size:13px">Image du produit</label>
    <input type="file" name="image" style="background:none;border:none;padding:0">
    <button class="btn btn-green" type="submit" style="margin-top:6px">➕ Ajouter</button>
  </form>
</div>

<div class="card">
  <h2>Mes produits (<?= count($my_products) ?>)</h2>
  <?php if ($my_products): ?>
  <table>
    <tr><th>Nom</th><th>Prix</th><th>Stock</th><th>Actions</th></tr>
    <?php foreach ($my_products as $p): ?>
    <tr>
      <td><a href="product.php?id=<?= $p['id'] ?>"><?= htmlspecialchars($p['name']) ?></a></td>
      <td><?= number_format($p['price'],2) ?>€</td>
      <td><?= $p['stock'] ?></td>
      <td style="display:flex;gap:6px">
        <form method="POST" style="margin:0;display:flex;gap:4px">
          <input type="hidden" name="pid" value="<?= $p['id'] ?>">
          <input type="number" name="new_price" value="<?= $p['price'] ?>" step="0.01" style="width:80px;padding:3px;font-size:12px;margin:0">
          <button class="btn btn-sm btn-blue" name="action" value="update_price" type="submit">Modifier</button>
        </form>
        <form method="POST" style="margin:0">
          <input type="hidden" name="pid" value="<?= $p['id'] ?>">
          <button class="btn btn-sm btn-red" name="action" value="delete" type="submit">🗑</button>
        </form>
      </td>
    </tr>
    <?php endforeach; ?>
  </table>
  <?php else: ?>
    <p style="color:#888">Vous n'avez pas encore de produits.</p>
  <?php endif; ?>
</div>
<?php require_once 'footer.php'; ?>
