---
paths:
  - 'resources/views/**'
---

# Views

## Storefront blade-icons default set
Storefront icons use the blade-icons `default` set mapped to resources/views/icons (see config/blade-icons.php). Icon files are stroke-based Lucide SVGs using currentColor, saved under the exact <x-icon name="..."> values the views reference (watch the historical names: logout, sync, refresh, check-circle, x-circle, alert). blade-icons wraps set paths with base_path(), so use a relative path in config. Do not rely on blade-heroicons — its v2.7.0 SVGs are prefixed (o-/m-/c-) and don't cover names like gamepad, mail, shield, box.

## Storefront theme: light kalem + forest accent
Storefront (resources/views) is light & calm — NOT dark. Palette: warm stone neutrals + custom forest olive-green accent (--color-forest-*). Anti-AI-slop: no emerald/teal/cyan, NO glow (shadow-glow), NO text-gradient, NO dot-grid/radial blur backgrounds, NO stagger reveal. Subtle blue/amber/red/green used only for status badges. Only allowed gradient = the thin top accent bar (from-forest-500 via-forest-400 to-forest-600) and soft forest branding panel on auth split-layout. Animations limited to a single fade-in (class fade-in, no data-stagger). Do NOT reintroduce glow/gradients everywhere.
