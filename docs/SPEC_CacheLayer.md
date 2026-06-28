---
jinc-spec-version: 1.0.0
project-name: wp-acessivel-jinc
feature-name: CacheLayer
status: draft
prd-ref: Non-Functional Requirements (Performance)
sdd-ref: ADR-003 (Transients API)
related-branch: docs/comprehensive-specs
coverage: "1/1 FRs mapped"
created-at: 2026-06-27
authors: Antigravity Orchestrator
---

# SPEC: Cache Layer

## Coverage Report

| FR     | Requirement Summary          | Spec Element                                   | Status     |
| ------ | ---------------------------- | ---------------------------------------------- | ---------- |
| NFR-P1 | Desempenho e Caching de HTML transformado | `TransientCacheManager` class                | 🟢 Covered |

---

## 1. Type System — AI-Ready Foundation

### 🤖 AI-Ready Layer (Machine Consumable)

```typescript
/**
 * @spec-ref NFR-P1
 * Represents the Cache payload for processed HTML content
 */
interface CachedContent {
    /** 
     * The ID of the post/page 
     */
    postId: number;

    /** 
     * The HTML string after SemanticEnforcer mutations 
     */
    processedHtml: string;

    /**
     * Timestamp of the original post modified date, for invalidation
     */
    lastModified: number;
}
```

### 🔧 Implementation Layer (Human + AI)

The Cache layer must strictly use the WordPress Transients API `set_transient` and `get_transient`. 
The key must be deterministic, combining a prefix and the Post ID (e.g., `jinc_a11y_content_{post_id}`).

---

## 2. API Contract & Hooks

### 🤖 AI-Ready Layer (Machine Consumable)

```php
/**
 * Class TransientCacheManager
 * @spec-ref NFR-P1
 */
interface ICacheManager {
    // Retrieves cached HTML if valid
    public function getCachedContent(int $postId, string $currentModifiedDate): ?string;
    
    // Saves processed HTML to transient
    public function setCachedContent(int $postId, string $html, string $modifiedDate): bool;
    
    // Hooks into 'save_post' to invalidate cache
    public function invalidateCache(int $postId): void;
}
```

### 🔧 Implementation Layer (Human + AI)

- **Read Phase**:
  - During `the_content` filter, before running `DOMDocument`, check if transient exists.
  - Verify if the stored `lastModified` matches the current `get_post_modified_time()`. If mismatch, consider cache invalid.
- **Write Phase**:
  - After `DOMDocument` processing, store the output HTML and the `lastModified` timestamp.
  - Set expiration to a reasonable limit (e.g., 24 hours or 1 week) to prevent db bloat.
- **Invalidation Phase**:
  - Hook into `save_post` and `deleted_post` to explicitly delete the transient `delete_transient("jinc_a11y_content_{$postId}")`.

---

## 3. Business Rules

BR-CL-001: Zero DB Tables
  Precondition: System initializes cache layer.
  Input: `setCachedContent` method call.
  Invariant: Must not run raw SQL or create custom tables.
  Output/Action: Use `set_transient()`.
  Violation: E_ARCH_VIOLATION - Direct DB query detected.

BR-CL-002: Safe Invalidation
  Precondition: User updates a post in wp-admin.
  Input: `save_post` hook fires.
  Invariant: Users must see the updated accessible content immediately.
  Output/Action: Call `delete_transient()` for the specific post ID.

---

## 4. Critical Path — Gherkin

```gherkin
Feature: Transient Caching for Semantic Enforcer

  Scenario: Cache Miss — First load
    Given a post has not been cached
    When a visitor requests the post
    Then the "SemanticEnforcer" parses the HTML with DOMDocument
    And the output is saved to the Transients API with the post's modification date
    And the parsed HTML is displayed to the user

  Scenario: Cache Hit — Subsequent load
    Given a post is already cached in Transients API
    And the post has not been modified since caching
    When a visitor requests the post
    Then the system retrieves the HTML from the cache
    And skips the DOMDocument processing phase entirely

  Scenario: Cache Invalidation on Update
    Given a post is cached
    When an editor updates the post content in wp-admin
    Then the "save_post" hook triggers "invalidateCache"
    And the cache transient for that post is deleted
```
