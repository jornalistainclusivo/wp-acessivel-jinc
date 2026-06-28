---
jinc-prd-version: 1.0.0
project-name: WP Acessível JINC
feature-name: Ecossistema de Plugins de Acessibilidade Nativa WordPress
status: draft
related-branch: docs/prd-wp-acessivel
product-context: backend
created-at: 2026-06-25
last-updated: 2026-06-25
authors: JINC Apps (AI-assisted)
---

# WP Acessível JINC — Product Requirements Document

## 1. Executive Summary

| Field                        | Value                                                                                                                     |
| ---------------------------- | ------------------------------------------------------------------------------------------------------------------------- |
| **Vision**                   | Todo site WordPress publica conteúdo acessível por padrão — sem overlays, sem dependências externas, sem desculpas.        |
| **Target Users**             | Desenvolvedores WordPress, agências digitais, criadores de conteúdo                                                       |
| **Core Problem**             | O WordPress permite publicação de HTML semanticamente incorreto e inacessível, e o mercado mitiga com overlays cosméticos. |
| **Proposed Solution**        | Plugins que intervêm diretamente no core rendering (hooks/filters PHP) e no Gutenberg para forçar compliance WCAG 2.2 AAA. |
| **Strategic Alignment**      | Missão JINC: jornalismo inclusivo — acessibilidade é pré-condição, não feature.                                           |
| **Key Differentiator**       | Correção no DOM renderizado pelo servidor (DOMDocument), não JavaScript client-side. Zero overlays. Open-source.           |
| **Success Metric (Primary)** | 100% dos sites com o plugin ativo passam em varredura automatizada WCAG 2.2 AA (target AAA) para critérios cobertos.       |
| **Timeline Target**          | MVP (Semantic Enforcer) em produção em 8 semanas.                                                                         |
| **Resource Estimate**        | 1 engenheiro + IA assistente × 8 semanas (MVP)                                                                            |

---

## 2. Problem and Opportunity

### Problem Definition

O ecossistema WordPress — responsável por ~43% da web — possui um problema estrutural: não impõe semântica nem acessibilidade no momento da criação ou publicação de conteúdo. Autores publicam livremente cabeçalhos fora de ordem (H1 → H4 → H2), imagens sem `alt text`, blocos sem landmarks ARIA, e cores com contraste insuficiente.

A resposta do mercado são **overlays de acessibilidade** — widgets JavaScript que prometem "consertar" o site após o carregamento. Esses overlays:

1. **Não corrigem o DOM real** — aplicam patches cosméticos no client-side que falham em auditores automatizados e screen readers.
2. **Custam R$200-800/mês** por site — criando dependência financeira recorrente sem compliance real.
3. **Foram processados judicialmente** nos EUA por violação da ADA (Americans with Disabilities Act), com precedentes contra AccessiBe, AudioEye e UserWay.
4. **Degradam performance** — injetam 200-500KB de JavaScript adicional, prejudicando Core Web Vitals.

O resultado: sites que pagam por "acessibilidade" continuam inacessíveis. Jornalistas com deficiência visual não conseguem navegar o conteúdo publicado. A credibilidade editorial é comprometida.

**Quantified Impact (current state):**

- **82% dos sites WordPress** falham em pelo menos 5 critérios WCAG 2.1 A (WebAIM Million Report, 2024)
- **96.3% das home pages** possuem erros detectáveis automaticamente — sendo cabeçalhos fora de ordem e alt text ausente os mais prevalentes
- **Custo de overlay por agência:** R$2.400-9.600/ano por cliente — sem evidência de compliance real

### Root Cause Analysis

1. **Why 1:** Sites WordPress são inacessíveis → Porque autores publicam HTML mal-estruturado
2. **Why 2:** Autores publicam HTML mal-estruturado → Porque o WordPress não valida semântica nem acessibilidade antes da publicação
3. **Why 3:** WordPress não valida → Porque o core é permissivo por design — prioriza facilidade de uso sobre rigor
4. **Root cause:** Não existe um mecanismo nativo no pipeline de rendering do WordPress que force compliance de acessibilidade no momento da geração do HTML — nem na criação (editor) nem na saída (`the_content`).

