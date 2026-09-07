---
paths:
  - app/Http/Controllers/PaymentController.php
---

# Controllers

## Callbacks require signature verification before changing order status
POST /midtrans/callback and /digiflazz/callback must verify authenticity before touching order status:
- Midtrans: compare `signature_key` in the body against SHA512(order_id + status_code + gross_amount + server_key) via hash_equals. The secrets come from config('services.midtrans.server_key').
- Digiflazz: verify the `X-Hub-Signature: sha1=...` header equals HMAC-SHA1 of the raw request body using config('digiflazz.webhook_secret'). Reject with 403 when it mismatches.
Implemented in PaymentController::midtransSignatureValid() and digiflazzSignatureValid(). Do not relax or remove these checks.

## Midtrans & Digiflazz callbacks are idempotent
Midtrans settlement callback must not re-dispatch ProcessTopUpJob when the order is already paid/processing/completed (guard with $alreadyPaid + $progressed) or the user gets double top-up. Digiflazz callback must not regress a completed/refunded order on a late failure callback. Order state must only move forward.
