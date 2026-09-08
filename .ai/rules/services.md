---
paths:
  - app/Services/TokovoucherService.php
  - 'app/Services/Tokovoucher*.php'
---

# Services

## Tokovoucher base URL & auth
Tokovoucher API lives at https://api.tokovoucher.net (also http://trx-ip.tokovoucher.net/ for IP-based flow — not used). Authentication uses Member Code + Secret Key from https://member.tokovoucher.net/pengaturan/secret-key. Signature formula: `md5(MEMBER_CODE:SECRET:REF_ID)` for transaction/status/webhook, and the default signature `md5(MEMBER_CODE:SECRET)` for member (balance) and product list endpoints. Whitelist Tokovoucher IP 188.166.243.56.

## Tokovoucher endpoints
- Cek saldo: `GET /member?member_code&signature` → `data.saldo`
- List produk full: `GET /member/produk/full` → nested `data.{category,operator,jenis,produk}`; `kode_produk` is the code used in transactions
- Transaksi: `POST /v1/transaksi` with `ref_id, produk, tujuan, server_id, member_code, signature`. `server_id` is separate from `tujuan` (zone vs player id)
- Status: `POST /v1/transaksi/status` with `ref_id, member_code, signature`
- Status values are lowercase: `sukses` / `gagal` / `pending`. All HTTP errors must be treated as PENDING, wait for callback final.

## Callbacks authenticated via X-TokoVoucher-Authorization header
Tokovoucher webhook sends header `X-TokoVoucher-Authorization` = `md5(MEMBER_CODE:SECRET:REF_ID)`. Validate it with the ref_id from the body using hash_equals. Unlike Midtrans/DigiFlazz there is no separate webhook secret — the header formula uses the same Member Code + Secret Key.
