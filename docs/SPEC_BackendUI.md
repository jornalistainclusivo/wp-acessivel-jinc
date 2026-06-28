---
jinc-spec-version: 1.0.0
project-name: wp-acessivel-jinc
feature-name: Phase 3.7 - Backend UI/UX e CSS Shield
status: draft
prd-ref: N/A
sdd-ref: N/A
related-branch: feat/phase-3.7-backend-ui
coverage: "1/1 FRs mapped"
created-at: 2026-06-28
authors: Antigravity
---

# Especificação Técnica: Phase 3.7 - Backend UI/UX e CSS Shield

Esta especificação define o escopo arquitetural para a refatoração da página de configurações (`SettingsPage.php`), implementando um sistema de abas nativo no WordPress, expansão do motor de temas, e a implementação do padrão **CSS Shield** para isolamento estrutural.

## 1. Arquitetura de Abas (WP Settings API)

A classe `SettingsPage` será refatorada para adotar a navegação por abas nativa do WordPress (`nav-tab-wrapper`). O sistema deve permitir escalabilidade horizontal sem poluir a interface.

### Aba 1: Configurações Visuais
Responsável pelo motor de temas e customizações estruturais da barra. Centraliza todos os campos de UI existentes e novos campos definidos no Mapeamento de Dados.

### Aba 2: DescreveAI (Placeholder)
Espaço reservado para futura integração de inteligência artificial.
- **Conteúdo Inicial:** Um aviso estático informativo e amigável, preparando o terreno para o módulo de IA ("Em breve: Inteligência Artificial para descrições de imagens automáticas").
- **Requisito Técnico:** O roteamento lógico (via parâmetro `?page=jinc-wp-acessivel&tab=descreveai`) deve ser plenamente funcional e isolar o carregamento da API de opções da Aba 1.

---

## 2. Mapeamento de Dados (Aba 1)

O array consolidado `jinc_theme_options` será expandido. Os seguintes atributos compõem o novo contrato do modelo de dados visual:

| Campo | Tipo | Regra de Sanitização | Valor Padrão (Default) |
| :--- | :--- | :--- | :--- |
| `frontend_title` | String | `sanitize_text_field` | `""` (Vazio) |
| `a11y_id` | String | `sanitize_title` | `"jinc-a11y-bar"` |
| `layout` | Dropdown | Valores restritos permitidos | `top_bar` |
| `position` | Dropdown | Valores restritos permitidos | `bottom_right` |
| `align` | Dropdown | Valores restritos permitidos | `center` |
| `button_style` | Dropdown | `quadrado`, `arredondado`, `pilula`, `text_only` | `arredondado` |
| `bar_size` | Dropdown | `small`, `medium`, `large` | `medium` |
| `bg_color` | Color Hex | `sanitize_hex_color` | `#1a1a2e` |
| `text_color` | Color Hex | `sanitize_hex_color` | `#e0e0e0` |
| `text_hover_color` | Color Hex | `sanitize_hex_color` | `#ffffff` |
| `accent_color` | Color Hex | `sanitize_hex_color` | `#00d4aa` |
| `accent_hover_color` | Color Hex | `sanitize_hex_color` | `#00f0c0` |
| `font_family` | Dropdown | Valores restritos permitidos | `system-ui...` |
| `show_icons` | Checkbox / String | `1` ou `0` | `1` |

---

## 3. Regra Arquitetural Crítica: CSS Shield

Para proteger a estilização da barra contra quebras causadas por customização de ID por parte do usuário final, o plugin adotará o padrão **CSS Shield**.

**Diretrizes:**
1. **Desacoplamento de ID:** O arquivo `jinc-bar.css` deve **abandonar completamente** o uso do seletor `#jinc-a11y-bar`.
2. **Nova Âncora Estrutural:** Toda a estilização (inclusive regras do *High Contrast Mode*) passará a utilizar exclusivamente a classe raiz `.jinc-a11y-wrapper`.
3. **Injeção Dinâmica:** O `BarInjector.php` imprimirá o atributo `id` no container pai através do valor resgatado da opção `a11y_id` (fallback: `"jinc-a11y-bar"`), mas as regras CSS só reconhecerão a classe isolada.

*Isso permite que o usuário altere livremente o atributo `id` para contornar bloqueadores de anúncios e temas que colidem com "a11y", sem perder a integridade visual da ferramenta.*

---

## 4. Casos de Teste (PHPUnit - TDD)

Os testes deverão guiar o desenvolvimento através de Asserções BDD (Behavior-Driven Development).

### Feature: CSS Shield e Desacoplamento

```gherkin
Cenário: Injeção de ID dinâmico não quebra a hierarquia CSS
  Dado que o usuário define o a11y_id como "barra-personalizada-jinc"
  Quando o HTML da barra for injetado
  Então o container `<nav>` (ou wrapper pai) deve conter o id "barra-personalizada-jinc"
  E o container `<nav>` (ou wrapper pai) deve conter a classe ".jinc-a11y-wrapper"
  E a saída CSS estática (jinc-bar.css) e injetada (ThemeEngine) não devem conter o seletor "#jinc-a11y-bar"
```

### Feature: Roteamento de Abas Nativas (Settings API)

```gherkin
Cenário: Roteamento para a aba padrão (Aba 1)
  Dado que o administrador visita a página "?page=jinc-wp-acessivel"
  Quando nenhuma aba é passada na querystring
  Então os campos do grupo "Configurações Visuais" devem ser registrados e renderizados

Cenário: Roteamento para a aba DescreveAI (Aba 2)
  Dado que o administrador visita a página "?page=jinc-wp-acessivel&tab=descreveai"
  Quando a aba DescreveAI é acionada
  Então apenas o placeholder estrutural da IA deve ser impresso
  E os campos visuais não devem ser renderizados
```
