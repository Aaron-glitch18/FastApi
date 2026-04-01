from fastapi import FastAPI, UploadFile, File
from fastapi.responses import JSONResponse
from fastapi.middleware.cors import CORSMiddleware
import requests
import base64
import json

app = FastAPI()

# Remplacer par l'URL reelle apres creation du compte PythonAnywhere
ALLOWED_ORIGIN = "https://tonnom.pythonanywhere.com"

app.add_middleware(
    CORSMiddleware,
    allow_origins=[ALLOWED_ORIGIN, "http://localhost", "http://127.0.0.1"],
    allow_methods=["POST", "GET", "OPTIONS"],
    allow_headers=["*"],
)

PLANTNET_API_KEY = "2b10oJNny0uLqvvFEwmaPqGC4O"
PLANTNET_URL     = "https://my-api.plantnet.org/v2/identify/all"
GROQ_API_KEY     = "votre clé groq"
GROQ_VISION_URL  = "https://api.groq.com/openai/v1/chat/completions"
SCORE_MIN        = 20.0

# Pas de camera sur PythonAnywhere (serveur distant)
# Le flux /video et /scan/camera sont supprimes


# ── POST /api/scan ─────────────────────────────────────────────────────────────
@app.post("/scan")
async def scan_upload(file: UploadFile = File(...)):
    content = await file.read()
    if len(content) == 0:
        return JSONResponse(status_code=400,
            content={"success": False, "error": "Fichier vide recu"})
    filepath = "/tmp/scan_temp.jpg"
    with open(filepath, "wb") as f:
        f.write(content)
    return identify_plant(filepath, content)


# ── PlantNet ───────────────────────────────────────────────────────────────────
def identify_plant(image_path: str, image_bytes: bytes):
    try:
        with open(image_path, "rb") as f:
            r = requests.post(
                PLANTNET_URL,
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
                if score >= SCORE_MIN:
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
        return {"success": False, "error": f"PlantNet {r.status_code} : {r.text[:200]}"}
    except Exception as e:
        return {"success": False, "error": str(e)}


# ── Groq Vision fallback ───────────────────────────────────────────────────────
def groq_vision_fallback(image_bytes: bytes, hint: str, hint_score: float):
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
                "max_tokens": 300,
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
