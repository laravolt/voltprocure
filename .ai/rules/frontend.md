# Frontend Views

- Use Laravolt's `x-volt-*` Blade components and Preline/Tailwind patterns before creating a new primitive. The `volt` prefix belongs to Laravolt, not the `livewire/volt` package.
- Preserve dark-mode styling with `dark:` variants and visible keyboard focus states.
- Prefer semantic `form`, `fieldset`, `button`, label, and status elements. Pending feedback must be programmatically exposed with `aria-busy`, `role="status"`, or `aria-live` where appropriate.
- Scope loading feedback to the action that caused it. Use delayed indicators for fast micro requests to avoid flicker.
- Do not add a second Alpine runtime or global JavaScript loading behavior when Livewire can own the component state declaratively.
- Ensure Blade component attribute bags preserve arbitrary `wire:*`, `aria-*`, and `data-*` attributes.
