# from fastapi import FastAPI, UploadFile, File
# from fastapi.responses import StreamingResponse, JSONResponse
# from fastapi.middleware.cors import CORSMiddleware
# import cv2
# import requests
# import base64
# import json
# import os

# app = FastAPI()

# app.add_middleware(
#     CORSMiddleware,
#     allow_origins=["*"],
#     allow_methods=["*"],
#     allow_headers=["*"],
# )

# PLANTNET_API_KEY  = os.getenv("PLANTNET_API_KEY")
# PLANTNET_URL      = "https://my-api.plantnet.org/v2/identify/all"
# GROQ_VISION_URL   = "https://api.groq.com/openai/v1/chat/completions"
# GROQ_API_KEY      = os.getenv("GROQ_API_KEY")
# GEMINI_API_KEY    = "VOTRE_CLE_GEMINI_ICI"
# GEMINI_IMAGE_URL  = "https://generativelanguage.googleapis.com/v1beta/models/imagen-3.0-generate-002:predict"
# SCORE_MIN         = 20.0

# cap = cv2.VideoCapture(0)


# # ── Flux video MJPEG (affichage uniquement) ───────────────────────────────────
# def generate_frames():
#     while True:
#         success, frame = cap.read()
#         if not success:
#             cap.open(0)
#             continue
#         _, buffer = cv2.imencode('.jpg', frame)
#         yield (b'--frame\r\n'
#                b'Content-Type: image/jpeg\r\n\r\n' + buffer.tobytes() + b'\r\n')

# @app.get("/video")
# def video():
#     return StreamingResponse(generate_frames(),
#         media_type="multipart/x-mixed-replace; boundary=frame")


# # ── POST /scan — image envoyee depuis le navigateur ───────────────────────────
# @app.post("/scan")
# async def scan_upload(file: UploadFile = File(...)):
#     content  = await file.read()
#     filepath = "scan_temp.jpg"
#     with open(filepath, "wb") as f:
#         f.write(content)
#     return identify_plant(filepath, content)


# @app.get("/great")
# def great():
#     return "le serveur fonctionne correctement"


# # ── GET /scan/camera — capture depuis la camera OpenCV ───────────────────────
# @app.get("/scan/camera")
# def scan_camera():
#     if not cap.isOpened():
#         cap.open(0)
#     ret, frame = cap.read()
#     if not ret or frame is None:
#         return JSONResponse(status_code=500,
#             content={"success": False, "error": "Impossible de lire la camera"})
#     filepath = "scan_camera.jpg"
#     cv2.imwrite(filepath, frame)
#     with open(filepath, "rb") as f:
#         content = f.read()
#     return identify_plant(filepath, content)


# # ── PlantNet ──────────────────────────────────────────────────────────────────
# def identify_plant(image_path: str, image_bytes: bytes):
#     try:
#         with open(image_path, "rb") as f:
#             r = requests.post(
#                 PLANTNET_URL,
#                 params={"api-key": PLANTNET_API_KEY, "lang": "fr"},
#                 files=[("images", (image_path, f, "image/jpeg"))],
#                 data={"organs": ["auto"]}
#             )
#         if r.status_code == 200:
#             results = r.json().get("results", [])
#             if results:
#                 top   = results[0]
#                 score = round(top["score"] * 100, 1)
#                 if score >= SCORE_MIN:
#                     return {
#                         "success":    True,
#                         "source":     "plantnet",
#                         "scientific": top["species"]["scientificNameWithoutAuthor"],
#                         "common":     top["species"].get("commonNames", ["Inconnu"])[0],
#                         "score":      score,
#                         "family":     top["species"].get("family", {}).get("scientificNameWithoutAuthor", "?")
#                     }
#                 hint = top["species"].get("commonNames", [""])[0] or top["species"]["scientificNameWithoutAuthor"]
#                 return groq_vision_fallback(image_bytes, hint, score)
#             return groq_vision_fallback(image_bytes, None, 0)
#         return {"success": False, "error": f"PlantNet {r.status_code}"}
#     except Exception as e:
#         return {"success": False, "error": str(e)}


