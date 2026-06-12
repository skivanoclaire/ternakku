# Container ML (FastAPI) untuk VM. Dipakai service `ml` di docker-compose.
# VM melatih model sendiri (Opsi 2) -> image berisi deps training + serving.
FROM python:3.12-slim

WORKDIR /app
COPY requirements.txt .
RUN pip install --no-cache-dir -r requirements.txt

COPY . .

# data/ bisa di-mount sebagai volume dari host (./data:/app/data) bila perlu
EXPOSE 8000
CMD ["uvicorn", "main:app", "--host", "0.0.0.0", "--port", "8000"]
