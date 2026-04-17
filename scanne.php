<?php
session_start();
if (!isset($_SESSION['user'])) {
    header("Location: formulaire_connect.php");
    exit();
}
$prenom = isset($_SESSION['prenom']) ? htmlspecialchars($_SESSION['prenom']) : "Utilisateur";
?>
<!DOCTYPE html>
    <html lang="fr">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Scanner une plante</title>
        <!-- <link rel="stylesheet" href="stylesoutenance.css"> -->
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Roboto:ital,wght@0,100..900;1,100..900&family=Saira+Stencil+One&family=Sekuya&display=swap" rel="stylesheet">        <style>
/* ── Global ─────────────────────────────── */
* { box-sizing: border-box; margin: 0; padding: 0; }
body { 
    font-family: sans-serif; 
    background: #c1dcc1; 
    min-height: 100vh; 
    display: flex; 
    flex-direction: column; 
}
canvas { display: none; }

/* ── Header ─────────────────────────────── */
header { 
    background: #9cd89c; 
    color: black; 
    padding: 12px 24px; 
    display: flex; 
    align-items: center; 
    justify-content: space-between;
    border:1px  solid #9cd89c; 
    border-radius:0px 0px 30px 30px;
}
header a { 
    transition: ease 0.5s; 
    color: black; 
    text-decoration: none; 
    font-size: 15px;
    font-weight: 900; 
    font-family: Verdana, Geneva, Tahoma, sans-serif;
}
strong { 
    font-family: "Saira Stencil One", sans-serif; 
    font-weight: 400; 
    font-style: normal; 
    font-size: 25px; 
    transition: ease 0.5s;
}
header a:hover, header span:hover, header strong:hover { color: white;}

/* ── Main / Titres ──────────────────────── */
main { 
    flex: 1; 
    max-width: 640px; 
    margin: 24px auto; 
    padding: 0 16px; 
    width: 100%; 
}
@keyframes apparition-left {
    from{
        opacity: 0;
        transform:translateX(-100px);
    }
    to{
        opacity: 1;
        transform:translateX(0px);
    }
}
h2 { 
    color: #333; 
    text-align: center; 
    margin-bottom: 16px; 
    font-weight: 900; 
    letter-spacing: -1px; 
    font-size: 30px; 
    font-family: "Sekuya", system-ui; 
    font-weight: 400; 
    font-style: normal;
    animation:apparition-left 1s ease-out forwards;  
}
@keyframes apparition-right {
    from{
        opacity: 0;
        transform:translateX(100px);
    }
    to{
        opacity: 1;
        transform:translateX(0px);
    }
}
.consigne{
    font-style:italic;
    font-size:15px; 
    text-align:center; 
    margin-bottom:20px;
    color:gray;
    animation: apparition-right 1s ease-out forwards;
}

/* ── Zone caméra ────────────────────────── */
@keyframes scale {
    from{
        opacity: 0;
        transform:scale(0);
    }
    to{
        opacity:1;
        transform:scale(1);
    }
}
#camera-zone { 
    position: relative; 
    width: 100%; 
    background: #000; 
    border-radius: 30px; 
    overflow: hidden; 
    margin-bottom: 16px; 
    /* animation:scale 0.5s ease forwards; */
}
#camera-video { width: 100%; display: block; border-radius: 12px; }
#camera-placeholder { 
    display: none; 
    width: 100%; 
    height: 300px; 
    background: #1a1a1a; 
    border-radius: 12px; 
    align-items: center; 
    justify-content: center; 
    flex-direction: column; 
    color: #aaa; 
    font-size: 14px; 
    gap: 8px; 
}
#mjpeg-view { width: 100%; display: none; border-radius: 12px; }
#preview-img { 
    width: 200px; 
    display: none; 
    border-radius: 12px; 
    margin-bottom: 10px; 
    border: 2px solid #2e7d32; 
    margin: auto; 
}

/* ── Boutons / Actions ──────────────────── */
.actions { 
    display: flex; 
    justify-content: space-around; 
    gap: 16px; 
    flex-wrap: wrap; 
    margin-bottom: 20px; 
}
.btn-action { 
    display: flex; 
    flex-direction: column; 
    align-items: center; 
    gap: 6px; 
    background: white; 
    border: 2px solid white; 
    border-radius: 50%; 
    cursor: pointer; 
    font-size: 13px; 
    color: #2e7d32; 
    transition: ease-out 0.4s; 
    width: 10%; 
}
.btn-action:hover { transform: translateY(-5px); }
.btn-action .icon img { width: 50px; padding: 5px; }
.btn-action:disabled { opacity: .4; cursor: not-allowed; }