# # ── Groq Vision fallback ──────────────────────────────────────────────────────
# def groq_vision_fallback(image_bytes: bytes, hint: str, hint_score: float):
#     try:
#         b64    = base64.b64encode(image_bytes).decode("utf-8")
#         hint_t = f"PlantNet suggere '{hint}' avec {hint_score}% de confiance. " if hint else ""
#         prompt = (
#             f"{hint_t}Identifie cette plante. Reponds UNIQUEMENT en JSON valide : "
#             '{"common":"nom commun","scientific":"nom scientifique",'
#             '"family":"famille","score":70,"confidence":"ta certitude en une phrase"}'
#         )
#         r = requests.post(GROQ_VISION_URL,
#             headers={"Authorization": f"Bearer {GROQ_API_KEY}", "Content-Type": "application/json"},
#             json={"model": "meta-llama/llama-4-scout-17b-16e-instruct",
#                   "messages": [{"role": "user", "content": [
#                       {"type": "image_url", "image_url": {"url": f"data:image/jpeg;base64,{b64}"}},
#                       {"type": "text", "text": prompt}
#                   ]}],
#                   "max_tokens": 300, "temperature": 0.2},
#             timeout=30)
#         if r.status_code == 200:
#             raw    = r.json()["choices"][0]["message"]["content"].strip()
#             raw    = raw.replace("```json","").replace("```","").strip()
#             parsed = json.loads(raw)
#             return {"success": True, "source": "groq_vision",
#                     "common": parsed.get("common","Inconnue"),
#                     "scientific": parsed.get("scientific",""),
#                     "family": parsed.get("family","?"),
#                     "score": parsed.get("score", 0),
#                     "confidence": parsed.get("confidence","")}
#         return {"success": False, "source": "groq_vision", "error": f"Groq Vision {r.status_code}"}
#     except Exception as e:
#         return {"success": False, "source": "groq_vision", "error": str(e)}


# # ── POST /generate-image — Gemini Imagen ─────────────────────────────────────
# @app.post("/generate-image")
# async def generate_image(data: dict):
#     """
#     Body JSON attendu :
#     { "plant_name": "Orgueil de Chine", "scientific": "Delonix regia", "type": "illustration" }
#     type peut etre : illustration | symptomes | medicinale
#     """
#     plant_name = data.get("plant_name", "plante tropicale")
#     scientific = data.get("scientific", "")
#     img_type   = data.get("type", "illustration")

#     prompts = {
#         "illustration": (
#             f"Botanical illustration of {plant_name} ({scientific}), "
#             "detailed scientific drawing, watercolor style, white background, "
#             "showing leaves, flowers and fruits clearly labeled"
#         ),
#         "symptomes": (
#             f"Close-up photo of {plant_name} plant showing visible disease symptoms, "
#             "yellowing leaves, spots, or pest damage, botanical reference photo"
#         ),
#         "medicinale": (
#             f"Herbal medicine infographic for {plant_name}, showing the plant with "
#             "traditional medicinal uses illustrated around it, clean flat design style"
#         )
#     }

#     prompt = prompts.get(img_type, prompts["illustration"])

#     try:
#         r = requests.post(
#             f"{GEMINI_IMAGE_URL}?key={GEMINI_API_KEY}",
#             headers={"Content-Type": "application/json"},
#             json={
#                 "instances": [{"prompt": prompt}],
#                 "parameters": {"sampleCount": 1, "aspectRatio": "1:1"}
#             },
#             timeout=60
#         )
#         if r.status_code == 200:
#             predictions = r.json().get("predictions", [])
#             if predictions and "bytesBase64Encoded" in predictions[0]:
#                 return {
#                     "success":  True,
#                     "image_b64": predictions[0]["bytesBase64Encoded"],
#                     "prompt":   prompt
#                 }
#             return {"success": False, "error": "Aucune image generee par Gemini"}
#         return {"success": False, "error": f"Gemini {r.status_code} : {r.text[:300]}"}
#     except Exception as e:
#         return {"success": False, "error": str(e)}

from fastapi import FastAPI, UploadFile, File
from fastapi.responses import StreamingResponse, JSONResponse
from fastapi.middleware.cors import CORSMiddleware
import cv2
import requests
import base64
import json
import os

app = FastAPI()

app.add_middleware(
    CORSMiddleware,
    allow_origins=["*"],
    allow_methods=["*"],
    allow_headers=["*"],
)

