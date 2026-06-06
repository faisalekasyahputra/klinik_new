# Skill: Web Design Research → Modular Redesign

<aside>
🧭

**What this is:** A Superpowers skill that makes any AI agent research a website's design, extract *all* its styling, and output a consistent, modular design-system artifact set **before** redesigning. Drop it into your repo so every AI editor follows the same path.

</aside>

This document is both **human-readable** (the sections below) and **machine-ready** (the full copyable `SKILL.md` at the bottom). It is written in English, the standard for AI skill files.

- **Where it lives:** `skills/web-design-research/SKILL.md` in the Superpowers plugin repo
- **When it runs:** during the **brainstorm / research** phase of the Superpowers path, *before* planning a redesign
- **Why it exists:** so anyone editing the codebase with AI produces the *same* tokens and the *same* modular structure — no ad-hoc styling

---

## Metadata (YAML frontmatter)

The skill begins with frontmatter so it **auto-activates** when a redesign/refactor task is detected. Naming follows Superpowers conventions (lowercase, hyphenated).

```yaml
---
name: web-design-research
description: Research an existing website's visual design and extract a complete, portable design system (tokens, CSS variables, Tailwind config, component inventory) to drive a consistent, modular redesign.
when_to_use: Use before redesigning or refactoring any web UI, when onboarding to an unfamiliar frontend, or whenever styling decisions must stay consistent across multiple AI editors.
version: 1.0.0
---
```

---

## Phase 1 — Design Audit & Capture

Systematically inventory the existing site **before touching code**.

- [ ]  Identify all **stylesheets, theme files, and inline styles** in the codebase
- [ ]  Capture the **rendered** values (computed styles), not just source values
- [ ]  Record every recurring visual primitive:

| Category | What to extract |
| --- | --- |
| Color | brand, neutrals, semantic (success / warn / error), interaction states |
| Typography | font families, sizes, weights, line-heights, letter-spacing |
| Spacing | scale, paddings, margins, gaps |
| Layout | grid, container widths, breakpoints |
| Effects | radii, shadows, borders, opacity, transitions |
| Assets | icons, logos, image treatments |

<aside>
📸

Capture **raw observed values first** — normalization happens in Phase 2. Do not invent values that don't exist on the site.

</aside>

---

## Phase 2 — Normalize into Design Tokens

Deduplicate and structure the raw values into a canonical token set.

- Collapse near-duplicate values into a single token (e.g. `#fafafa` / `#f9f9f9` → `--color-bg-subtle`)
- Use a **tiered naming scheme**: primitive → semantic → component tokens
- Output tokens as platform-neutral JSON:

```json
{
  "color": { "brand": { "500": "#2563eb" }, "bg": { "subtle": "#fafafa" } },
  "font": { "family": { "sans": "Inter, system-ui, sans-serif" } },
  "space": { "1": "4px", "2": "8px", "3": "12px" },
  "radius": { "md": "8px" }
}
```

<aside>
🧱

Tokens are the single source of truth. Everything downstream (CSS variables, Tailwind) is **generated from** these.

</aside>

---

## Phase 3 — Emit CSS Variables & Tailwind Config

Generate both consumable artifacts from the token set.

**CSS custom properties** (`:root` block):

```css
:root {
  --color-brand-500: #2563eb;
  --space-2: 8px;
  --radius-md: 8px;
}
```

**Tailwind config** (`tailwind.config.js` → `theme.extend`):

```jsx
module.exports = {
  theme: { extend: {
    colors: { brand: { 500: 'var(--color-brand-500)' } },
    spacing: { 2: 'var(--space-2)' },
    borderRadius: { md: 'var(--radius-md)' },
  }},
}
```

- Tailwind values **reference the CSS variables** so theming stays in one place
- Verify every token maps to **both** outputs before moving on

---

## Phase 4 — Component Inventory

Catalog every reusable UI piece and bind it to tokens, enabling modular rebuilds.

| Component | Variants | Tokens used | States |
| --- | --- | --- | --- |
| Button | primary, secondary, ghost | brand, radius-md, space-2/3 | hover, focus, disabled |
| Card | default, elevated | bg-subtle, radius-md, shadow-sm | — |
| Input | text, error | border, radius, space | focus, error |
- For each component, note **structure, props / variants, and accessibility** considerations
- Flag duplicated or inconsistent components to **consolidate** during redesign

---

## Phase 5 — Handoff & Consistency Rules

This skill outputs the artifact set; the next phase consumes it.

**Superpowers handoff:**

`brainstorm → [THIS SKILL: research + tokens] → write-plan → execute-plan`

**Consistency rules (the contract every AI must follow):**