/* ── Statut ─────────────────────────────── */
#status { 
    text-align: center; 
    font-size: 14px; 
    color: #555; 
    min-height: 24px; 
    margin-bottom: 12px; 
}
#status.loading { color: #2e7d32; font-weight: 500; }
#status.error { color: #c62828; }

/* ── Mode caméra ────────────────────────── */
/* .mode-toggle { 
    display: flex; 
    gap: 10px; 
    justify-content: center; 
    margin-bottom: 14px; 
}
.mode-btn { 
    padding: 6px 16px; 
    border-radius: 20px; 
    border: 1px solid #2e7d32; 
    background: white; 
    color: #2e7d32; 
    cursor: pointer; 
    font-size: 13px; 
}
.mode-btn.active { background: #2e7d32; color: white; } */

/* ── Footer ─────────────────────────────── */
footer { 
    text-align: center; 
    padding: 16px; 
    color: #959090; 
    font-size: 15px; 
    margin-top: -30px; 
}
        </style>
    </head>
    <body>

    <header>
        <strong>WhatAPlant</strong>
        <nav style="display:flex;gap:20px;align-items:center;">
            <a href="historique.php"style="font-weight:bold;">Historique</a>
            <span style="font-size:13px;"style="font-weight:600;font-size:13px;"fon>Bonjour <?= $prenom ?></span>
            <a href="deconnexion.php"style="font-weight:600;">Déconnexion</a>
            </nav>
        </header>

        <main>
            <h2>Capturez une plante</h2>

            <!-- Bascule mode -->
            <p class="consigne">Présentez une plante et apprenez-en d'elle</p>

        <!-- Zone d'affichage -->
        <div id="camera-zone">
            <!-- Mode webcam : getUserMedia -->
            <video id="camera-video" autoplay playsinline></video>
            <!-- Mode flux MJPEG FastAPI (affichage seul) -->
            <img id="mjpeg-view" src="" alt="Flux camera IP">
            <!-- Placeholder si rien ne fonctionne -->
            <div id="camera-placeholder">
                <span style="font-size:40px;">📷</span>
                <span>Camera non disponible</span>
            </div>
        </div>

        <canvas id="canvas"></canvas>
        <img id="preview-img" alt="Apercu capture">

        <div id="status"></div>

        <!-- Actions -->
        <div class="actions">
                    <button class="btn-action" onclick="window.location.href='historique.php'">
                <span class="icon"><img src="Image\historique.jpeg"></span>
                <!-- <span>Historique</span> -->
            </button>

            <button class="btn-action" id="btn-capturer" onclick="capturer()">
                <span class="icon"><img src="Image\scan.jpeg" title="capturer une image"></span>
                <!-- <span>Scanner</span> -->
            </button>

            <label class="btn-action" for="input-galerie">
                <span class="icon"><img src="Image\galerie.jpeg" title="téléchargé une imge"></span>
                <!-- <span>Galerie</span> -->
            </label>
            <input type="file" id="input-galerie" accept="image/*" style="display:none;" onchange="envoyerFichier(this)">
        </div>
    </main>

    <footer>©WhatAPlant</footer>

    <script>
        var modeActuel = 'webcam';
        var streamWebcam = null;

        // ── Demarrage webcam ──────────────────────────────────────────────────────────
        function demarrerWebcam() {
            var video = document.getElementById('camera-video');
            if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
                afficherPlaceholder("getUserMedia non supporte par ce navigateur");
                return;
            }
            navigator.mediaDevices.getUserMedia({ video: { facingMode: "environment" }, audio: false })
            .then(function(stream) {
                streamWebcam = stream;
                video.srcObject = stream;
                video.style.display = 'block';
                document.getElementById('mjpeg-view').style.display = 'none';
                document.getElementById('camera-placeholder').style.display = 'none';
                setStatut('');
            })
            .catch(function(err) {
                console.warn("Webcam:", err.message);
                afficherPlaceholder("Webcam non accessible : " + err.message);
            });
        }

        function arreterWebcam() {
            if (streamWebcam) {
                streamWebcam.getTracks().forEach(function(t) { t.stop(); });
                streamWebcam = null;
            }
        }

        function afficherPlaceholder(msg) {
            document.getElementById('camera-video').style.display = 'none';
            document.getElementById('mjpeg-view').style.display = 'none';
            var ph = document.getElementById('camera-placeholder');
            ph.style.display = 'flex';
            if (msg) ph.querySelector('span:last-child').textContent = msg;
        }

        // ── Bascule mode ──────────────────────────────────────────────────────────────
        function setMode(mode) {
            modeActuel = mode;
            document.getElementById('btn-mode-webcam').classList.toggle('active', mode === 'webcam');
            document.getElementById('btn-mode-flux').classList.toggle('active', mode === 'flux');

            if (mode === 'webcam') {
                arreterWebcam();
                document.getElementById('mjpeg-view').style.display = 'none';
                demarrerWebcam();
            } else {
                arreterWebcam();
                document.getElementById('camera-video').style.display = 'none';
                document.getElementById('camera-placeholder').style.display = 'none';
                document.getElementById('mjpeg-view').style.display = 'block';
                setStatut('Flux camera IP affiche (capture via bouton Scanner)');
            }
        }

        // ── Capture ───────────────────────────────────────────────────────────────────
        function capturer() {
            if (modeActuel === 'webcam') {
                capturerWebcam();
            } else {
                // Mode flux IP : demander a FastAPI de capturer directement
                capturerViaServeur();
            }
        }

        function capturerWebcam() {
            var video  = document.getElementById('camera-video');
            var canvas = document.getElementById('canvas');
            if (!streamWebcam || video.videoWidth === 0) {
                setStatut('Webcam non prete. Attendez quelques secondes.', 'error');
                return;
            }
            canvas.width  = video.videoWidth;
            canvas.height = video.videoHeight;
            canvas.getContext('2d').drawImage(video, 0, 0);

            var preview = document.getElementById('preview-img');
            preview.src = canvas.toDataURL('image/jpeg');
            preview.style.display = 'block';

            canvas.toBlob(function(blob) {
                envoyerBlob(blob);
            }, 'image/jpeg', 0.9);
        }

        function capturerViaServeur() {
            // Le serveur FastAPI capture directement depuis OpenCV
            setStatut('Capture en cours...', 'loading');
            document.getElementById('btn-capturer').disabled = true;

            fetch('https://fastapi-mwx7.onrender.com/scan/camera', { method: 'GET' })
            .then(function(r) { return r.json(); })
            .then(function(data) {
                traiterResultat(data);
            })
            .catch(function(err) {
                setStatut('Erreur serveur : ' + err.message, 'error');
                document.getElementById('btn-capturer').disabled = false;
            });
        }

        // ── Envoi galerie ─────────────────────────────────────────────────────────────
        function envoyerFichier(input) {
            var file = input.files[0];
            if (!file) return;
            var reader = new FileReader();
            reader.onload = function(e) {
                var preview = document.getElementById('preview-img');
                preview.src = e.target.result;
                preview.style.display = 'block';
            };
            reader.readAsDataURL(file);
            envoyerBlob(file);
        }

        // ── Envoi vers FastAPI /scan ──────────────────────────────────────────────────
        function envoyerBlob(blob) {
            setStatut('Analyse en cours...', 'loading');
            document.getElementById('btn-capturer').disabled = true;

            var formData = new FormData();
            formData.append('file', blob, 'plante.jpg');

            fetch('https://fastapi-mwx7.onrender.com/scan', { method: 'POST', body: formData })
            .then(function(r) {
                if (!r.ok) throw new Error('Serveur : ' + r.status);
                return r.json();
            })
            .then(function(data) { traiterResultat(data); })
            .catch(function(err) {
                setStatut('Erreur : ' + err.message, 'error');
                document.getElementById('btn-capturer').disabled = false;
            });
        }

        // ── Traitement resultat ───────────────────────────────────────────────────────
        function traiterResultat(data) {
            document.getElementById('btn-capturer').disabled = false;
            if (!data.success) {
                setStatut('Non identifie : ' + (data.error || 'erreur inconnue'), 'error');
                return;
            }
            setStatut('Identifie : ' + data.common + ' (' + data.score + '%)');
            localStorage.setItem('resultat_plante', JSON.stringify(data));
            setTimeout(function() { window.location.href = 'resultat.php'; }, 800);
        }

        function setStatut(msg, type) {
            var el = document.getElementById('status');
            el.textContent = msg;
            el.className   = type || '';
        }

        // ── Init ──────────────────────────────────────────────────────────────────────
        window.addEventListener('DOMContentLoaded', function() {
            demarrerWebcam();
        });
    </script>
    </body>
</html>