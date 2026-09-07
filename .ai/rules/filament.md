---
paths:
  - 'app/Filament/**'
---

# Filament

## Filament v5 schema/page gotchas
Filament v5 traps: (1) disabled() fields are NOT saved by default — derive values (e.g. banned_at from is_banned) in page-level mutateFormDataBeforeCreate/mutateFormDataBeforeSave, not the resource; (2) TextInput::uppercase() does not exist (v3 API) — use ->dehydrateStateUsing(fn ($state) => strtoupper($state)); (3) navigationGroup/navigationIcon properties need `string|UnitEnum|null` / `string|BackedEnum|null` type with `use UnitEnum`/`use BackedEnum`; (4) closure rules via ->rule() can capture $operation/$record for context-aware validation (e.g. forbid banning admins).

## Filament v5 badantas & route-key traps for CRUD tests
(1) disabled() fields are excluded from form state AND still validated if required() — a disabled+required() field (e.g. OrderForm.order_number) blocks create. Drop required() for fields auto-computed via page-level mutateFormDataBeforeCreate/Save. (2) Resources whose model overrides getRouteKeyName() (Category/Product=slug, Promo=code) must mount Edit pages in Livewire tests with the route-key value, not id. (3) FileUpload v5 expect fillForm('image[]') as an array.
