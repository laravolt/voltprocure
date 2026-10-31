# Livewire Components

- Treat every public property as untrusted request input. Validate writable properties and authorize the current model/state inside every action before persistence.
- Use `#[Locked]` for client-immutable scalar state such as IDs, but never as a replacement for authorization or state checks.
- Keep domain writes in reusable Actions or services when an HTTP fallback and a Livewire action perform the same operation.
- Primary write actions must expose an accessible pending state: lock conflicting controls, set `aria-busy`, and show an action-specific status.
- Use targeted delayed loading for micro requests. Parameterized row actions must target the exact invocation so unrelated rows do not appear busy.
- Render `wire:offline` feedback on forms that write data.
- Use stable, prefixed `wire:key` values for editable loops. Keep UI-only identity out of persisted domain payloads.
- Calculate derived values in domain methods or `#[Computed]` properties, not independently in Blade and persistence code.
- Do not load Alpine separately when Livewire provides it. Do not hardcode `/livewire/update`; browser tests must discover the page's actual update URI.
- For progressive enhancement, preserve the existing HTTP action, CSRF token, method spoofing, server validation, field names, and flashed old input until the fallback is intentionally retired.
- Keep HTTP and Livewire validation rules in one application-owned source so the two write paths cannot drift.
