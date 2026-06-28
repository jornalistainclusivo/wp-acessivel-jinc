---
jinc-spec-version: 1.0.0
project-name: wp-acessivel-jinc
feature-name: FrontendBar
status: draft
prd-ref: Phase 3 (Frontend UI Engine)
sdd-ref: Frontend UI & Theming Engine
related-branch: docs/comprehensive-specs
coverage: "1/1 FRs mapped"
created-at: 2026-06-27
authors: Antigravity Orchestrator
---

# SPEC: Frontend Bar (Phase 3)

## Coverage Report

| FR     | Requirement Summary          | Spec Element                                   | Status     |
| ------ | ---------------------------- | ---------------------------------------------- | ---------- |
| FR-UI1 | Injeção nativa de interface adaptativa no topo do site | `BarInjector` class + `wp_body_open` / `wp_footer` | 🟢 Covered |

---

## 1. Type System — AI-Ready Foundation

### 🤖 AI-Ready Layer (Machine Consumable)

```typescript
/**
 * @spec-ref FR-UI1
 * Configuration schema consumed by FrontendBar based on ThemeOptions
 */
interface FrontendBarConfig {
    /** 
     * Determines layout structure injected via data-layout attribute 
     */
    layout: 'top_bar' | 'floating_pill';

    /** 
     * Determines positioning injected via data-position attribute
     */
    position: 'bottom_right' | 'bottom_left';

    /**
     * Determines if Dashicons should be displayed
     * Value comes from 'show_icons' option ('1' or '0')
     */
    showIcons: boolean;
}
```

### 🔧 Implementation Layer (Human + AI)

The options are retrieved via `get_option('jinc_theme_options')` in WordPress. The `BarInjector` interprets these settings to decide the DOM injection structure and CSS classes.

---

## 2. API Contract & Hooks

### 🤖 AI-Ready Layer (Machine Consumable)

```php
/**
 * Class BarInjector
 * @spec-ref FR-UI1
 */
interface IBarInjector {
    // Hooks into 'wp_enqueue_scripts'
    public function enqueueAssets(): void;
    
    // Hooks into 'wp_body_open'
    public function renderBar(): void;
    
    // Hooks into 'wp_footer' (fallback)
    public function renderBarFallback(): void;
    
    // Returns the HTML structure
    public function getBarHtml(): string;
}
```

### 🔧 Implementation Layer (Human + AI)

- **Asset Enqueueing**:
  - Requires `wp_enqueue_scripts`.
  - Bypasses admin context (`isAdminContext()`).
  - Enqueues `jinc-bar.css` and `jinc-bar.js`.
  - Enqueues `dashicons` conditionally if `show_icons` is enabled.
- **HTML Injection Strategy**:
  - Prefer `wp_body_open` to place the accessibility bar at the top of the DOM.
  - Fallback to `wp_footer` if the active theme does not support `wp_body_open`.
  - Ensure idempotency (`$this->rendered = true;`) to avoid duplicate bars.

---

## 3. Business Rules

BR-FB-001: Bar Idempotency
  Precondition: Theme calls `wp_body_open()` and `wp_footer()`.
  Input: Both hooks fire in sequence.
  Invariant: The frontend bar must only appear once in the DOM.
  Output/Action: First hook call sets `$this->rendered = true` and outputs HTML. Subsequent call returns early.
  Violation: E_DUPLICATE_BAR - The DOM contains duplicate ID `jinc-a11y-bar`.

BR-FB-002: Icon Toggle
  Precondition: Frontend rendering begins.
  Input: `show_icons` option from `jinc_theme_options`.
  Invariant: Icon requests should not bloat the page if disabled.
  Output/Action: If `'0'`, skip `wp_enqueue_style('dashicons')` and add `jinc-no-icons` class to the container.

---

## 4. Critical Path — Gherkin

```gherkin
Feature: Frontend Bar Injection

  Scenario: Happy path — Theme supports wp_body_open
    Given a WordPress theme that calls "wp_body_open"
    When the page renders
    Then "renderBar" injects the toolbar HTML
    And "renderBarFallback" exits early without duplicating HTML

  Scenario: Edge case — Theme lacks wp_body_open
    Given an older WordPress theme without "wp_body_open" support
    When the page renders
    Then "renderBarFallback" triggers via "wp_footer"
    And injects the toolbar HTML at the bottom of the page
```
