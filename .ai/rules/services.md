---
paths:
  - app/Services/DigiflazzService.php
  - app/Services/MidtransService.php
---

# Services

## DigiFlazz uses api.digiflazz.com only (no sandbox subdomain)
DigiFlazz does not have an api-sandbox.digiflazz.com host — the host is always https://api.digiflazz.com/v1. Development/sandbox mode is toggled by (a) a dev- prefixed API key and (b) sending testing:true in the request body, NOT by changing the URL. Balance endpoint is POST /cek-saldo with cmd=deposit and sign md5(username+apiKey+"depo").

## Callbacks Sandbox Wajib Set Payment Notification URL di Dashboard
MIDTRANS_WEBHOOK_URL di .env TIDAK dipakai aplikasi; Midtrans hanya mengirim webhook ke Payment Notification URL yang didaftarkan di Dashboard (Settings > Configuration). Fallback: jalankan `orders:sync-midtrans` (terjadwal tiap 5 menit) yang membaca status dari api.sandbox.midtrans.com. API base URL configurable lewat `services.midtrans.api_url`; jangan hardcode sandbox.

## Centralize Midtrans status sync in MidtransService::applyStatus
Semua pembaruan status order dari Midtrans (webhook PaymentController::callback, orders:sync-midtrans, dan verifikasi di PaymentController::success) WAJIB lewat MidtransService::applyStatus. Jangan menulis ulang mapping status (settlement/capture/deny/dll) di tempat lain agar tidak kembar dan tetap idempotent (ProcessTopUpJob hanya di-dispatch saat baru jadi paid).
