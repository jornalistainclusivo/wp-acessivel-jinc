# SPEC_CacheLayer.md

**Phase 3.8 — Transients Cache Layer**

## 1. Visão Geral (Goal)

A Phase 1 (Semantic Enforcer) utiliza `DOMDocument` para analisar e reestruturar cabeçalhos HTML e atributos ARIA no gancho `the_content`. Essa operação de I/O intensivo sobre strings e manipulação de árvore DOM é pesada e escala de forma ineficiente em sites com alto tráfego.

O objetivo da **Phase 3.8** é implementar a classe `CacheManager` que se integra com a **Transients API** do WordPress para guardar o HTML já processado de cada post, evitando múltiplas passagens pela árvore DOM.

## 2. Arquitetura do Cache (Storage Mechanism)

### 2.1. Classe `CacheManager`

A classe `CacheManager` será introduzida na camada de Serviços/Módulos para gerenciar todo o ciclo de vida do cache (leitura, gravação, invalidação).

### 2.2. Estratégia de Chave

Para evitar vazamentos de dados, condições de corrida e conflitos entre conteúdos atualizados, a chave do transient será baseada no ID do Post e na data exata de modificação do mesmo.

* **Formato da Chave:** `jinc_a11y_content_{post_id}_{timestamp_de_modificacao}`
* **Exemplo de Chave:** `jinc_a11y_content_402_1718293021`
* **Tempo de Expiração (TTL):** Dependerá do contexto do servidor (padrão 12 horas: `12 * HOUR_IN_SECONDS`), mas, como a chave muta quando o post muda, a duração pode ser longa sem risco de estagnação.

## 3. Lógica de Interceptação (`SemanticEnforcer`)

A interceptação ocorrerá *antes* da execução pesada da manipulação DOM.
O hook `the_content` registrado em `SemanticEnforcer` deverá orquestrar um **Early Return**:

1. Identifica o Post atual (`$post = get_post()`).
2. Constrói a chave dinâmica de cache a partir do `post_modified`.
3. Checa o cache: `$cached_html = get_transient($cache_key)`.
4. **Early Return:** Se o transient existe, retorna `$cached_html` imediatamente (ignora o restante do processo).
5. **Processamento:** Se não existe (Cache Miss), instancia `DOMDocument`, faz todas as correções acessíveis.
6. **Salvamento:** Salva o output gerado via `set_transient($cache_key, $final_html)`.
7. Retorna o output final.

## 4. Estratégia de Invalidação (Purge)

Apesar da mutação natural da chave mitigar stale data, a base de dados de transients do WordPress inflaria indefinidamente com chaves abandonadas ao longo de sucessivas edições. Precisamos expurgar os transientes antigos.

* **Hooks Alvo:** `save_post` e `post_updated`
* **Mecanismo:** Assim que um post é salvo ou atualizado (excluindo revisões e auto-saves via `wp_is_post_revision()` e `wp_is_post_autosave()`), o sistema deverá engatilhar uma limpeza que, localizando chaves antigas com o formato `jinc_a11y_content_{post_id}_*`, remova o lixo obsoleto através da diretriz `delete_transient()`.

> [!WARNING]
> A purga do cache deve focar primariamente na exclusão agressiva ao salvar (via `delete_transient`), garantindo que apenas os dados novos existam e prevenindo inchaço do banco de dados (especialmente na tabela `wp_options`).

## 5. Casos de Teste Gherkin (PHPUnit)

Os testes deverão cobrir todos os fluxos com *mocking* correto das funções WP.

### Cenário 1: Geração de Cache na primeira visita (Cache Miss)

**Given** que o post com ID "10" não possui um transient válido ativo
**When** a função `the_content` é filtrada via `SemanticEnforcer`
**Then** o output deve ser re-processado via `DOMDocument`
**And** um transient contendo a chave vinculada ao post 10 deve ser criado com o HTML gerado.

### Cenário 2: Recuperação de Cache (Cache Hit)

**Given** que o post com ID "10" possui um transient perfeitamente válido
**When** a função `the_content` é filtrada via `SemanticEnforcer`
**Then** a função deve abortar precocemente a execução pesada (Early Return)
**And** retornar diretamente a string contida no transient
**And** as lógicas de DOM (como injeção de Headers e ARIA) não devem ser chamadas redundantemente.

### Cenário 3: Purga Completa ao salvar post (Invalidation)

**Given** que existem três (3) registros de cache antigos vinculados ao ID "10" na tabela de transientes
**When** o hook `save_post` é disparado para o ID "10" simulando uma edição
**Then** o `CacheManager` deve acionar a diretiva de exclusão
**And** todos os registros de cache do ID "10" devem ser purgados imediatamente garantindo um banco limpo.
