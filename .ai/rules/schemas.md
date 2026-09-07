---
paths:
  - 'app/Filament/**/Schemas/*Form.php'
---

# Schemas

## FileUpload must use ->disk('public')
FILESYSTEM_DISK=local makes the default disk storage/app/private. Any Filament FileUpload that stores product/category/banner images the storefront renders via asset('storage/...') must set ->disk('public') — otherwise files go to private and 404 on the frontend. Banners, Products, and Categories forms all need it.
