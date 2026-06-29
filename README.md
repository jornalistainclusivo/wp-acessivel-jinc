# WP Acessível JINC ♿

![PHP Version](https://img.shields.io/badge/PHP-8.1%2B-blue.svg)
![WordPress](https://img.shields.io/badge/WordPress-6.0%2B-blue.svg)
![WCAG](https://img.shields.io/badge/WCAG-2.2_AAA-success.svg)
![Build Status](https://img.shields.io/badge/PHPUnit-100%25_Passing-brightgreen.svg)
![Architecture](https://img.shields.io/badge/Architecture-TDD_%7C_SDD-orange.svg)

**WP Acessível JINC** é um plugin *Enterprise-Grade* para WordPress, concebido para impor a acessibilidade web em nível arquitetural. Em vez de atuar como uma camada cosmética (overlay) pós-carregamento, o ecossistema JINC intercepta a renderização do DOM, bloqueia requisições inseguras via REST API e injeta interfaces adaptativas nativas, operando sob a premissa da **Acessibilidade Inegociável (Zero Tolerance)**.

Consulte o nosso [Manifesto e Declaração de Acessibilidade (JINC_ACCESSIBILITY_STATEMENT.md)](docs/JINC_ACCESSIBILITY_STATEMENT.md) para detalhes sobre as diretrizes WCAG 2.2 AAA e WAI-ARIA 1.2 aplicadas.

---

## 🏗️ Arquitetura e Módulos (Core Ecosystem)

O plugin é estruturado em módulos de domínio isolados (Domain-Driven Design), regidos por contratos rigorosos de interface.

### 1. Semantic Enforcer (Phase 1)

O motor de integridade estrutural. Ele atua no filtro `the_content`, processando o HTML via `DOMDocument` para correções cirúrgicas em tempo real (sem uso de *Regex*):

* **HeadingHierarchyFixer:** Rebaixamento ou elevação automatizada de cabeçalhos (`H1-H6`) aninhados incorretamente.
* **LandmarkInjector:** Alocação idempotente de marcos semânticos (`<main>`, `<article>`) em blocos de conteúdo.
* **Encoding Resiliente:** Supressão segura de erros `libxml` e preservação agressiva de `UTF-8`.

### 2. Media Gatekeeper (Phase 2)

O cão de guarda da biblioteca de mídia e do Gutenberg.

* **REST API Interceptor:** Intercepta `rest_pre_insert_attachment`. Se uma imagem for enviada sem `alt text`, a requisição é bloqueada (HTTP 403 / `WP_Error`). Não há opção de bypass nas configurações.
* **Semantic Bypass:** Imagens decorativas requerem o preenchimento explícito da string `"decorativo"` (case-insensitive). O backend limpa o `alt` (`alt=""`) e armazena a flag estrutural `_jinc_decorative`.

### 3. Frontend UI & Theming Engine (Phase 3)

Injeção nativa de interface adaptativa sem dependências de frameworks pesados (Zero jQuery).

* **BarInjector:** Injeta o `<nav>` de acessibilidade no topo da página usando `wp_body_open` (com fallback para `wp_footer`).
* **High Contrast Override:** CSS isolado com reset agressivo. Ao ser ativado, sobrepõe cores de qualquer tema com preto, branco e amarelo, garantindo conformidade matemática de contraste WCAG (inclusive em estados de `:hover` e `:focus`).
* **Theming Engine:** Painel construído com a *Settings API* do WordPress. Permite controle geométrico (border-radius), alinhamento (Flexbox), tipografia e paleta de cores (injetadas via Custom Properties/CSS Variables).
* **Dashicons Integration:** Utiliza a biblioteca nativa de ícones do wp-admin para manter o *payload* do frontend extremamente leve.

### 4. DescreveAI Integration (Phase 4 - Concluída)

A camada de *Agentic AI* que automatiza o preenchimento semântico de textos alternativos diretamente no Media Gatekeeper. Utiliza uma arquitetura de Quarentena e processamento assíncrono (AJAX) em background comunicando-se com a API Node.js do JINC para não bloquear a experiência do usuário durante o upload.

---

## 💻 Requisitos e Instalação

* **PHP:** 8.1 ou superior (Estritamente tipado com `declare(strict_types=1)`).
* **WordPress:** 6.0+ (Dependência de REST API nativa).
* **Banco de Dados (Lean DB):** O plugin opera em conformidade com ADR-002: Zero tabelas customizadas. Utiliza exclusivamente `wp_options`, `wp_postmeta` e *Transients*.

**Instalação para Desenvolvimento:**

```bash
# Clone o repositório
git clone [https://github.com/SeuUsuario/wp-acessivel-jinc.git](https://github.com/SeuUsuario/wp-acessivel-jinc.git)

# Acesse o diretório
cd wp-acessivel-jinc

# Instale as dependências de teste (PHPUnit)
composer install

# Crie um Symlink para o seu ambiente LocalWP/Docker
New-Item -ItemType SymbolicLink -Path "C:\Caminho\LocalWP\wp-content\plugins\wp-acessivel-jinc" -Target "C:\Caminho\Repositorio\wp-acessivel-jinc"

```

---

## 🧪 Workflow de Desenvolvimento (TDD & SDD)

Este projeto opera sob **Specification-Driven Development (SDD)**. Nenhuma funcionalidade é escrita sem uma `SPEC_*.md` aprovada previamente.

O desenvolvimento segue o ciclo **RED/GREEN (TDD)**:

1. Os testes são escritos isoladamente usando *mocks* de funções do WordPress (localizados em `tests/Fixtures/wp-stubs.php`). Não inicializamos o banco de dados do WP para testes unitários.
2. Execução da Suíte:

```bash
vendor/bin/phpunit --testdox

```

1. Execução da Auditoria SDD (Python):

```bash
python scripts_jinc/jinc_sdd_validator.py src/Modules/Caminho/Classe.php

```

---

*Desenvolvido com rigor arquitetural para o ecossistema JINC.*

```

---