### Opportunity

| Dimension                 | Assessment                                                                                                                       |
| ------------------------- | -------------------------------------------------------------------------------------------------------------------------------- |
| **Market size**           | ~810 milhões de sites WordPress ativos. Segmento institucional/editorial (agências, jornais, gov) = ~2 milhões de sites target.  |
| **Revenue opportunity**   | Modelo freemium: plugin gratuito (core enforcement) + módulos pro (DescreveAI, relatórios VPAT). Target: 500 instalações ativas em 6 meses. |
| **Mission alignment**     | Pilar central da JINC: nenhum conteúdo jornalístico deve excluir leitores com deficiência. Plugin é infraestrutura para a missão. |
| **Competitive landscape** | Overlays (AccessiBe, UserWay): $49-299/mês, compliance cosmético. WP Accessibility (plugin gratuito): básico, sem enforcement. Nenhum concorrente intervém no DOM server-side. |
| **Window of opportunity** | European Accessibility Act (EAA) entra em vigor em junho 2025. Sites governamentais e corporativos precisarão de compliance real, não overlays. |

---

## 3. User Requirements

### Primary Personas

**Persona 1: DevOps Diana — Desenvolvedora WordPress em Agência**

- **Context:** Mantém 15-30 sites de clientes. Precisa garantir acessibilidade sem auditar cada página manualmente.
- **Goals:** Automatizar compliance WCAG nos sites que entrega. Reduzir custo de overlay por cliente.
- **Pain points:** Overlays caros que não passam em auditorias. Clientes reclamam quando recebem notificações de não-compliance. Nenhum plugin faz enforcement real.
- **Tech literacy:** Alta — confortável com hooks, filters, WP-CLI.
- **Quote:** _"Eu preciso de um plugin que resolva o problema no servidor, não um band-aid de JavaScript."_

**Persona 2: Editor Eduardo — Criador de Conteúdo em Portal Jornalístico**

- **Context:** Publica 5-10 artigos/dia. Usa Gutenberg. Não tem conhecimento técnico profundo sobre semântica HTML.
- **Goals:** Publicar conteúdo sem se preocupar se a estrutura está "certa". Receber feedback imediato quando algo está errado.
- **Pain points:** Não sabe que H1→H4→H2 é um problema. Não entende landmarks ARIA. Não tem tempo para aprender WCAG.
- **Tech literacy:** Baixa — usa o editor visual apenas.
- **Disability/accessibility context:** Pode ter colegas com deficiência visual que consomem o conteúdo publicado.
- **Quote:** _"Se eu cometi um erro de acessibilidade, o plugin deveria corrigir automaticamente ou me avisar antes de publicar."_

### Jobs to be Done

- **JTBD-001:** Quando eu ativo o plugin em um site cliente, quero que a semântica HTML seja corrigida automaticamente no output, para que o site passe em auditorias WCAG sem intervenção manual em cada página.
- **JTBD-002:** Quando eu publico um artigo no Gutenberg, quero ser avisado se meus cabeçalhos estão fora de ordem, para que eu corrija antes que o conteúdo vá ao ar.
- **JTBD-003:** Quando eu faço upload de uma imagem, quero ser bloqueado de publicar se não preenchi o `alt text`, para que nenhuma imagem sem descrição chegue ao leitor.
- **JTBD-004:** Quando eu escolho cores para um bloco no Gutenberg, quero ver imediatamente se o contraste atende à WCAG AAA, para que eu não publique texto ilegível.
- **JTBD-005:** Quando eu preciso demonstrar compliance a um cliente ou regulador, quero um relatório de auditoria gerado pelo plugin, para que eu tenha evidência técnica sem ferramentas externas.

