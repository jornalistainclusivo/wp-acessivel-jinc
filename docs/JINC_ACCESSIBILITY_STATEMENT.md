# Declaração de Acessibilidade e Padrões Arquiteturais JINC

**Versão:** 1.0.0 | **Data de Emissão:** Junho de 2026 | **Classificação:** Pública

---

## 1. O Manifesto JINC: Acessibilidade Inegociável

O ecossistema **WP Acessível JINC** não concebe a acessibilidade digital como um recurso opcional, um *plugin* cosmético ou uma camada de conformidade tardia. Acreditamos que a exclusão digital é uma falha de engenharia. Portanto, nossa arquitetura é projetada sob o princípio da **Acessibilidade *By Default*** e do **Design Universal**, garantindo que a integridade estrutural, a semântica da informação e as interfaces de usuário operem em total sinergia com tecnologias assistivas.

Nosso compromisso não é apenas mitigar barreiras, mas erradicá-las na raiz do processo de autoria e publicação, assegurando que o jornalismo e o conteúdo digital sejam universalmente consumíveis, independentemente das capacidades visuais, motoras ou cognitivas do usuário.

---

## 2. Conformidade Normativa e Padrões Globais

O padrão JINC foi rigorosamente modelado para atender e, em aspectos críticos, superar as diretrizes internacionais de acessibilidade web. Nossa infraestrutura está alinhada aos seguintes protocolos:

* **WCAG 2.2 (Web Content Accessibility Guidelines):** Nível de conformidade **AAA** (o padrão mais elevado) como meta de engenharia, com aderência estrita aos critérios de sucesso para contraste, redimensionamento de texto, navegação por teclado e semântica de mídia.
* **WAI-ARIA 1.2 (Web Accessibility Initiative - Accessible Rich Internet Applications):** Injeção dinâmica de atributos de estado e função (`aria-pressed`, `aria-label`, `aria-hidden`) geridos por gerenciamento de estado determinístico.
* **HTML5 Semantic Standards (W3C):** Validação estrita do Document Object Model (DOM) sem uso de *regex* falíveis, garantindo a árvore de acessibilidade perfeita.

---

## 3. A Arquitetura da Inclusão: Os Três Pilares JINC

O sistema atua de forma autônoma e inflexível sobre o código-fonte, regido por três módulos fundamentais de intervenção:

### Pilar I: Semantic Enforcer (Rigor Estrutural Automático)

A integridade da informação depende de uma estrutura previsível para leitores de tela. O motor JINC intercepta a renderização de conteúdo para aplicar correções cirúrgicas em tempo real:

* **Hierarquia H1-H6 Inquebrável:** Rebaixamento ou elevação automatizada de *headings* (cabeçalhos) aninhados incorretamente, garantindo uma árvore de navegação lógica.
* **Injeção de Landmarks:** Alocação automática de invólucros semânticos (ex: `<nav>`, `<article>`, `<main>`) em blocos de conteúdo órfãos, facilitando a navegação via rotores de leitores de tela.

### Pilar II: Media Gatekeeper (Tolerância Zero)

O Padrão JINC extirpa o conceito de "imagem sem descrição" do fluxo de publicação através de um bloqueio arquitetural:

* **REST API Blocking:** Rejeição absoluta (HTTP 403) na tentativa de publicação de mídias desprovidas de Texto Alternativo (`alt`).
* **Bypass Semântico Rigoroso:** Imagens estritamente decorativas exigem declaração explícita (uso da flag técnica `_jinc_decorative`), que o sistema converte em `alt=""` limpo, suprimindo o ruído para leitores de tela sem burlar o validador.

### Pilar III: Frontend UI Engine (Interação Adaptável)

Uma interface nativa injetada no topo da experiência visual do usuário, dotada de um Motor de Temas Híbrido, que garante:

* **Sobrescrita de Contraste Agressiva:** Um modo de Alto Contraste que, ao ser acionado, subverte o CSS original do tema, impondo matematicamente a legibilidade (índice WCAG superior a 7:1) em todos os nós interativos, incluindo validação rigorosa de *hover* e *focus*.
* **Bypass Blocks (Skip Links):** Link dinâmico "Ir para o conteúdo principal", visível ao foco do teclado, isolando o usuário de blocos de navegação repetitivos.
* **Touch Targets e Geometria:** Áreas de clique (botões e controles) projetadas com altura mínima de 44px e delineamento claro de estado ativo.

---

## 4. O Futuro Autônomo e Inteligência Artificial

A acessibilidade escalável exige assistência computacional avançada. O Padrão JINC está em processo de evolução contínua para integrar a arquitetura **DescreveAI**, um modelo *Agentic AI* desenhado para gerar descrições semânticas de contexto profundo e mitigar a carga cognitiva do autor, mantendo sempre o humano (jornalista/editor) no loop de validação final.

---

## 5. Engenharia Orientada a Contratos (SDD/TDD)

Declaramos que nenhuma linha de código relacionada a este padrão de acessibilidade entra em produção sem aprovação absoluta em nossa esteira de *Test-Driven Development* (TDD). A resiliência das nossas funções de acessibilidade é matematicamente provada por uma suíte de testes de mutação e regressão antes de qualquer implantação.

---

## 6. Auditoria e Feedback Contínuo

Embora nosso código busque a perfeição algorítmica, a acessibilidade é, em última análise, uma experiência humana. Encorajamos ativamente a comunidade de usuários com deficiência, auditores de acessibilidade e desenvolvedores a relatarem quaisquer fricções ou barreiras não antecipadas por nossa arquitetura.

* **Relatórios Técnicos e Pull Requests:** [Link para o repositório/GitHub]
* **Canal Direto de Acessibilidade:** [Email institucional, ex: acessibilidade@jinc.com.br]
* **Tempo Estimado de Resposta:** Todas as submissões categorizadas como "Barreira de Acesso Crítica" possuem SLA de análise em até 48 horas úteis.

> *"A tecnologia só atinge seu ápice quando não deixa ninguém para trás."*
