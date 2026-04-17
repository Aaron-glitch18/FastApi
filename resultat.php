<?php
session_start();
if (!isset($_SESSION['user'])) {
    header("Location: formulaire_connect.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:ital,wght@0,100..900;1,100..900&family=Saira+Stencil+One&display=swap" rel="stylesheet">
    <title>Resultat & Chat — WhatAPlant</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: sans-serif; background: #9ea99C;; min-height: 100vh; display: flex; flex-direction: column; }
        header { background: #394931;display: flex;justify-content: space-between; align-items: center;border-radius:0px 0px 30px 30px;padding: 12px 30px }
        header a { color: #D4D0B9; text-decoration: none; font-size: 14px; opacity: .85;font-size: 15px;font-weight: 900; transition:ease 0.5s;font-family: Verdana, Geneva, Tahoma, sans-serif;}
        /* header a:hover { text-decoration: underline; } */
        strong{font-family: "Saira Stencil One", sans-serif; font-weight: 400;font-style: normal;color:#D4D0B9;transition:ease 0.5s;font-size:25px;width: 400px;}
        header a:hover, header strong:hover{color:white;}
        header div nav{
            display:flex;
            gap:30px;
        }
        
        main { flex: 1; max-width: 900px; width: 100%; margin: 24px auto; padding: 0 16px; }

        #carte-plante { background:#d4d0b9; border-radius: 12px; padding: 20px; margin-bottom: 16px; box-shadow: 0 2px 8px rgba(0,0,0,.08); display: none; transition:0.5s;}
        #carte-plante h2 { color:black; margin-bottom: 6px; font-size: 22px; }
        #nom-scientifique { color: #555; font-style: italic; margin-bottom: 12px; font-size: 14px; }
        .badges { display: flex; flex-wrap: wrap; gap: 8px; margin-bottom: 10px; }
        .badge { display: inline-block; padding: 4px 12px; border-radius: 20px; font-size: 13px; }
        .badge.vert   { background: #e8f5e9; color: #1b5e20; }
        .badge.rouge  { background: #fde8e8; color: #b71c1c; }
        .badge.orange { background: #fff3e0; color: #e65100; }
        .badge.gris   { background: #f5f5f5; color: #555; }
        .badge.bleu   { background: #e3f2fd; color: #0d47a1; }

        #bloc-maladie { display: none; background:#afb59d; border: 1px solid #ffcdd2; border-radius: 8px; padding: 14px 16px; margin-top: 10px; }
        #maladie-titre { font-weight: 600; color: #b71c1c; margin-bottom: 8px; font-size: 15px; }
        #maladie-symptomes { font-size: 13px; color: #555; line-height: 1.6; margin-top: 8px; font-style: italic; }

        #bloc-saine { display: none; background: #f1f8e9; border: 1px solid #c5e1a5; border-radius: 8px; padding: 10px 16px; margin-top: 10px; font-size: 13px; color: #33691e; }

        #chat-wrapper { background: white; border-radius: 12px; box-shadow: 0 2px 8px rgba(0,0,0,.08); overflow: hidden; }
        #chat-box { height: 480px; overflow-y: auto; padding: 16px; display: flex; flex-direction: column; gap: 12px; background:#d4d0b9;}
        .message { max-width: 82%; padding: 10px 14px; border-radius: 12px; line-height: 1.6; font-size: 14px; }
        .message.user      { align-self: flex-end;   background:#afb59d; color: black; border-bottom-right-radius: 2px; }
        .message.assistant { align-self: flex-start; background:#afb59d; color: #1b1b1b; border-bottom-left-radius: 2px; }
        .message.system    { align-self: center; background: #fff3cd; color: #856404; font-size: 13px; border-radius: 8px; max-width: 95%; text-align: center; padding: 8px 16px; }
        #typing { background:#d4d0b9;; font-size: 13px; font-style: italic; padding: 0 16px 10px; display: none; }
        #chat-input-zone { display: flex; gap: 10px; padding: 12px 16px; border-top: 1px solid #e0e0e0; background:#afb59d; }
        #user-msg { flex: 1; padding: 10px 14px; border: 1px solid #ccc; border-radius: 24px; font-size: 14px; outline: none;background:#white; }
        #user-msg:focus { border-color: #3434; }
        #btn-envoyer { padding: 10px 20px; background: #394931; color: white; border: none; border-radius: 24px; cursor: pointer; font-size: 14px; }
        #btn-envoyer:hover { background: #1b5e20; }
        #btn-envoyer:disabled { background: #394931; cursor: not-allowed; }
        footer { text-align: center; padding: 16px; color: #aaa; font-size: 13px; }
        @media  screen and (max-width:996px) {
                body {
                    background: #9ea99C;
                }
                header{
                    background: #394931; 
                    /* padding: 12px 30px;  */
                    display: flex; 
                    flex-direction:column;
                    justify-content: space-between; 
                    align-content: center;
                    border-radius:0px 0px 30px 30px; 
                    /* width: min-content; */
                    padding:none;
                }
                header nav a,strong{
                color:#D4D0B9;
                text-align:center;
                }
            /* #carte-plante h2,#bloc-maladie{color:#394931;} */
                #carte-plante h2{
                    color:black;
                }
                #user-msg{background:white;}/*à revoir*/
            header nav{
                display:flex;
                flex-direction:column;
                justify-content:space-between;
                align-items:center;
                gap:10px;
                margin-top:10px;
                width: 100%;
                /* margin-bottom:10px; */
            }
            header div{
                background:;
                width: 100%;
                margin:-2px 0 0;
                background:#afb59;
            }
            #chat-box,#carte-plante,#typing{
                background:#d4d0b9;
                color:black;
            }
            .message.user,#bloc-maladie,.message.assistant{
                background:#afb59d;
                border:none;
                color:black;
            }
            #chat-input-zone{
                background:#afb59d;
            }
            #btn-envoyer{
                background: #394931; 
                color:#D4D0B9;
                font-weight:bold;
            }
            #btn-envoyer:disabled { background: #394931; cursor: not-allowed; }
        }
       
    </style>
</head>
<body>

<header>
    <strong>WhatAPlant - Resultat</strong>
    <div>
        <nav >
            <a href="scanne.php">Scanner</a>
            <a href="historique.php">Historique</a>
            <a href="deconnexion.php">Déconnexion</a>
        </nav>
    </div>
</header>

<main>
    <div id="carte-plante">
        <h2 id="nom-commun"></h2>
        <p id="nom-scientifique"></p>
        <div class="badges">
            <span class="badge vert" id="badge-famille"></span>
            <span class="badge gris" id="badge-score"></span>
            <span class="badge gris" id="badge-source" style="display:none;"></span>
        </div>
        <div id="bloc-maladie">
            <div id="maladie-titre"></div>
            <div class="badges" id="badges-maladie"></div>
            <div id="maladie-symptomes"></div>
        </div>
        <div id="bloc-saine">Aucune maladie detectee — la plante semble en bonne sante.</div>
    </div>

    <div id="chat-wrapper">
        <div id="chat-box">
            <div class="message system">Chargement de l analyse...</div>
        </div>
        <div id="typing">Groq reflechit...</div>
        <div id="chat-input-zone">
            <input type="text" id="user-msg" placeholder="Posez une question sur cette plante..." disabled>
            <button id="btn-envoyer" onclick="envoyerQuestion()" disabled>Envoyer</button>
        </div>
    </div>
</main>

<footer><p>© WhatAPlant — Tous droits reserves</p></footer>

<script>
var historique = [{
    role: "system",
    content: "Tu es un expert botaniste et herboriste. Pour chaque plante qu on t identifie, tu fournis en paragraphes continus en listes ni caracteres speciaux : 1. L etat general de sante de la plante en tenant compte de la maladie detectee si elle est presente, 2. Les symptomes visibles de la maladie et comment les reconnaitre sur la plante, 3. Si la plante est comestible et comment preparer ses parties saines en evitant les parties malades, 4. Ses vertus medicinales et son mode d utilisation. Si une maladie est mentionnee, oriente toujours ta reponse sur son traitement, son impact sur la consommation et les precautions a prendre. Si une question ne concerne pas les plantes reponds que c est hors de ton domaine, toute réponse fourni dois avoir une référence réel rien ne doit être inventé."
}];

window.addEventListener("DOMContentLoaded", function() {
    var raw = localStorage.getItem("resultat_plante");
    if (!raw) {
        viderChat();
        ajouterMsg("system", "Aucun resultat. Retournez scanner une plante.");
        return;
    }

    var data = JSON.parse(raw);
    viderChat();
    afficherCarte(data);

    if (!data.success) {
        ajouterMsg("system", "Plante non identifiee : " + (data.error || "erreur inconnue"));
        return;
    }

    var nomPlante   = data.common || data.scientific || "plante inconnue";
    var disease     = data.disease || {};
    var ctxMaladie  = construireContexteMaladie(disease);

    // Prompt complet : nom plante + état sanitaire + maladie si présente
    var prompt = "La plante identifiee sur la photo est \"" + nomPlante + "\""
               + " (nom scientifique : " + (data.scientific || "inconnu") + ")"
               + ", score de confiance PlantNet : " + (data.score || "?") + "%."
               + ctxMaladie
               + " Fournis une analyse complete et detaillee.";

    // Message affiché dans le chat
    var labelUser = "Analyse : " + nomPlante;
    if (disease.found) labelUser += " | Maladie : " + disease.name;
    ajouterMsg("user", labelUser);

    envoyerAGroq(prompt, nomPlante, data.family, disease.found ? disease.name : "");
});


// ── Affichage de la carte identification ──────────────────────────────────────
function afficherCarte(data) {
    if (!data.common && !data.scientific) return;

    document.getElementById("carte-plante").style.display = "block";
    document.getElementById("nom-commun").textContent       = data.common || data.scientific || "Plante inconnue";
    document.getElementById("nom-scientifique").textContent = data.scientific || "";
    document.getElementById("badge-famille").textContent    = "Famille : " + (data.family || "?");
    document.getElementById("badge-score").textContent      = "Confiance : " + (data.score || "?") + "%";

    if (data.source === "groq_vision") {
        var s = document.getElementById("badge-source");
        s.textContent = "Identifie par IA"; s.className = "badge bleu"; s.style.display = "inline-block";
    }

    var d = data.disease || {};

    if (d.found) {
        document.getElementById("bloc-maladie").style.display = "block";
        document.getElementById("maladie-titre").textContent  = "Maladie detectee : " + d.name;

        var b = document.getElementById("badges-maladie");
        b.innerHTML = "";
        if (d.category && d.category !== "?")
            b.innerHTML += '<span class="badge orange">' + d.category + '</span>';
        /*if (d.score && d.score > 0)
            b.innerHTML += '<span class="badge rouge">Certitude PlantNet : ' + d.score + '%</span>';*/
        if (d.certitude)
            b.innerHTML += '<span class="badge ' + ({elevee:"rouge",moyenne:"orange",faible:"gris"}[d.certitude]||"gris") + '">' + d.certitude + '</span>';
        if (d.source === "plantnet_diseases")
            b.innerHTML += '<span class="badge vert">PlantNet Diseases</span>';
        else if (d.source === "groq_vision")
            b.innerHTML += '<span class="badge bleu">Analyse visuelle IA</span>';
        if (d.eppo_code && d.eppo_code !== d.name)
            b.innerHTML += '<span class="badge gris">Code EPPO : ' + d.eppo_code + '</span>';
        if (d.symptomes)
            document.getElementById("maladie-symptomes").textContent = d.symptomes;

    } else if (d.saine) {
        document.getElementById("bloc-saine").style.display = "block";
    }
}


// ── Contexte maladie injecté dans le prompt Groq ──────────────────────────────
function construireContexteMaladie(d) {
    if (!d || !d.found) {
        return " Aucune maladie n a ete detectee sur la photo, la plante semble en bonne sante.";
    }

    var ctx = " IMPORTANT — Une maladie a ete detectee sur la photo de cette plante : \""
            + d.name + "\"";

    if (d.eppo_code && d.eppo_code !== d.name)
        ctx += " (code EPPO : " + d.eppo_code + ")";

    if (d.category && d.category !== "?")
        ctx += ", de type " + d.category;

    if (d.source === "plantnet_diseases" && d.score > 0)
        ctx += ", identifiee par PlantNet Diseases avec " + d.score + "% de certitude";
    else if (d.source === "groq_vision" && d.certitude)
        ctx += ", detectee visuellement par analyse IA avec certitude " + d.certitude;

    if (d.symptomes)
        ctx += ". Symptomes visibles sur la photo : " + d.symptomes;

    ctx += ". En te basant sur ce nom de maladie, developpe : "
         + "(1) une description precise de cette maladie et son agent causateur, "
         + "(2) comment reconnaitre ses symptomes sur cette espece, "
         + "(3) les traitements biologiques et chimiques disponibles, "
         + "(4) l impact sur la comestibilite de la plante et les precautions, "
         + "(5) comment prevenir la propagation.";

    return ctx;
}


// ── Envoi vers Groq ───────────────────────────────────────────────────────────
function envoyerAGroq(message, nomPlante, famille, maladie) {
    historique.push({ role: "user", content: message });
    document.getElementById("typing").style.display = "block";
    document.getElementById("btn-envoyer").disabled = true;
    document.getElementById("user-msg").disabled    = true;

    fetch("groq_proxy.php", {
        method: "POST",
        credentials: "include",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ messages: historique })
    })
    .then(function(res) {
        return res.json().then(function(j) { return { ok: res.ok, s: res.status, j: j }; });
    })
    .then(function(r) {
        if (!r.ok) {
            ajouterMsg("system", "Erreur Groq (" + r.s + ") : " + (r.j.detail || r.j.error || "?"));
            historique.pop(); return;
        }
        var rep = r.j.choices && r.j.choices[0] && r.j.choices[0].message && r.j.choices[0].message.content;
        if (!rep) { ajouterMsg("system", "Reponse vide. Reessayez."); return; }

        historique.push({ role: "assistant", content: rep });
        ajouterMsg("assistant", rep);

        // Sauvegarde dans historique avec famille et maladie
        fetch("save_historique.php", {
            method: "POST", credentials: "include",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify({
                plante: nomPlante || "Inconnue",
                question: message,
                reponse: rep,
                famille: famille || "",
                maladie: maladie || ""
            })
        });
    })
    .catch(function(e) { ajouterMsg("system", "Erreur : " + e.message); })
    .finally(function() {
        document.getElementById("typing").style.display = "none";
        document.getElementById("btn-envoyer").disabled = false;
        document.getElementById("user-msg").disabled    = false;
        document.getElementById("user-msg").focus();
    });
}

function envoyerQuestion() {
    var input = document.getElementById("user-msg");
    var msg   = input.value.trim();
    if (!msg) return;
    ajouterMsg("user", msg);
    input.value = "";
    var data = JSON.parse(localStorage.getItem("resultat_plante") || "{}");
    var nomPlante = data.common || data.scientific || "Plante";
    var famille = data.family || "";
    var maladie = (data.disease && data.disease.found) ? data.disease.name : "";
    envoyerAGroq(msg, nomPlante, famille, maladie);
}

document.addEventListener("DOMContentLoaded", function() {
    document.getElementById("user-msg").addEventListener("keydown", function(e) {
        if (e.key === "Enter") envoyerQuestion();
    });
});

function viderChat() { document.getElementById("chat-box").innerHTML = ""; }
function ajouterMsg(role, texte) {
    var box = document.getElementById("chat-box");
    var div = document.createElement("div");
    div.className = "message " + role;
    div.innerHTML = texte.replace(/\n/g, "<br>");
    box.appendChild(div);
    box.scrollTop = box.scrollHeight;
}
</script>
</body>
</html>