### User Journey Map

```
[Persona: DevOps Diana] — Flow: Ativação do Plugin em Site de Cliente

Current (Painful) Journey:

1. Instala overlay pago → 😤 Pain: R$200-800/mês por site, compliance cosmético
2. Recebe reclamação de auditoria → ⏱️ Friction: 4-8h de trabalho manual para corrigir HTML
3. Cliente questiona valor do overlay → 🔴 Blocker: sem evidência de compliance real

Future (With WP Acessível JINC) Journey:

1. Instala plugin gratuito, ativa Semantic Enforcer → ✅ Semântica HTML corrigida automaticamente
2. Roda auditoria → ✅ 0 violações de cabeçalho e landmarks
3. Cliente recebe relatório de compliance → ✅ Evidência técnica real, custo zero
```

### User Stories

**Epic: Semantic Enforcement — Correção Automática de Semântica HTML**

**US-001: Correção automática de hierarquia de cabeçalhos**
- As a **desenvolvedor WordPress (Diana)**
- I want to **ativar o plugin e ter a hierarquia de cabeçalhos corrigida automaticamente no output HTML**
- So that **todos os sites que mantenho tenham sequência H1→H2→H3 correta sem editar conteúdo existente**
- **Acceptance Criteria:**
  - [ ] Given um post com H1→H4→H2, When `the_content` é renderizado, Then output contém H1→H2→H3 em sequência lógica
  - [ ] Given um post sem cabeçalhos, When `the_content` é renderizado, Then o conteúdo passa inalterado
  - [ ] Given um post com H2→H2→H2 (mesmo nível), When `the_content` é renderizado, Then mantém H2→H2→H2 (sequência válida)

**US-002: Injeção automática de landmarks ARIA**
- As a **desenvolvedor WordPress (Diana)**
- I want to **ter blocos de conteúdo automaticamente envelopados com landmarks ARIA apropriados**
- So that **screen readers possam navegar o conteúdo por regiões sem alteração manual nos templates**
- **Acceptance Criteria:**
  - [ ] Given conteúdo com blocos de texto e imagens, When `the_content` é renderizado, Then o bloco principal está envelopado em `<article>` com `role="article"`
  - [ ] Given conteúdo com lista de navegação detectável, When renderizado, Then está envelopado em `<nav>` com `aria-label`
  - [ ] Given conteúdo já com landmarks corretos, When renderizado, Then não duplica landmarks existentes

**US-003: Bloqueio de upload de imagem sem alt text**
- As a **editor (Eduardo)**
- I want to **ser impedido de publicar um artigo se alguma imagem não tem alt text**
- So that **nenhuma imagem inacessível chegue ao leitor**
- **Acceptance Criteria:**
  - [ ] Given upload de imagem no Media Library, When `alt text` está vazio, Then exibe aviso persistente no painel
  - [ ] Given artigo com imagem sem alt text, When clico em "Publicar", Then publicação é bloqueada com mensagem descritiva
  - [ ] Given imagem decorativa, When marco como "decorativa", Then `alt=""` é aceito (vazio intencional)

**US-004: Validação de contraste no Gutenberg**
- As a **editor (Eduardo)**
- I want to **ver imediatamente se as cores que escolhi para um bloco atendem à WCAG AAA**
- So that **eu corrija o contraste antes de publicar**
- **Acceptance Criteria:**
  - [ ] Given bloco com texto claro em fundo claro, When altero a cor, Then indicador visual mostra ratio atual e status (Pass/Fail AAA)
  - [ ] Given ratio < 7:1, When tento publicar, Then publicação é bloqueada com sugestão de cor alternativa
  - [ ] Given ratio ≥ 7:1, When verifico, Then indicador mostra "✅ AAA Compliant"

---

## 4. Functional Requirements

### Must-Have (MVP — P0)

