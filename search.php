<?php
$title = 'Recherche';
require_once 'header.php';

$qRaw = $_GET['q'] ?? '';
$q = trim($qRaw);
$sort = $_GET['sort'] ?? 'name';
$results = [];

if ($q !== '') {
    $allowedSort = ['name', 'price'];
    if (!in_array($sort, $allowedSort, true)) {
        $sort = 'name';
    }

    $like = '%' . $q . '%';
    $stmt = db()->prepare(
        "SELECT p.*, u.username as seller FROM products p
         JOIN users u ON p.seller_id = u.id
         WHERE p.name LIKE :like OR p.description LIKE :like
         ORDER BY $sort ASC"
    );
    $stmt->execute(['like' => $like]);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>
<div class="card">
  <h1>🔍 Recherche</h1>
  <form method="GET">
    <div style="display:flex;gap:10px;margin-bottom:14px">
      <input type="text" name="q" value="<?php echo htmlspecialchars($qRaw, ENT_QUOTES, 'UTF-8'); ?>" placeholder="Rechercher un produit..." style="margin:0">
      <select name="sort" style="width:auto;margin:0">
        <option value="name" <?php echo $sort==='name'?'selected':''; ?>>Nom</option>
        <option value="price" <?php echo $sort==='price'?'selected':''; ?>>Prix</option>
      </select>
      <button class="btn" type="submit">Chercher</button>
    </div>
  </form>

  <?php if ($q !== ''): ?>
    <p style="font-size:13px;color:#888;margin-bottom:14px">
      <?php echo count($results); ?> résultat(s) pour : <?php echo htmlspecialchars($qRaw, ENT_QUOTES, 'UTF-8'); ?>
    </p>
    <?php if ($results): ?>
    <div class="grid">
      <?php foreach ($results as $p): ?>
      <div class="product-card">
        <h2><?php echo htmlspecialchars($p['name'], ENT_QUOTES, 'UTF-8'); ?></h2>
        <div class="price"><?php echo number_format($p['price'],2); ?> €</div>
        <a href="product.php?id=<?php echo (int)$p['id']; ?>" class="btn btn-sm">Voir →</a>
      </div>
      <?php endforeach; ?>
    </div>
    <?php else: ?>
      <p style="color:#888">Aucun résultat.</p>
    <?php endif; ?>
  <?php endif; ?>
</div>
<?php require_once 'footer.php'; ?>
