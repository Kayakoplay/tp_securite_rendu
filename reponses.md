# Audit de sécurité - tp_securite_rendu

## 1) Vulnérabilités identifiées

### 1.1 Injection SQL (critique)
Dans `login.php`, la requête SQL est construite par concaténation :
```php
SELECT * FROM users WHERE username='$username' AND password='" . md5($password) . "'
```
➡️ Avec un username malveillant, on peut contourner l’authentification (SQL injection) ou exfiltrer des données.

### 1.2 Hachage des mots de passe obsolète (critique)
Les mots de passe sont comparés via `md5()` (sans salt). C’est insuffisant face à des tables arc-en-ciel/dictionnaire.

### 1.3 Fuite de données : base SQLite dans le dépôt (critique)
Le fichier `shop.db` est versionné dans le repo. En contexte real, ça expose potentiellement utilisateurs/comptes, et même en TP, c’est une mauvaise pratique.

### 1.4 XSS (probable)
Les messages/valeurs provenant de la base peuvent être affichés sans `htmlspecialchars(...)`. Un utilisateur peut injecter du HTML/JS.

### 1.5 Absence de protection CSRF (probable)
Les formulaires de type login/register/actions ne semblent pas intégrer de token CSRF.

### 1.6 Sécurité session cookie (probable)
Pas de `session_regenerate_id()` après login ni d’options cookie (`httponly`, `secure`, `samesite`).

## 2) Corrections attendues

### 2.1 Requêtes préparées
Utiliser PDO préparé :
```php
$stmt = db()->prepare('SELECT id, password FROM users WHERE username = :username');
$stmt->execute(['username' => $username]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);
```

### 2.2 Password hashing moderne
À la création/au reset : `password_hash($password, PASSWORD_DEFAULT)`
À l’authent : `password_verify($password, $user['password'])`

### 2.3 Sortie HTML
Tout output venant de l’utilisateur : `echo htmlspecialchars($val, ENT_QUOTES, 'UTF-8');`

### 2.4 CSRF
Stocker un token CSRF en session et le valider à chaque POST.

### 2.5 Base de données
- ne pas la versionner
- la placer hors webroot

### 2.6 Sessions
Après login : `session_regenerate_id(true)` et configuration `session_set_cookie_params(...)`/ini set.

## 3) Tests
- injection classique: `test' OR '1'='1` sur le login
- XSS sur les champs texte
- vérifier le retour à une session propre après login/logout