| ID     | Requirement                                                                                                | User Story | Notes                                                    |
| ------ | ---------------------------------------------------------------------------------------------------------- | ---------- | -------------------------------------------------------- |
| FR-001 | Sistema deve corrigir automaticamente a hierarquia de cabeçalhos H1-H6 no output de `the_content`          | US-001     | Via DOMDocument, nunca Regex. Cache com Transients API.  |
| FR-002 | Sistema deve envelopar blocos de conteúdo com landmarks ARIA sem duplicar landmarks existentes              | US-002     | Detectar `<main>`, `<nav>`, `<article>` existentes.      |
| FR-003 | Todo processamento de DOM deve usar `DOMDocument::loadHTML()` — Regex é proibido para manipulação HTML      | US-001/002 | Restrição arquitetural inegociável.                      |
| FR-004 | Resultados de processamento DOM devem ser cacheados via Transients API para evitar reprocessamento          | US-001/002 | TTL configurável. Invalidar em `save_post`.              |
| FR-005 | Plugin não deve criar tabelas customizadas no banco de dados WordPress                                     | —          | Lean Database: usar `wp_options` e `postmeta` apenas.    |
| FR-006 | Todo código PHP deve usar `declare(strict_types=1)` sem exceção                                            | —          | Restrição de qualidade inegociável.                      |

### Should-Have (V1 — P1)

| ID     | Requirement                                                                           | Rationale for deferral                                      |
| ------ | ------------------------------------------------------------------------------------- | ----------------------------------------------------------- |
| FR-010 | Sistema deve bloquear publicação de artigos com imagens sem alt text no Gutenberg      | Requer integração com Block Editor JS — complexidade maior. |
| FR-011 | Sistema deve validar contraste de cores em blocos Gutenberg contra WCAG AAA (7:1 ratio)| Requer componente React no Block Editor sidebar.            |
| FR-012 | Sistema deve exibir painel de status de acessibilidade no editor de posts              | Depende de FR-010 e FR-011 estarem estáveis.                |

### Nice-to-Have (Backlog — P2)

| ID     | Requirement                                                                  | Trigger for promotion                                       |
| ------ | ---------------------------------------------------------------------------- | ----------------------------------------------------------- |
| FR-020 | Integração com DescreveAI para geração automática de alt text via IA         | Quando DescreveAI API estiver estável e documentada.        |
| FR-021 | Relatório VPAT exportável em PDF para compliance regulatório                 | Quando demanda de agências corporativas for validada.       |
| FR-022 | Dashboard administrativo com score de acessibilidade do site                 | Quando 100+ instalações ativas.                             |

### Explicit Non-Goals

- **Not in scope:** Correção de acessibilidade em temas — o plugin atua no conteúdo (`the_content`), não no template. Temas devem ser acessíveis por conta própria.
- **Not in scope:** Overlay ou widget flutuante — o plugin corrige o DOM real, nunca injeta camada visual de "acessibilidade".
- **Not in scope:** Suporte a PHP < 8.1 — tipagem estrita é requisito arquitetural. Sites em PHP 7.x devem atualizar.
- **Not in scope:** Compatibilidade com Classic Editor para funcionalidades de Block Editor (FR-010, FR-011) — Gutenberg only.

### Feature Prioritization

| Feature                        | Business Value | User Impact | Effort | Priority | Phase |
| ------------------------------ | -------------- | ----------- | ------ | -------- | ----- |
| Semantic Enforcer (H1-H6)     | High           | High        | M      | P0       | MVP   |
| ARIA Landmarks Injection       | High           | High        | M      | P0       | MVP   |
| Transients Cache Layer         | Medium         | Medium      | S      | P0       | MVP   |
| Media Gatekeeper (alt text)   | High           | High        | L      | P1       | V1    |
| Contrast Auditor (Gutenberg)  | High           | Medium      | L      | P1       | V1    |
| DescreveAI Integration         | Medium         | High        | XL     | P2       | Scale |

---

## 5. Accessibility Requirements

**⚠️ MANDATORY — JINC standard: WCAG 2.2 AAA**

