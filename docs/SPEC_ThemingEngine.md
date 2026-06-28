---
jinc-spec-version: 1.0.0
project-name: wp-acessivel-jinc
feature-name: Theming Engine
status: draft
prd-ref: Phase 3.5 (Theming Engine)
sdd-ref: SDD Core Architecture
related-branch: feat/theming-engine
coverage: "3/3 FRs mapped"
created-at: 2026-06-27
authors: Antigravity Orchestrator
---

# SPEC: Theming Engine (Phase 3.5)

## Coverage Report

| FR     | Requirement Summary          | Spec Element                                   | Status     |
| ------ | ---------------------------- | ---------------------------------------------- | ---------- |
| FR-001 | Backend Settings API         | `SettingsPage` class + `jinc_theme_options`    | 🟢 Covered |
| FR-002 | Visual Options & Storage     | `ThemeOptions` schema + `update_option()`      | 🟢 Covered |
| FR-003 | Frontend Dynamic CSS Inject  | `ThemeEngine` class + `wp_add_inline_style`    | 🟢 Covered |

---

## 1. Type System — AI-Ready Foundation

### 🤖 AI-Ready Layer (Machine Consumable)

```typescript
/**
 * @spec-ref FR-002
 * Define the schema for the options stored in the wp_options table.
 * Option Key: 'jinc_theme_options'
 */
type LayoutType = 'top_bar' | 'floating_pill';
type FloatingPosition = 'bottom_right' | 'bottom_left';

interface ThemeOptions {
    /** 
     * The layout style of the accessibility bar. 
     * @default 'top_bar'
     */
    layout: LayoutType;

    /** 
     * Position used only if layout is 'floating_pill'.
     * @default 'bottom_right'
     */
    position: FloatingPosition;

    /** 
     * Hex color code for the bar background.
     * @default '#000000'
     */
    bg_color: string;

    /** 
     * Hex color code for the text and icons.
     * @default '#FFFFFF'
     */
    text_color: string;

    /** 
     * Hex color code for interactive elements/focus states.
     * @default '#0052CC' // High contrast blue
     */
    accent_color: string;
}
```

### 🔧 Implementation Layer (Human + AI)

The data will be stored as a serialized array in the WordPress `wp_options` table under the key `jinc_theme_options`. Default values should be injected if the option is missing or if specific keys are undefined.

---

## 2. API Contract & Hooks (Backend Settings)

### 🤖 AI-Ready Layer (Machine Consumable)

```php
/**
 * Class SettingsPage
 * @spec-ref FR-001
 */
interface ISettingsPage {
    // Hooks into 'admin_menu'
    public function add_admin_menu(): void;
    
    // Hooks into 'admin_init'
    public function register_settings(): void;
    
    // Hooks into 'admin_enqueue_scripts' to load wp-color-picker
    public function enqueue_assets(string $hook_suffix): void;
    
    // Renders the HTML page
    public function render_page(): void;
}
```

### 🔧 Implementation Layer (Human + AI)

- **Menu**: Add a page under Settings (or a custom root menu if established) called "WP Acessível".
- **Settings API**:
  - Register setting group: `jinc_theme_options_group`
  - Register setting name: `jinc_theme_options`
  - Add Sections and Fields for Layout, Position, and Colors.
- **Fields**:
  - `layout`: `<select>` element.
  - `position`: `<select>` element. JS should hide/disable this field if `layout !== 'floating_pill'`.
  - `colors`: Native WP color picker. Must enqueue `wp-color-picker` in `admin_enqueue_scripts`.

---

## 3. Theming Engine (Frontend Injection)

### 🤖 AI-Ready Layer (Machine Consumable)

```php
/**
 * Class ThemeEngine
 * @spec-ref FR-003
 */
interface IThemeEngine {
    // Hooks into 'wp_enqueue_scripts' (late priority)
    public function inject_dynamic_css(): void;
    
    // Retrieves and sanitizes the stored options with fallbacks
    private function get_options(): array;
}
```

### 🔧 Implementation Layer (Human + AI)

- **Action Hook**: `wp_enqueue_scripts`. Priority should ensure it runs *after* the main plugin CSS is enqueued.
- **Dynamic CSS**:
  - Uses `wp_add_inline_style( 'jinc-acessibilidade-style', $custom_css )`. (Assuming 'jinc-acessibilidade-style' is the handle of the main bar stylesheet).
- **CSS Variable Injection**:

  ```css
  :root {
      --jinc-bar-bg: [bg_color];
      --jinc-bar-text: [text_color];
      --jinc-bar-accent: [accent_color];
  }
  ```

- **Layout Behavior**:
  - **top_bar**: The bar container should have `position: sticky; top: 0; width: 100%; border-radius: 0;`.
  - **floating_pill**: The bar container should have `position: fixed; bottom: 20px; border-radius: 50px; padding: 10px 20px;`.
    - If `position === 'bottom_right'`, apply `right: 20px;`.
    - If `position === 'bottom_left'`, apply `left: 20px;`.
  - The PHP `ThemeEngine` will output a wrapper class or specific inline rules for these layouts, or just set data attributes (e.g., `<div id="jinc-bar" data-layout="floating_pill" data-position="bottom_right">`) that the static CSS will respond to.

---

## 4. Business Rules

BR-001: Fallback Options
  Precondition: The `jinc_theme_options` key doesn't exist in the database (first activation).
  Input: `get_option('jinc_theme_options')` returns `false`.
  Invariant: The frontend must never crash or output empty/invalid CSS values.
  Output/Action: The `ThemeEngine` must merge any retrieved values with the hardcoded defaults (e.g., bg='#000000').

BR-002: Color Sanitization
  Precondition: Admin submits the settings form.
  Input: Color values from the POST request.
  Invariant: Stored color values must be valid HEX codes.
  Output/Action: Use `sanitize_hex_color` for all color inputs during the `register_setting` sanitize callback.

---

## 5. Critical Path — Gherkin (PHPUnit Scenarios)

```gherkin
Feature: Theming Engine Visual Customization

  Scenario: Default options are loaded when no settings exist
    Given the option "jinc_theme_options" does not exist in the database
    When the ThemeEngine runs "get_options"
    Then it should return the default array with layout "top_bar" and background "#000000"
    And the injected inline CSS should contain "--jinc-bar-bg: #000000"

  Scenario: Inline style injection with custom values
    Given the option "jinc_theme_options" contains background "#FF0000" and layout "floating_pill"
    When the "wp_enqueue_scripts" action is fired
    Then the "wp_add_inline_style" function must be called on the plugin's stylesheet handle
    And the generated CSS string must contain "--jinc-bar-bg: #FF0000"

  Scenario: Color input sanitization fails on invalid hex
    Given the admin submits a background color of "invalid_color"
    When the settings are sanitized during save
    Then the "bg_color" should fall back to its previous valid state or empty string/default
```
