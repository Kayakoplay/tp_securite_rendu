Audit de sécurité - TP Sécurité

1) Vulnérabilités trouvées

1.1 Injection SQL (critique)

Dans le fichier `login.php`, la requête SQL est construite directement avec les variables :

```php
SELECT * FROM users WHERE username='$username' AND password='" . md5($password) . "'
```

Le problème est que si quelqu’un met un username malveillant, il peut modifier la requête SQL.
Par exemple il pourrait contourner la connexion ou accéder à des données de la base.

---

1.2 Hachage des mots de passe pas sécurisé (critique)

Les mots de passe sont hashés avec `md5()`.
Aujourd’hui ce n’est plus considéré comme sécurisé car il existe des attaques par dictionnaire ou des tables arc-en-ciel qui permettent de retrouver les mots de passe.

---

1.3 Base de données présente dans le dépôt (critique)

Le fichier `shop.db` est directement dans le repository Git.
Dans un vrai projet ce serait dangereux car n’importe qui pourrait récupérer la base et voir les données des utilisateurs.

Même pour un TP ce n’est pas une bonne pratique.

---

1.4 Possible faille XSS

Certaines valeurs venant de la base ou des formulaires peuvent être affichées directement dans la page.
Si elles ne sont pas protégées avec `htmlspecialchars()`, un utilisateur pourrait injecter du code HTML ou JavaScript.

---

1.5 Pas de protection CSRF

Les formulaires comme le login ou l’inscription ne semblent pas utiliser de token CSRF.
Du coup un attaquant pourrait envoyer une requête à la place de l’utilisateur sans qu’il s’en rende compte.

---

1.6 Sécurité des sessions

Après la connexion, l’identifiant de session n’est pas régénéré avec `session_regenerate_id()`.
De plus les cookies de session ne semblent pas avoir d’options de sécurité comme `httponly`, `secure` ou `samesite`.

---

2) Corrections possibles

2.1 Utiliser des requêtes préparées

Pour éviter les injections SQL, il faut utiliser des requêtes préparées avec PDO :

```php
$stmt = db()->prepare('SELECT id, password FROM users WHERE username = :username');
$stmt->execute(['username' => $username]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);
```

---

2.2 Utiliser un hash de mot de passe moderne

Au lieu de `md5`, il vaut mieux utiliser :

* `password_hash()` pour créer le mot de passe
* `password_verify()` pour vérifier lors de la connexion

---

2.3 Sécuriser l’affichage HTML

Toutes les données venant de l’utilisateur doivent être échappées avant affichage :

```php
echo htmlspecialchars($val, ENT_QUOTES, 'UTF-8');
```

---

2.4 Ajouter une protection CSRF

On peut générer un token CSRF dans la session puis vérifier ce token lors de chaque requête POST.

---

2.5 Gestion de la base de données

ne pas versionner la base de données dans Git
la placer en dehors du dossier accessible par le web

---

2.6 Sécuriser les sessions

Après la connexion :
```php
session_regenerate_id(true);
```
Et configurer correctement les cookies de session (`httponly`, `secure`, `samesite`).

---

3) Tests réalisés

Pour vérifier les failles :

Test d’injection SQL sur le login avec :
  `test' OR '1'='1`

Test XSS sur les champs texte

Vérification que la session est bien renouvelée après login/logout
