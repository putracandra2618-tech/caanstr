---
paths:
  - app/Http/Controllers/PaymentController.php
---

# Controllers

## Callbacks require signature verification before changing order status
POST /midtrans/callback and /tokovoucher/callback must verify authenticity before touching order status:
- Midtrans: compare `signature_key` in the body against SHA512(order_id + status_code + gross_amount + server_key) via hash_equals. The secrets come from config('services.midtrans.server_key').
- Tokovoucher: verify the `X-TokoVoucher-Authorization` header equals `md5(MEMBER_CODE:SECRET:REF_ID)` using config('tokovoucher.member_code') and config('tokovoucher.secret_key'), via TokovoucherService::verifyWebhookSignature(). Reject with 403 when it mismatches.
Implemented in PaymentController::midtransSignatureValid() and tokovoucherCallback(). Do not relax or remove these checks.

## Midtrans & Tokovoucher callbacks are idempotent
Midtrans settlement callback must not re-dispatch ProcessTopUpJob when the order is already paid/processing/completed (guard with $alreadyPaid + $progressed) or the user gets double top-up. Tokovoucher callback must not regress a completed/refunded order on a late failure callback. Order state must only move forward.