PLANTNET_API_KEY  = os.getenv("PLANTNET_API_KEY")
PLANTNET_ID_URL   = "https://my-api.plantnet.org/v2/identify/all"
PLANTNET_DIS_URL  = "https://my-api.plantnet.org/v2/diseases/identify"
GROQ_API_KEY      = os.getenv("GROQ_API_KEY")
GROQ_VISION_URL   = "https://api.groq.com/openai/v1/chat/completions"
SCORE_MIN_PLANT   = 30.0   # relevé de 20 → 30 pour plus de précision
# Maladie : en dessous de ce seuil le résultat est ignoré (trop incertain)
SCORE_MIN_DISEASE = 25.0

cap = cv2.VideoCapture(0)


# ── Flux video MJPEG (caméra IP) ──────────────────────────────────────────────
def generate_frames():
    while True:
        success, frame = cap.read()
        if not success:
            cap.open(0)
            continue
        _, buffer = cv2.imencode('.jpg', frame)
        yield (b'--frame\r\n'
               b'Content-Type: image/jpeg\r\n\r\n' + buffer.tobytes() + b'\r\n')

@app.get("/video")
def video():
    return StreamingResponse(generate_frames(),
        media_type="multipart/x-mixed-replace; boundary=frame")


# ── Test connexion ─────────────────────────────────────────────────────────────
@app.get("/health")
def health():
    return {"status": "ok", "message": "WhatAPlant API operationnelle"}


# ── POST /scan — image depuis le navigateur ou galerie ────────────────────────
@app.post("/scan")
async def scan_upload(file: UploadFile = File(...)):
    content = await file.read()
    if len(content) == 0:
        return JSONResponse(status_code=400,
            content={"success": False, "error": "Fichier vide recu"})
    filepath = "scan_temp.jpg"
    with open(filepath, "wb") as f:
        f.write(content)
    return pipeline(filepath, content)


# ── GET /scan/camera — capture depuis la caméra OpenCV ───────────────────────
@app.get("/scan/camera")
def scan_camera():
    if not cap.isOpened():
        cap.open(0)
    ret, frame = cap.read()
    if not ret or frame is None:
        return JSONResponse(status_code=500,
            content={"success": False, "error": "Impossible de lire la camera"})
    filepath = "scan_camera.jpg"
    cv2.imwrite(filepath, frame)
    with open(filepath, "rb") as f:
        content = f.read()
    return pipeline(filepath, content)


@app.get("/historique")
def get_historique():
    url = "https://tonsite.byethost.com/api.php"
    
    response = requests.get(url)
    data = response.json()

    # Exemple de traitement
    for item in data:
        item["resume"] = item["plante_nom"] + " - " + str(item["maladie_nom"])

    return data


        # ── Pipeline : identification plante + maladie ────────────────────────────────
def pipeline(image_path: str, image_bytes: bytes) -> dict:
    plant_info   = identify_plant(image_path, image_bytes)
    disease_info = identify_disease(image_path, image_bytes)
    return {**plant_info, "disease": disease_info}


# ── PlantNet : identification de la plante ────────────────────────────────────
def identify_plant(image_path: str, image_bytes: bytes) -> dict:
    try:
        with open(image_path, "rb") as f:
            r = requests.post(
                PLANTNET_ID_URL,
                params={"api-key": PLANTNET_API_KEY, "lang": "fr"},
                files=[("images", ("plante.jpg", f, "image/jpeg"))],
                data={"organs": ["auto"]},
                timeout=30
            )
        if r.status_code == 200:
            results = r.json().get("results", [])
            if results:
                top   = results[0]
                score = round(top["score"] * 100, 1)
                if score >= SCORE_MIN_PLANT:
                    return {
                        "success":    True,
                        "source":     "plantnet",
                        "scientific": top["species"]["scientificNameWithoutAuthor"],
                        "common":     top["species"].get("commonNames", ["Inconnu"])[0],
                        "score":      score,
                        "family":     top["species"].get("family", {}).get("scientificNameWithoutAuthor", "?")
                    }
                hint = top["species"].get("commonNames", [""])[0] or top["species"]["scientificNameWithoutAuthor"]
                return groq_vision_fallback(image_bytes, hint, score)
            return groq_vision_fallback(image_bytes, None, 0)
        return {"success": False, "error": f"PlantNet {r.status_code}"}
    except Exception as e:
        return {"success": False, "error": str(e)}


