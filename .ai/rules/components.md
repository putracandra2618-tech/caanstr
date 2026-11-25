---
paths:
  - 'resources/views/components/**'
---

# Components

## Reuse status-badge & checkout-steps components
Use the shared x-status-badge (single source for order status colors/icons) and x-checkout-steps (3-step journey: Detail Pesanan > Pembayaran > Selesai) instead of re-declaring status color/icon maps in each page. New storefront icons must be Lucide stroke-based SVGs using currentColor.