| Requirement                                 | WCAG Criterion | Target Level | Notes                                                       |
| ------------------------------------------- | -------------- | ------------ | ----------------------------------------------------------- |
| Heading hierarchy logical (H1→H2→H3)       | 1.3.1          | A            | Enforced automaticamente pelo Semantic Enforcer              |
| Landmarks ARIA present on content regions   | 1.3.1, 4.1.2   | A            | Injetados automaticamente; `<main>`, `<article>`, `<nav>`   |
| Images have alt text                        | 1.1.1          | A            | Bloqueio de publicação em V1 (FR-010)                        |
| Text contrast ratio ≥ 7:1                   | 1.4.6          | AAA          | Validação no Gutenberg em V1 (FR-011)                        |
| No auto-playing media                       | 1.4.2          | A            | Plugin não injeta mídia. Validação passiva.                  |
| Focus indicator visible                     | 2.4.7          | AA           | Admin UI do plugin deve ter `focus-visible` ring ≥ 3px       |
| Skip navigation links                       | 2.4.1          | A            | Plugin injeta `Skip to Content` se tema não possui           |
| Keyboard navigation (no traps)              | 2.1.1, 2.1.2   | A/AAA        | Todos os controles do plugin navegáveis via Tab              |
| Dynamic notifications via `aria-live`       | 4.1.3          | AA           | Todas as notificações do plugin usam `aria-live="polite"`    |
| Content reflow at 400% zoom                 | 1.4.10         | AA           | Admin UI responsiva. Sem scroll horizontal.                  |

**Specific product accessibility concerns:**

- O Semantic Enforcer altera o DOM renderizado — deve preservar `id`, `class`, e atributos ARIA pré-existentes ao corrigir cabeçalhos.
- A injeção de landmarks ARIA não deve criar landmarks redundantes se o tema já os fornece.
- Notificações de erro no Block Editor (bloqueio de publicação) devem ser anunciadas via `aria-live` para screen readers.

---

## 6. User Experience Requirements

### Design Principles (for this product)

1. **Invisibilidade quando correto:** O plugin não deve ser perceptível quando o conteúdo já está acessível. Zero ruído visual ou funcional para autores que seguem boas práticas.
2. **Intervenção direta, não consultiva:** O plugin corrige problemas, não apenas os relata. Relatórios são secundários — o output HTML correto é primário.
3. **Feedback apenas quando necessário:** Mensagens de erro/aviso aparecem somente quando há ação requerida do autor. Sem dashboards intrusivos.

### JINC Design System Constraints

- Color: Neutral palette only (`neutral-50` to `neutral-900`). No purple/violet.
- Content width: Max `70ch` for readable text in admin panels.
- Typography: System fonts (WordPress admin defaults). Sem fontes externas.
- Motion: All animations must respect `prefers-reduced-motion`.

### Interface Requirements

- Admin settings page deve ser acessível em `/wp-admin/options-general.php?page=wp-acessivel-jinc`
- Todas as opções configuráveis via WP-CLI (`wp jinc-a11y [command]`)
- Interface do Gutenberg sidebar (V1) deve funcionar com teclado e screen reader
- Nenhum popup, modal ou dialog que capture foco sem controle de saída

---

## 7. Non-Functional Requirements

### Performance

| Metric                            | Requirement         | Notes                                                         |
| --------------------------------- | ------------------- | ------------------------------------------------------------- |
| Overhead de `the_content` filter  | < 5ms (p95)         | Com cache Transients ativo. Sem cache: < 50ms para 10KB HTML. |
| Memory footprint do plugin        | < 2MB adicionais    | Medido com `memory_get_peak_usage()` em request típico.       |
| Transient cache hit rate          | > 90% em steady state | Invalidação em `save_post` e `update_option`.               |
| Impacto em Core Web Vitals        | Zero degradação     | LCP, CLS, INP não devem ser afetados.                         |

