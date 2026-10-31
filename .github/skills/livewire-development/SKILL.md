---
name: livewire-development
description: "Use this skill whenever creating, editing, reviewing, or testing Livewire components, `wire:*` Blade directives, reactive forms, Livewire loading or offline states, computed or locked properties, dynamic rows, progressive conversion from controller forms, or Livewire browser tests. Also trigger for double-submit prevention, pending-state UX, Livewire authorization, custom update routes, and Alpine integration. Do not use for plain Blade/controller forms that have no Livewire behavior."
license: MIT
metadata:
  author: laravolt
---

# Livewire 4 Development

## Before Editing

1. Confirm the installed Livewire version with `composer show livewire/livewire`.
2. Use `search-docs` for APIs that depend on the installed version.
3. Read `.ai/rules/index.md` and every matching project rule.
4. Inspect nearby components, Blade views, and tests before introducing a new pattern.

The `x-volt-*` prefix belongs to Laravolt's Blade components. It does not mean the application uses the `livewire/volt` package.

## Component Boundaries

- Keep business writes in Actions or services when both an HTTP controller and Livewire component use the operation.
- Treat every public property and action argument as untrusted request input.
- Validate writable values immediately before persistence.
- Reload state-sensitive models inside the action before authorizing and writing.
- Authorize every action. A hidden button, route middleware, or locked property is not sufficient on its own.
- Avoid request-specific static or singleton state because the application may run under Octane.

## Progressive Conversion

When converting an existing controller form:

- preserve its route, CSRF token, method spoofing, field names, validation, flashed old input, and fallback until removal is intentional;
- keep the existing HTTP feature test, including successful submit and redirect-back error recovery;
- share validation rules and the write operation rather than duplicating either path;
- introduce Livewire on one bounded flow before converting authentication or other high-risk surfaces;
- do not activate a component for states it cannot render or submit.

A progressively enhanced form should retain a normal `action` and `method` where practical:

```blade
<form
    method="POST"
    action="{{ route('profile.update') }}"
    wire:submit="save"
>
    @csrf
    @method('PUT')
</form>
```

## Security

Use `#[Locked]` for client-immutable scalar state such as model IDs:

```php
use Livewire\Attributes\Locked;

#[Locked]
public int $userId;
```

Still authorize the resolved model inside the action:

```php
public function save(UpdateProfile $updateProfile): void
{
    $user = User::query()->findOrFail($this->userId);

    $this->authorize('update', $user);

    $validated = $this->validate([...]);

    $updateProfile->handle($user, $validated);
}
```

Do not trust hidden, readonly, or disabled fields. Recompute derived values on the server.

## Loading-State Contract

Classify each request as either a primary write action or a micro request.

### Primary writes

For Save, Submit, Approve, Reject, Delete, or Sync:

- disable conflicting actions;
- lock the relevant fieldset;
- set `aria-busy` on the form or region;
- keep the action's normal and loading labels separate;
- provide a polite status message;
- ensure the final success or error state is explicit.

```blade
<form
    wire:submit="save"
    wire:loading.attr="aria-busy"
    wire:target="save"
>
    <fieldset wire:loading.attr="disabled" wire:target="save">
        {{-- fields --}}

        <x-volt-button
            type="submit"
            wire:loading.attr="disabled"
            wire:target="save"
        >
            <span wire:loading.remove wire:target="save">Save</span>
            <span wire:loading.flex wire:target="save" role="status">
                Saving...
            </span>
        </x-volt-button>

        <p
            wire:loading.delay
            wire:target="save"
            role="status"
            aria-live="polite"
        >
            Please wait while the form is processed.
        </p>
    </fieldset>
</form>
```

If multiple writes conflict, target all of them on the fieldset and buttons, while keeping each visible label targeted to its own action.

### Micro requests

Use action-specific delayed indicators for checks, dependent selects, and small updates:

```blade
<p wire:loading.delay wire:target="checkCode" role="status">
    Checking availability...
</p>
```

Do not show a global spinner for every component request. For row actions, target the exact invocation:

```blade
wire:target="removeRow({{ $rowId }})"
```

## Offline and Long-Running States

Every form that writes data should expose offline feedback:

```blade
<div wire:offline>
    <x-volt-alert variant="warning">
        You are offline. Changes cannot be sent yet.
    </x-volt-alert>
</div>
```

For polling or asynchronous backend work:

- show the current operation;
- stop polling on a terminal state;
- expose backend errors;
- escalate the message after a documented UX threshold;
- offer retry only when the operation is safe to repeat.

## Derived State

Use a domain method or `#[Computed]` property as the single source of truth:

```php
use Livewire\Attributes\Computed;

#[Computed]
public function total(): int
{
    return $this->quantity * $this->unitPrice;
}
```

Access computed properties through `$this` in Blade:

```blade
{{ $this->total }}
```

Do not independently implement the same formula in Blade, JavaScript, validation, and persistence.

## Dynamic Collections

Editable loops require stable, prefixed keys:

```blade
@foreach ($rows as $index => $row)
    <div wire:key="profile-row-{{ $rowKeys[$index] }}">
        {{-- row --}}
    </div>
@endforeach
```

- Prefer a database ID for persisted rows.
- Use a server-generated UUID for unsaved rows.
- Keep UI-only keys separate from domain payloads.
- Reindex data and key arrays together after removal.
- Never regenerate every key during render.
- Never use an array index as the identity of an editable row.

## Assets and Alpine

Livewire can auto-inject its assets. For custom layouts or deterministic placement, include `@livewireStyles` in the head and `@livewireScripts` before the body closes.

Do not load Alpine a second time. If manual bundling is required, use the official Livewire ESM pattern and start Livewire exactly once.

Do not hardcode `/livewire/update`. Deployments may customize the route, and tests must read the exact URI exposed by `[data-update-uri]`.

## Testing

Use `Livewire::test()` for validation, authorization, state, persistence, redirects, and rendered directives.

Use Pest Browser for behavior that requires JavaScript:

- Livewire initialization;
- a real request remaining pending;
- button and fieldset disabling;
- spinner visibility;
- `aria-busy` changes;
- offline behavior;
- dynamic DOM identity;
- final success, redirect, or error.

A loading-state browser test must:

1. fill valid data;
2. read the exact URI from `[data-update-uri]`;
3. hold one matching `fetch()` request;
4. trigger the action;
5. assert the intermediate pending UI;
6. release the request;
7. assert the final outcome.

Markup-only assertions prove that directives exist, not that the UX works.

Run focused component and browser tests first, then Pint and the repository's complete test gate.