# ── PlantNet Diseases : identification de la maladie ─────────────────────────
# La réponse réelle retourne :
#   results[0].name  → code EPPO  (ex: "APHISP", "PSDMV0")
#   results[0].score → float 0-1
#   results[0].categories → liste de catégories taxonomiques
# Il n'y a PAS de champ "label" dans la réponse de base.
# Pour obtenir le nom lisible on appelle l'endpoint /v2/taxonomy/{eppo_code}
# OU on demande lang=fr qui peut enrichir la réponse avec un label localisé.
def identify_disease(image_path: str, image_bytes: bytes) -> dict:
    try:
        with open(image_path, "rb") as f:
            r = requests.post(
                PLANTNET_DIS_URL,
                params={"api-key": PLANTNET_API_KEY, "lang": "fr"},
                files=[("images", ("plante.jpg", f, "image/jpeg"))],
                timeout=30
            )

        if r.status_code != 200:
            return {"found": False, "error": f"PlantNet Diseases {r.status_code}"}

        data    = r.json()
        results = data.get("results", [])

        if not results:
            return {"found": False}

        top   = results[0]
        score = round(top.get("score", 0) * 100, 1)

        # Ignorer si score trop faible
        if score < SCORE_MIN_DISEASE:
            return {"found": False}

        # Champ "name" = code EPPO (ex: "APHISP")
        eppo_code  = top.get("name", "")

        # Champ "label" présent uniquement si lang=fr retourne une traduction
        # Sinon on utilise le code EPPO + appel taxonomy pour le nom lisible
        label = top.get("label", "")

        if not label:
            label = get_disease_label(eppo_code)

        categories = top.get("categories", [])
        category   = categories[0] if categories else "?"

        return {
            "found":     True,
            "name":      label or eppo_code,   # nom lisible ou code EPPO en fallback
            "eppo_code": eppo_code,
            "score":     score,
            "category":  category
        }

    except Exception as e:
        return {"found": False, "error": str(e)}


# ── Résolution du nom lisible d'une maladie via son code EPPO ─────────────────
def get_disease_label(eppo_code: str) -> str:
    """
    Appelle l'endpoint PlantNet taxonomy pour obtenir le nom commun
    français d'un agent pathogène à partir de son code EPPO.
    Retourne une chaîne vide si non trouvé.
    """
    if not eppo_code:
        return ""
    try:
        r = requests.get(
            f"https://my-api.plantnet.org/v2/taxonomy/{eppo_code}",
            params={"api-key": PLANTNET_API_KEY, "lang": "fr"},
            timeout=10
        )
        if r.status_code == 200:
            data = r.json()
            # Essayer commonNames en fr puis en fallback
            common = data.get("commonNames", [])
            if common:
                return common[0]
            # Sinon nom scientifique
            return data.get("scientificName", "")
    except Exception:
        pass
    return ""


# ── Groq Vision : fallback si PlantNet < SCORE_MIN_PLANT ─────────────────────
def groq_vision_fallback(image_bytes: bytes, hint: str, hint_score: float) -> dict:
    try:
        b64    = base64.b64encode(image_bytes).decode("utf-8")
        hint_t = f"PlantNet suggere '{hint}' avec {hint_score}% de confiance. " if hint else ""
        prompt = (
            f"{hint_t}Identifie cette plante. Reponds UNIQUEMENT en JSON valide : "
            '{"common":"nom commun","scientific":"nom scientifique",'
            '"family":"famille","score":70,"confidence":"certitude en une phrase"}'
        )
        r = requests.post(
            GROQ_VISION_URL,
            headers={"Authorization": f"Bearer {GROQ_API_KEY}", "Content-Type": "application/json"},
            json={
                "model": "meta-llama/llama-4-scout-17b-16e-instruct",
                "messages": [{"role": "user", "content": [
                    {"type": "image_url", "image_url": {"url": f"data:image/jpeg;base64,{b64}"}},
                    {"type": "text", "text": prompt}
                ]}],
                "max_tokens":  300,
                "temperature": 0.2
            },
            timeout=30
        )
        if r.status_code == 200:
            raw    = r.json()["choices"][0]["message"]["content"].strip()
            raw    = raw.replace("```json", "").replace("```", "").strip()
            parsed = json.loads(raw)
            return {
                "success":    True,
                "source":     "groq_vision",
                "common":     parsed.get("common",     "Inconnue"),
                "scientific": parsed.get("scientific", ""),
                "family":     parsed.get("family",     "?"),
                "score":      parsed.get("score",      0),
                "confidence": parsed.get("confidence", "")
            }
        return {"success": False, "source": "groq_vision", "error": f"Groq Vision {r.status_code}"}
    except Exception as e:
        return {"success": False, "source": "groq_vision", "error": str(e)}
