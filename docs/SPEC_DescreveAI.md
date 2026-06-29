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

## 3. Arquitetura Assíncrona (AJAX & Quarantine)

### 🤖 AI-Ready Layer (Machine Consumable)

```typescript
// Fluxo lógico na validação Assíncrona (State Machine)
type UploadState = 
  | "ALT_PRESENT"     // Aprova imediato
  | "AI_INACTIVE"     // Bloqueio 403
  | "AI_QUARANTINE"   // Aprova com alt temporário e status 'pending'
  | "AI_PROCESSING"   // Processamento via AJAX
  | "AI_SUCCESS"      // Alt definitivo salvo e 'pending' removido
  | "AI_FAILED"       // Falha na IA, status muda para 'failed'
```

### 🔧 Implementation Layer (Human + AI)

`BR-UPLOAD-001: Quarentena de Mídia (RestUploadValidator)`
- Quando o upload chega via REST sem alt-text, e a DescreveAI está ATIVA, o `RestUploadValidator` **NÃO** deve instanciar o `DescreveAIClient`.
- **Output:** Injeta silenciosamente o post meta `_wp_attachment_image_alt` com o valor `[JINC: Processando IA...]`.
- Injeta o post meta `_jinc_ai_status` com o valor `pending`.
- Aprova a persistência da imagem (HTTP 201).

`BR-UPLOAD-002: Gatilho Frontend (assets/js/jinc-media-ai.js)`
- Um script JS enfileirado (`admin_enqueue_scripts`) intercepta eventos do `wp.Uploader` / `wp.media`.
- Ao concluir um upload, verifica se o anexo possui o status `pending` (via resposta REST ou extração).
- Dispara uma requisição POST assíncrona para `admin-ajax.php?action=jinc_process_ai`, passando o ID do anexo.

`BR-UPLOAD-003: Processador Background (AsyncAIProcessor)`
- Hook: `wp_ajax_jinc_process_ai`
- Recebe o ID do anexo.
- Instancia o `DescreveAIClient` e executa a requisição real de forma demorada sem travar a thread de upload do usuário.
- Se Sucesso: Substitui o alt `[JINC: Processando IA...]` pela resposta gerada, e exclui o meta `_jinc_ai_status`.
- Se Falha: Retorna um erro JSON e atualiza o `_jinc_ai_status` para `failed` (permitindo retentativa manual na UI ou apenas alertando o usuário).

### 🔗 Traceability Layer (Human)
A decisão pela assincronicidade visa uma UX fluida ("Seamless"). Timeout de APIs LLMs não devem estourar limites do servidor ou gerar ansiedade no usuário. A quarentena garante que a imagem não fique sem alt temporário enquanto a rede processa.

---

## 4. BDD / PHPUnit (Cenários)

### 🤖 AI-Ready Layer (Machine Consumable)

```gherkin
Feature: Integração DescreveAI via Arquitetura Assíncrona

  Scenario: Fase 1 - Upload entra em Quarentena
    Given a configuração DescreveAI está ativa
    And a imagem enviada na rota de media REST não possui alt-text
    When o RestUploadValidator intercepta a requisição
    Then o status code do upload é aprovado (HTTP 201/200)
    And o post meta `_wp_attachment_image_alt` contém "[JINC: Processando IA...]"
    And o post meta `_jinc_ai_status` é marcado como "pending"

  Scenario: Fase 2 - Processamento Background Sucesso
    Given uma imagem existe no banco com `_jinc_ai_status` igual a "pending"
    When a requisição AJAX atinge `wp_ajax_jinc_process_ai`
    And o filtro `pre_http_request` mocka uma resposta HTTP 200 com alt "Teste IA"
    Then o AsyncAIProcessor finaliza com sucesso
    And o post meta `_wp_attachment_image_alt` é atualizado para "Teste IA"
    And o post meta `_jinc_ai_status` é excluído

  Scenario: Fase 2 - Processamento Background Falha
    Given uma imagem existe no banco com `_jinc_ai_status` igual a "pending"
    When a requisição AJAX atinge `wp_ajax_jinc_process_ai`
    And o filtro `pre_http_request` mocka uma resposta HTTP 500 (Timeout/Erro)
    Then o AsyncAIProcessor devolve um JSON de erro
    And o post meta `_jinc_ai_status` é atualizado para "failed"
    And o alt temporário "[JINC: Processando IA...]" é mantido para intervenção manual
```

### 🔧 Implementation Layer (Human + AI)
Para testar, a primeira fase testa o bypass com injeção de metadata. A segunda fase testa estritamente a nova classe `AsyncAIProcessor` simulando chamadas HTTP mockadas através do filtro `pre_http_request`.

### 🔗 Traceability Layer (Human)
Isto garante que as falhas de rede não travem uploads, e que o fluxo de BDD agora abrange a esteira completa (Upload -> Quarentena -> AJAX -> Resolução).