### Security

- Sanitização: Todo input do admin UI sanitizado com `sanitize_text_field()`, `wp_kses()`, `absint()`
- Nonce verification em todos os forms e AJAX calls
- Capability check: `manage_options` para configurações, `edit_posts` para notificações de editor
- Sem chamadas externas (HTTP requests) no MVP — tudo processado localmente
- Nenhum segredo hardcoded — configurações em `wp_options` com `update_option()`

### Reliability

- O plugin NUNCA deve quebrar o output do site. Se `DOMDocument::loadHTML()` falhar, retornar o conteúdo original inalterado.
- Graceful degradation: Em caso de erro no plugin, o site deve funcionar normalmente — plugin silencia exceções com `try/catch` e log para `error_log()`.
- Compatibilidade: WordPress 6.4+ (mínimo). PHP 8.1+ (obrigatório).

### Scalability

| Dimension              | MVP Baseline           | 12-Month Target        |
| ---------------------- | ---------------------- | ---------------------- |
| Instalações ativas     | 50                     | 500                    |
| Posts processados/hora | 1.000                  | 10.000                 |
| Tamanho de conteúdo    | Até 50KB por post      | Até 200KB por post     |

---

## 8. Success Metrics and Analytics

### Primary KPIs (North Star Metrics)

| Metric                                        | Baseline | MVP Target | V1 Target | Measurement Method                     |
| --------------------------------------------- | -------- | ---------- | --------- | -------------------------------------- |
| % de posts com hierarquia H1-H6 correta       | ~18%     | 100%       | 100%      | Auditoria com `wp jinc-a11y audit`     |
| Violações de heading em sites com plugin ativo | ~5/page  | 0          | 0         | Scanner automatizado (axe-core)        |

### Secondary KPIs

| Metric                                   | Baseline | Target | Notes                             |
| ---------------------------------------- | -------- | ------ | --------------------------------- |
| Instalações ativas no WordPress.org      | 0        | 500    | Em 6 meses após publicação.       |
| Rating no WordPress.org                  | N/A      | ≥ 4.5★ | Meta de qualidade.                |
| Tempo médio de processamento (p95)       | N/A      | < 5ms  | Com Transients cache ativo.       |
| Tickets de suporte por 100 instalações   | N/A      | < 2    | Indicador de estabilidade.        |

### Analytics Implementation

Plugin não coleta analytics do usuário (zero tracking). Métricas são obtidas via:

- `wp jinc-a11y audit --format=json` — comando WP-CLI para auditoria local
- WordPress.org plugin stats (instalações, ratings) — dados públicos
- GitHub issues/stars — feedback qualitativo

### Guardrail Metrics (Do Not Worsen)

| Metric                          | Current Baseline | Alert Threshold                    |
| ------------------------------- | ---------------- | ---------------------------------- |
| Tempo de resposta do site       | Baseline do tema | > 10% de degradação = investigar   |
| Core Web Vitals (LCP/CLS/INP)  | Score do tema    | Qualquer degradação = bug crítico  |
| Conteúdo visualmente alterado   | 0 ocorrências    | > 0 = bug crítico P0              |

---

## 9. Implementation Considerations

### Technical Context

- **Existing stack:** WordPress 6.4+, PHP 8.1+, MySQL/MariaDB (via `$wpdb`), Gutenberg Block Editor (React)
- **Integration points:** `the_content` filter (core rendering), `save_post` action (cache invalidation), `wp_handle_upload` filter (media gatekeeper), Block Editor sidebar (Gutenberg SlotFill API)
- **Data requirements:** Configurações globais em `wp_options` (key: `wp_acessivel_jinc_settings`). Cache em Transients. Zero tabelas customizadas.
- **Migration needs:** Nenhuma migração de dados. Plugin é aditivo — não altera dados persistidos.
- **Platform targets:** WordPress backend (PHP) + Block Editor (React/JS). Frontend: output HTML server-side.

### Constraints

