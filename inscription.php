<?php
session_start();
include 'dataconnect.php';

$error = "";
$success = "";

if (isset($_POST['inscris'])) {

    $nom    = htmlspecialchars(trim($_POST['nom']));
    $prenom = htmlspecialchars(trim($_POST['prenom']));
    $mail   = htmlspecialchars(trim($_POST['email']));
    $mot_de_passe = $_POST['mot_de_passe'];
    $confirm      = $_POST['cmdp'];

        if (empty($nom) || empty($prenom) || empty($mail) || empty($mot_de_passe)) {
            $error = "Veuillez remplir tous les champs ⚠️";

        } elseif ($mot_de_passe !== $confirm) {
            $error = "Les mots de passe ne correspondent pas ❌";

        } else {
                // Vérifier si l'email existe déjà
                $check = $mysqlClient->prepare("SELECT id FROM user_db WHERE email = ?");
                $check->execute([$mail]);
                if ($check->fetch()) {
                    $error = "Cet email est déjà utilisé";
                } else {
                    $pass = password_hash($mot_de_passe, PASSWORD_DEFAULT);
                    $sql  = "INSERT INTO user_db (nom, prenom, email, mot_de_passe) VALUES (?, ?, ?, ?)";
                    $req  = $mysqlClient->prepare($sql);
                    $req->execute([$nom, $prenom, $mail, $pass]);

                    // ✅ Pas de echo avant header()
                    $_SESSION['success'] = "Inscription réussie ! Connectez-vous. ✅";
                    header('Location: formulaire_connect.php');
                    exit();
                }
        }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inscription</title>
    <style>
       body { font-family: sans-serif; max-width: 400px; margin: 60px auto; padding: 0 16px; background-image:url("Image/tree.jpeg");background-position:center ;background-repeat:no-repeat;background-size:cover;height:85vh;}
        h2   { text-align: center; color:white;}
        input { width: 100%; padding: 10px; margin: 8px 0; box-sizing: border-box; border: 1px solid #ccc; border-radius: 6px; }
        button { width: 100%; padding: 12px; background: #2e7d32; color: #fff; border: none; border-radius: 6px; cursor: pointer; font-size: 15px; }
        button:hover { background: #1b5e20; }
        .error   { color: red; margin-bottom: 10px; }
        a { display: block; text-align: center; margin-top: 14px;color:white; }
    </style>
</head>
<body>
    <h2>Inscription 🌿</h2>

    <?php if ($error): ?>
        <p class="error"><?= $error ?></p>
    <?php endif; ?>
    
	<div>
    <form method="POST">
        <input type="text"     name="nom"           placeholder="Nom"                   required>
        <input type="text"     name="prenom"         placeholder="Prénom"                required>
        <input type="email"    name="email"          placeholder="Email"                 required>
        <input type="password" name="mot_de_passe"   placeholder="Mot de passe"          required>
        <input type="password" name="cmdp"           placeholder="Confirmer mot de passe" required>
        <button type="submit" name="inscris">S'inscrire</button>
    </form>
    </div>

    <a href="formulaire_connect.php">Déjà un compte ? Se connecter</a>
</body>
</html>
