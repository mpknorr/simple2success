# Simple2Success — Compliance Rules for Public Pages

> **This document defines mandatory rules for all public-facing pages.**
> These rules apply to every developer, AI agent, or contributor working on this project.

---

## Rule 1: No Partner Company References on Public Pages

### Scope
This rule applies to **all publicly accessible pages** — meaning any page that can be viewed without being logged in:

- `link1/`, `link2/`, `link3/` (Capture Pages)
- `linkp1/`, `linkp2/`, `linkp3/` (Premium Pages)
- Any future landing pages (`link4/`, `linkp4/`, etc.)
- Any other public-facing pages (e.g. `index.php`, error pages, legal pages)

### What is FORBIDDEN on public pages

The following words, abbreviations, brand names, product names, and domain names must **NEVER** appear in any user-visible text, HTML, meta tags, titles, alt texts, or link labels on public pages:

| Category | Forbidden terms |
|---|---|
| Company name | `PM-International`, `PM International` |
| Abbreviation | `PM` (when used as abbreviation for the company) |
| Brand / Products | `FitLine`, `NTC`, `PM Products` |
| Domains | `pmebusiness.com`, `fitline.com`, `pm-international.com` |
| Program names | `Teampartner Start`, `Manager Quickstart`, `Starter Kit`, `Demo Bag` |
| Business terms | `Autoship`, `PM Compensation Plan`, `PM Partner`, `PM Partnership`, `PM Income Plan`, `PM Experience` |

### What IS allowed on public pages

- Generic income/business language: "partner program", "compensation plan", "our business partner", "the company"
- Social proof without naming the company: "Active in 40+ Countries", "Proven System Since 2023"
- Commission language without company attribution: "200% Commissions Month One", "Residual Income"

### Where these terms ARE allowed

The backoffice (all pages behind login) may and must use the full company name and product names, because registered members need this information to complete their registration steps.

---

## Rule 2: Scan Before Every Release

Before committing changes to any public page, run this scan command:

```bash
grep -rni "\bpm\b\|pm-\|fitline\|pmebusiness\|autoship\|teampartner\|quickstart\|starter.kit\|demo.bag\|pm products\|pm experience\|pm partnership\|pm compensation\|pm income" \
  link1/ link2/ link3/ linkp1/ linkp2/ linkp3/ \
  --include="*.php" --include="*.html"
```

**Expected result: no output.** Any match must be fixed before committing.

> Note: Matches inside minified JavaScript library files (e.g. variable names like `pm`, `PM` in `hoot-smplcp.js`) are technical code identifiers, not user-visible text, and are exempt from this rule.

---

*Last updated: April 2026*