- [ ]  Never hardcode a style value — always reference a token
- [ ]  New values must be added to tokens *first*, then propagated to CSS vars + Tailwind
- [ ]  Keep the primitive → semantic → component token tiers intact
- [ ]  Re-run the audit when new pages or components appear

<aside>
🔁

Any agent editing this codebase reads this skill, reuses the token set, and follows the same path — guaranteeing a modular, consistent redesign.

</aside>

---

## 📋 Copy-ready `SKILL.md`

Copy everything below verbatim into `skills/web-design-research/SKILL.md` in your Superpowers plugin repo.

```markdown
---
name: web-design-research
description: Research an existing website's visual design and extract a complete, portable design system (tokens, CSS variables, Tailwind config, component inventory) to drive a consistent, modular redesign.
when_to_use: Use before redesigning or refactoring any web UI, when onboarding to an unfamiliar frontend, or whenever styling decisions must stay consistent across multiple AI editors.
version: 1.0.0
---

# Web Design Research → Modular Redesign

Research an existing website's visual design, extract ALL of its styling, and
emit a portable design-system artifact set so any AI editor can redesign the UI
consistently and modularly. Run this during the brainstorm/research phase,
before write-plan.

## Outputs (produce all of these)
1. `design-tokens.json` — canonical, platform-neutral tokens
2. `tokens.css` — CSS custom properties generated from the tokens
3. `tailwind.config.js` — theme.extend referencing the CSS variables
4. `component-inventory.md` — every reusable component mapped to tokens

## Phase 1 — Design Audit & Capture
Before touching code, inventory the existing site:
- Locate all stylesheets, theme files, CSS-in-JS, and inline styles.
- Capture RENDERED (computed) values, not just source values.
- Record every recurring visual primitive:
  - Color: brand, neutrals, semantic (success/warn/error), interaction states
  - Typography: font families, sizes, weights, line-heights, letter-spacing
  - Spacing: scale, paddings, margins, gaps
  - Layout: grid, container widths, breakpoints
  - Effects: radii, shadows, borders, opacity, transitions
  - Assets: icons, logos, image treatments
Rule: capture raw observed values first. Do NOT invent values not on the site.

## Phase 2 — Normalize into Design Tokens
- Collapse near-duplicate values into a single token
  (e.g. #fafafa / #f9f9f9 -> --color-bg-subtle).
- Use a tiered naming scheme: primitive -> semantic -> component tokens.
- Tokens are the single source of truth; everything else is generated from them.
- Emit design-tokens.json, e.g.:

{
  "color": { "brand": { "500": "#2563eb" }, "bg": { "subtle": "#fafafa" } },
  "font": { "family": { "sans": "Inter, system-ui, sans-serif" } },
  "space": { "1": "4px", "2": "8px", "3": "12px" },
  "radius": { "md": "8px" }
}

## Phase 3 — Emit CSS Variables & Tailwind Config
Generate both artifacts FROM the tokens.

tokens.css:
:root {
  --color-brand-500: #2563eb;
  --space-2: 8px;
  --radius-md: 8px;
}

tailwind.config.js (Tailwind values reference the CSS variables):
module.exports = {
  theme: { extend: {
    colors: { brand: { 500: 'var(--color-brand-500)' } },
    spacing: { 2: 'var(--space-2)' },
    borderRadius: { md: 'var(--radius-md)' },
  }},
}

Verify every token maps to BOTH outputs.

## Phase 4 — Component Inventory
Catalog every reusable UI piece in component-inventory.md and bind it to tokens:
| Component | Variants                  | Tokens used                 | States              |
|-----------|---------------------------|-----------------------------|---------------------|
| Button    | primary, secondary, ghost | brand, radius-md, space-2/3 | hover, focus, disabled |
| Card      | default, elevated         | bg-subtle, radius-md, shadow-sm | -               |
| Input     | text, error               | border, radius, space       | focus, error        |
For each: note structure, props/variants, and accessibility considerations.
Flag duplicated/inconsistent components to consolidate during redesign.

## Phase 5 — Handoff & Consistency Rules
Handoff path:
  brainstorm -> [THIS SKILL: research + tokens] -> write-plan -> execute-plan
This skill outputs the artifact set; write-plan consumes it to scope the redesign.

Consistency rules (the contract every AI must follow):
- Never hardcode a style value — always reference a token.
- New values must be added to tokens FIRST, then propagated to CSS vars + Tailwind.
- Keep the primitive -> semantic -> component token tiers intact.
- Re-run the audit when new pages or components appear.
```

<aside>
📦

**Install:** drop the folder `skills/web-design-research/` (containing the `SKILL.md` above) into your Superpowers plugin's `skills/` directory. The skill then activates automatically on redesign/refactor tasks.

</aside>