- **Compliance:** WCAG 2.2 AAA (target). WordPress Plugin Guidelines. GPL-2.0-or-later license.
- **Performance:** Overhead < 5ms em `the_content` com cache. Zero impacto em CWV.
- **PHP:** `declare(strict_types=1)` obrigatório. PHP 8.1+ features (enums, fibers, readonly props, named args).
- **Database:** Zero tabelas customizadas. Lean Database principle. `wp_options` + Transients apenas.
- **DOM:** `DOMDocument::loadHTML()` exclusivamente. Regex para HTML é proibido.

### Open Technical Questions (for SDD)

- 🟡 **Prioridade de filtro:** Qual `priority` usar em `add_filter('the_content', ...)` para garantir execução após todos os shortcodes e blocos, mas antes de cache plugins? Sugestão: `PHP_INT_MAX - 10`.
- 🟡 **Encoding:** Como lidar com `DOMDocument` e UTF-8 (o `loadHTML()` do PHP tem issues conhecidos com multibyte)? Sugestão: wrapper `mb_encode_numericentity()`.
- 🟡 **Transient key strategy:** Hash do conteúdo (`md5($content)`) ou `post_id + post_modified`? Trade-offs de invalidação.

---

## 10. Risk Assessment

### Risk Register

| #     | Risk                                                                  | Probability | Impact | Mitigation                                                               | Owner       |
| ----- | --------------------------------------------------------------------- | ----------- | ------ | ------------------------------------------------------------------------ | ----------- |
| R-001 | `DOMDocument::loadHTML()` altera whitespace/encoding do HTML           | High        | Medium | Wrapper com `LIBXML_HTML_NOIMPLIED \| LIBXML_HTML_NODEFDTD`. Testes extensivos. | Engineering |
| R-002 | Conflito com plugins de cache (WP Super Cache, W3 Total Cache)        | Medium      | High   | Invalidar transients em `save_post`. Documentar hook priorities.         | Engineering |
| R-003 | Temas que já possuem landmarks — duplicação de ARIA                   | Medium      | Medium | Detecção de landmarks existentes antes de injetar. Idempotência.         | Engineering |
| R-004 | Baixa adoção: desenvolvedores preferem overlays "fáceis"              | Medium      | High   | Marketing educacional. Comparativo técnico overlay vs. enforcement real. | Product     |
| R-005 | PHP 8.1 minimum exclui hospedagens compartilhadas antigas             | Low         | Medium | Documentar claramente. WordPress.org trends: 70%+ já em PHP 8.0+.       | Product     |

### Assumptions and Validations Needed

| Assumption                                                              | How to Validate                        | Deadline    | Status  |
| ----------------------------------------------------------------------- | -------------------------------------- | ----------- | ------- |
| DOMDocument corrige headings sem alterar layout visual                  | Testes automatizados com 50+ fixtures  | MVP Week 3  | Pending |
| Transients API é performante o suficiente para cache de DOM             | Benchmark com 1000 posts               | MVP Week 2  | Pending |
| Desenvolvedores preferem enforcement automático a relatórios            | 10 entrevistas com devs WordPress      | MVP Week 4  | Pending |

---

## Downstream Pipeline

This PRD is the input for:

- **SDD (Architecture):** Use `sdd-creator`. Sections to focus on: Technical Context (Sec. 9), Non-Functional Requirements (Sec. 7), Constraints (PHP, DOMDocument, Lean Database).
- **Spec (Technical Spec):** Use `spec-creator`. Sections to focus on: Functional Requirements (Sec. 4), Acceptance Criteria (Sec. 3 — User Stories), Business Rules implied by FR constraints.

| Status                 | Value                                                     |
| ---------------------- | --------------------------------------------------------- |
| PRD Status             | draft                                                     |
| Ready for SDD?         | 🟢 Yes — all constraints and requirements are specified.  |
| Stakeholder sign-off   | 🟡 Pending human review.                                  |
