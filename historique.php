<?php
session_start();
include 'dataconnect.php';


if (!isset($_SESSION['user'])) {
    header("Location: formulaire_connect.php");
    exit();
}

$user_id = (int) $_SESSION['user'];

// sélection par 
$stmt = $mysqlClient->prepare(
    "SELECT * FROM historique 
     WHERE user_id = ? 
     ORDER BY created_at DESC 
     LIMIT 100"
);
$stmt->execute([$user_id]);
$lignes = $stmt->fetchAll(PDO::FETCH_ASSOC);

// je dois grouper les historiques par plante
$groupes = [];
foreach ($lignes as $ligne) {
    $groupes[$ligne['plante_nom']][] = $ligne;
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Historique</title>
        <link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Roboto:ital,wght@0,100..900;1,100..900&family=Saira+Stencil+One&display=swap" rel="stylesheet">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: sans-serif;background: #c1dcc1;; min-height: 100vh; }

        header { background: #9cd89c; color: white; padding: 12px 30px; display: flex; justify-content: space-between; align-items: center;border:1px  solid #9cd89c; border-radius:0px 0px 30px 30px;font-size:15px; font-weight:900; }
        header a { color: black; text-decoration: none; font-size: 14px; opacity: .85;font-size: 15px;font-weight: 900; transition:ease 0.5s;font-family: Verdana, Geneva, Tahoma, sans-serif;}
        strong{font-family: "Saira Stencil One", sans-serif; font-weight: 400;font-style: normal;color:black;transition:ease 0.5s;}
        header a:hover, header strong:hover{color:white;}
        strong { font-family: "Saira Stencil One", sans-serif; font-weight: 400; font-style: normal; font-size: 25px; transition: ease 0.5s;}
        main { max-width: 800px; margin: 30px auto; padding: 0 16px; }
        h1 { color: #2e7d32; margin-bottom: 20px; }

        .vide { text-align: center; color: #888; margin-top: 60px; font-size: 16px; }
        .vide a { color: #2e7d32; }

        /* ── Accordéon plante ─────────────────────────── */
        .plante-section { background: white; border-radius: 12px; margin-bottom: 16px; box-shadow: 0 2px 6px rgba(0,0,0,.07); overflow: hidden; }

        .plante-header { 
            display: flex; 
            justify-content: space-between;
            align-items: center;
            padding: 14px 20px; 
            cursor: pointer; 
            user-select: none; 
            background: #f1f8e9; 
        }
        .plante-header:hover { background: #e8f5e9; }
        .plante-header h2 { font-size: 16px; color: #1b5e20; }
        .plante-header .nb { font-size: 13px; color: #555; }
        .chevron { transition: transform .25s; }
        .ouvert .chevron { transform: rotate(180deg); }

        .echanges { display: none; padding: 0 20px 16px; transition:0.4s;}
        .ouvert .echanges { display: block; }

        /* ── Bulles ───────────────────────────────────── */
        .echange { margin-top: 14px; border-top: 1px solid #eee; padding-top: 12px; }
        .echange:first-child { border-top: none; }
        .date { font-size: 11px; color: #aaa; margin-bottom: 6px; }

        .bulle { display: inline-block; max-width: 90%; padding: 8px 12px;
            border-radius: 10px; font-size: 14px; line-height: 1.5; margin-bottom: 6px; }
        .bulle.question { background: #2e7d32; color: white; border-bottom-right-radius: 2px; }
        .bulle.reponse  { background: #f1f8e9; color: #1b1b1b; border-bottom-left-radius: 2px; display: block; }

        .btn-rejouer { display: inline-block; margin-top: 8px; padding: 6px 14px;
            background: #e8f5e9; color: #1b5e20; border-radius: 20px; font-size: 13px;
            text-decoration: none; border: 1px solid #a5d6a7; cursor: pointer; }
        .btn-rejouer:hover { background: #c8e6c9; }
        strong{font-family: "Saira Stencil One", sans-serif;font-size:25px; font-weight: 400;font-style: normal;}

        footer { text-align: center; padding: 20px; color: #aaa; font-size: 13px; margin-top: 20px; }
        @media screen and (max-width: 996px) {
            header { 
                background: #9cd89c; 
                color: white; 
                padding: 12px 30px; 
                display: flex; 
                justify-content: space-between; 
                align-items: center;
                border:1px  solid #9cd89c; 
                border-radius:0px 0px 30px 30px; 
                gap: 50px;
            }
            /* nav{display:flex; justify-content:space-between;gap: 100px;} */
            strong{
                font-size:18px;
            }
            h1{
                text-align:center;
                font-family: Verdana, Geneva, Tahoma, sans-serif;                
                margin-bottom:20px;
                margin-top:50px;
                margin-bottom:50px;
                color:#222;
                animation: apparition 0.8s ease forwards;
            }
            main{ 
            margin-bottom:250px;
            }
            @keyframes apparition {
                from{
                    opacity: 0;
                    transform:translatey(100px);
                }
                to{
                    opacity: 1;
                    transform:translatey(0px);
                }
                }
            .plante-section{
                animation: apparition 0.8s ease forwards;
                border-radius:20px 20px 0px 20px;
                margin-left:5%;
                width: 90%;
                        }
            .plante-section:active{
                transitions:0.5s;

            }
            }
    </style>
</head>
<body>

<header>
    <strong>WhatAPlant </strong>
    <nav style="display:flex; gap:20px;">
        <a href="scanne.php">Scanner</a>
        <a href="deconnexion.php">Se déconnecter</a>
    </nav>
</header>

<main>
    <h1>Mes analyses</h1>
    

    <?php if (empty($groupes)): ?>
        <div class="vide">
            <p>Aucune analyse enregistrée pour l'instant.</p>
            <p style="margin-top:12px;"><a href="scanne.php"> Scanner une plante</a></p>
        </div>

    <?php else: ?>
        <?php foreach ($groupes as $plante => $echanges): ?>
            <div class="plante-section" onclick="basculer(this)">
                <div class="plante-header">
                    <h2>🌱 <?= htmlspecialchars($plante) ?></h2>
                    <span>
                        <span class="nb"><?= count($echanges) ?> échange(s)</span>
                        &nbsp;
                        <span class="chevron">▼</span>
                    </span>
                </div>

                <div class="echanges">
                    <?php foreach ($echanges as $e): ?>
                        <div class="echange">
                            <div class="date">
                                🕐 <?= date('d/m/Y H:i', strtotime($e['created_at'])) ?>
                            </div>
                            <div>
                                <span class="bulle question">
                                    ❓ <?= htmlspecialchars($e['question']) ?>
                                </span>
                            </div>
                            <div>
                                <span class="bulle reponse">
                                    <?= nl2br(htmlspecialchars($e['reponse'])) ?>
                                </span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</main>

<footer>© WhatAPlant</footer>

<script>
function basculer(section) {
    section.classList.toggle("ouvert");
}
</script>

</body>
</html>
