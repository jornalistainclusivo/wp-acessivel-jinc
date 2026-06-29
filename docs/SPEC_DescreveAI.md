---
jinc-spec-version: 1.0.0
project-name: wp-acessivel-jinc
feature-name: DescreveAI Integration (Phase 4)
status: draft
prd-ref: Phase 4 Requirements
sdd-ref: DescreveAI Architecture Layer
related-branch: feat/phase-4-descreveai
coverage: "4/4 FRs mapped"
created-at: 2026-06-28
authors: Antigravity / JINC Engineering
---

# Spec: Integração DescreveAI (Phase 4)

## Coverage Report

| FR     | Requirement Summary          | Spec Element                                   | Status     |
| ------ | ---------------------------- | ---------------------------------------------- | ---------- |
| FR-001 | Opções do Painel (Aba 2) | `DescreveAIOptions` interface | 🟢 Covered |
| FR-002 | Cliente HTTP Multipart   | `DescreveAIClient` class + `analyze` endpoint | 🟢 Covered |
| FR-003 | Orquestração do Gatekeeper | `RestUploadValidator` flow + BR-UPLOAD-001 | 🟢 Covered |
| FR-004 | BDD & Timeouts Mockados  | `pre_http_request` tests in PHPUnit | 🟢 Covered |

---

## 1. Modelo de Dados (Aba 2)

### 🤖 AI-Ready Layer (Machine Consumable)

```typescript
/**
 * @spec-ref FR-001
 * Expansão de jinc_theme_options no banco de dados.
 */
interface DescreveAIOptions {
  descreveai_active: boolean;
  descreveai_endpoint: string; // URL
  descreveai_api_key: string;  // Treated as password in UI
  descreveai_timeout: number;  // min: 10, max: 60
}
```

### 🔧 Implementation Layer (Human + AI)
A options table do WordPress (`jinc_theme_options`) recebe quatro novas chaves referentes à IA.
Na Aba 2 do JINC Panel (UI), `descreveai_api_key` deve ser renderizado como um input `type="password"`.
O valor de `descreveai_timeout` será validado para aceitar exclusivamente valores inteiros entre 10 e 60 segundos (limite mínimo e máximo de expiração).

### 🔗 Traceability Layer (Human)
Decisão para evitar credenciais soltas no código e gerenciar com segurança a ativação global do sistema DescreveAI a nível de administrador.

---

## 2. HTTP Client Architecture

### 🤖 AI-Ready Layer (Machine Consumable)

```php
/**
 * @spec-ref FR-002
 */
interface IDescreveAIClient {
  /**
   * @param string $file_path Caminho absoluto do arquivo da imagem
   * @return array { success: bool, alt: string|null, error: string|null, status_code: int }
   */
  public function analyze(string $file_path): array;
}
```

### 🔧 Implementation Layer (Human + AI)
A classe `DescreveAIClient` executará a comunicação.
Restrições de arquitetura (Obrigatório):
- Usar **exclusivamente** `wp_remote_post()`. (Nenhuma dependência cURL direta).
- O payload precisa ser binário encapsulado em `multipart/form-data`. O boundary da requisição deve ser prefixado estritamente com `---JINC`.
- A imagem deve ser injetada na requisição utilizando `file_get_contents($file_path)`.
- O cliente HTTP deve injetar a `descreveai_api_key` (via Authorization Header, se aplicável, ou formulário, conforme definido pela API) e respeitar o `descreveai_timeout` para os `timeout args` do HTTP request do WP.

**I/O Example:**
Input: `/var/www/html/wp-content/uploads/2026/06/image.jpg`
Output HTTP 200 (Success): `{ "success": true, "alt": "Homem correndo no parque", "status_code": 200 }`
Output HTTP 500 (Fail): `{ "success": false, "error": "API Offline", "status_code": 500 }`

### 🔗 Traceability Layer (Human)
O uso mandatório do `wp_remote_post` garante conformidade com padrões WordPress VIP e padronização. Multipart manual evita uso de extensões diretas que podem estar bloqueadas em alguns ambientes de hospedagem.

---

## 3. Orquestração do Gatekeeper

### 🤖 AI-Ready Layer (Machine Consumable)

```typescript
// Fluxo lógico na validação de Upload (State Machine simplificada)
type UploadState = 
  | "ALT_PRESENT"     // Aprova imediato
  | "AI_INACTIVE"     // Bloqueio 403
  | "AI_ANALYZING"    // Chamando API
  | "AI_SUCCESS_200"  // Set _wp_attachment_image_alt silencioso e aprova
  | "AI_FAIL_ERROR"   // Bloqueio 403 (Human Intervention)
```

### 🔧 Implementation Layer (Human + AI)

A injeção ocorre no `RestUploadValidator.php`:

`BR-UPLOAD-001: Fluxo de Gatekeeper e Fallback da IA`
- **Precondition:** Um upload chega via REST e o arquivo de imagem **não** possui alt-text enviado na requisição.
- **Input:** Arquivo binário temporário/salvo.
- **Invariant:** Nenhuma imagem sem alt-text avaliado e verificado deve ultrapassar o gatekeeper.
- **Output/Action:** 
  1. Verifica se `descreveai_active` está ON. Se OFF -> Bloqueia (HTTP 403).
  2. Se ON -> Aciona `DescreveAIClient::analyze()`.
  3. Se a API responder HTTP 200 -> Injeta silenciosamente a resposta como post meta `_wp_attachment_image_alt` -> Aprova upload (201).
- **Violation:** Se a API falhar por Timeout, Erro 500, ou Endpoint Offline -> Gatekeeper encerra a execução e devolve bloqueio HTTP 403. Retorna erro forçando intervenção humana.

### 🔗 Traceability Layer (Human)
Garante a política Zero-Trust Accessibility. A falha da IA não aprova a imagem cegamente; pelo contrário, transfere a responsabilidade para o humano garantindo um repositório 100% com texto alternativo.

---

## 4. BDD / PHPUnit (Cenários)

### 🤖 AI-Ready Layer (Machine Consumable)

```gherkin
Feature: Integração DescreveAI no Filtro de Upload Rest

  Scenario: Happy path — API gera alt com sucesso e libera upload
    Given a configuração DescreveAI está ativa
    And a imagem enviada na rota de media REST não possui alt-text
    And o filtro `pre_http_request` mocka uma resposta síncrona HTTP 200 com alt "Teste IA"
    When o endpoint de upload finaliza a persistência
    Then o status code do upload é aprovado (HTTP 201/200)
    And o post meta `_wp_attachment_image_alt` contém "Teste IA"

  Scenario: Edge case — Timeout da API de IA forçado
    Given a configuração DescreveAI está ativa com timeout de 10s
    And o filtro `pre_http_request` mocka um evento de WP_Error (Timeout)
    When a imagem sem alt-text tenta realizar o upload
    Then a requisição é abortada e rejeitada (HTTP 403)
    And o upload reporta que a IA falhou, requerindo intervenção humana
```

### 🔧 Implementation Layer (Human + AI)
Para testar, todos os endpoints do cliente IA **devem** ser mockados. A exigência técnica é interceptar a chamada do `wp_remote_post` usando o filtro nativo `pre_http_request` do WordPress na suíte PHPUnit.

### 🔗 Traceability Layer (Human)
Isto garante que as falhas de rede (que seriam gargalos de tempo) não travem as execuções de CI/CD. Testes blindados validam perfeitamente a barreira de segurança sem consumir a quota da API real.
