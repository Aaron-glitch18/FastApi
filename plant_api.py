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
PLANTNET_URL      = "https://my-api.plantnet.org/v2/identify/all"
GROQ_API_KEY      = os.getenv("GROQ_API_KEY")
SCORE_MIN         = 20.0

cap = cv2.VideoCapture(0)


# ── Flux video MJPEG (affichage uniquement) ───────────────────────────────────
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


@app.get("/great")
def great():
    return {"message": "of course."} #test la connexion


# ── POST /scan — image envoyee depuis le navigateur ───────────────────────────
@app.post("/scan")
async def scan_upload(file: UploadFile = File(...)):
    content  = await file.read()
    filepath = "scan_temp.jpg"
    with open(filepath, "wb") as f:
        f.write(content)
    return identify_plant(filepath, content)


# # ── GET /scan/camera — capture depuis la camera OpenCV ───────────────────────
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
    return identify_plant(filepath, content)


#  PlantNet
def identify_plant(image_path: str, image_bytes: bytes):
    try:
        with open(image_path, "rb") as f:
            r = requests.post(
                PLANTNET_URL,
                params={"api-key": PLANTNET_API_KEY, "lang": "fr"},
                files=[("images", (image_path, f, "image/jpeg"))],
                data={"organs": ["auto"]}
            )
        # if r.status_code == 200:
        #     results = r.json().get("results", [])
        #     if results:
        #         top   = results[0]
        #         score = round(top["score"] * 100, 1)
        #         if score >= SCORE_MIN:
        #             return {
        #                 "success":    True,
        #                 "source":     "plantnet",
        #                 "scientific": top["species"]["scientificNameWithoutAuthor"],
        #                 "common":     top["species"].get("commonNames", ["Inconnu"])[0],
        #                 "score":      score,
        #                 "family":     top["species"].get("family", {}).get("scientificNameWithoutAuthor", "?")
        #             }
        #         hint = top["species"].get("commonNames", [""])[0] or top["species"]["scientificNameWithoutAuthor"]
        #         return groq_vision_fallback(image_bytes, hint, score)
        #     return groq_vision_fallback(image_bytes, None, 0)
        # return {"success": False, "error": f"PlantNet {r.status_code}"}
    except Exception as e:
        return {"success": False, "error": str(e)}
