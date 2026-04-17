<?php
session_start();
include 'dataconnect.php';

$error   = "";
$success = "";

// Message venant de l'inscription
if (isset($_SESSION['success'])) {
    $success = $_SESSION['success'];
    unset($_SESSION['success']);
}

// Déjà connecté → rediriger
if (isset($_SESSION['user'])) {
    header("Location: scanne.php");
    exit();
}

if (isset($_POST['connect'])) {

    if (!empty($_POST['email']) && !empty($_POST['mot_de_passe'])) {

        $mail     = htmlspecialchars(trim($_POST['email']));
        $password = $_POST['mot_de_passe'];

        $stmt = $mysqlClient->prepare('SELECT * FROM user_db WHERE email = ?');
        $stmt->execute([$mail]);
        $user = $stmt->fetch();

        if ($user) {
            if (password_verify($password, $user['mot_de_passe'])) {
                $_SESSION['user']   = $user['id'];
                $_SESSION['prenom'] = $user['prenom'];
                header("Location: scanne.php");
                exit();
            } else {
                $error = "Mot de passe incorrect ❌";
            }
        } else {
            $error = "Aucun compte avec cet email ❌";
        }

    } else {
        $error = "Veuillez remplir tous les champs ";
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion</title>
    <style>
       body { font-family: sans-serif; max-width: 400px; margin: 60px auto; padding: 0 16px; background-image:url("Image/tree.jpeg");background-position:center ;background-repeat:no-repeat;background-size:cover;height:85vh;}
        h2     { text-align: center; color:white;}
        input  { width: 100%; padding: 10px; margin: 8px 0; box-sizing: border-box; border: 1px solid #ccc; border-radius: 6px; }
        button { width: 100%; padding: 12px; background: #1b5e20;color: #fff; border: none; border-radius: 6px; cursor: pointer; font-size: 15px; }
        button:hover { background: #2e7d32;  }
        .error   { color: red;   margin-bottom: 10px; }
        .success { color: green; margin-bottom: 10px; }
        a { display: block; text-align: center; margin-top: 14px; color:white;text-decoration:none;font-style:italic;}
    </style>
</head>
<body>
    <h2>Connexion</h2>

    <?php if ($error):   ?><p class="error">  <?= $error   ?></p><?php endif; ?>
    <?php if ($success): ?><p class="success"><?= $success ?></p><?php endif; ?>

    <form method="POST">
        <input type="email"    name="email"        placeholder="Votre email"    required>
        <input type="password" name="mot_de_passe" placeholder="Mot de passe"  required>
        <a href="">mot de passe oublié</a>
        <br>
        <button type="submit" name="connect">Se connecter</button>
    </form>

    <a href="inscription.php">Pas encore de compte ? S'inscrire</a>
</body>
</html>
