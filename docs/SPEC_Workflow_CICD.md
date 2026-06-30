# 🚢 SPEC: Workflow de CI/CD e Preparação para Produção

Esta especificação define o fluxo oficial de integração e entrega contínua (CI/CD) para o ecossistema JINC. A fonte da verdade para o pacote final de produção é o GitHub Actions.

## 1. Regras do Repositório (Higiene Zero-Trust)

O repositório do GitHub deve manter-se sempre "limpo", armazenando estritamente código-fonte.
- O diretório `/vendor` (e suas dependências) **nunca** deve ser incluído no controle de versão (`git rm -r --cached vendor/`).
- Regras de empacotamento estão definidas no `.gitattributes` na raiz do projeto (onde arquivos como `phpunit.xml`, testes e documentações internas recebem a flag `export-ignore`).

## 2. Ambiente de Desenvolvimento Local (LocalWP)

A equipe de engenharia JINC utiliza o LocalWP como ambiente de simulação primário.
Como o repositório não rastreia o `/vendor`, os desenvolvedores recém-integrados precisam seguir este fluxo para que o plugin funcione localmente:

1. Clone o repositório na máquina:
   `git clone https://github.com/SeuUsuario/wp-acessivel-jinc.git`
2. Instale as dependências com as ferramentas de desenvolvimento (PHPUnit, Mockery):
   `composer install`
3. Crie um *Symlink* conectando o repositório clonado à pasta de plugins do LocalWP. No PowerShell (Admin), execute:
   `New-Item -ItemType SymbolicLink -Path "C:\Caminho\LocalWP\wp-content\plugins\wp-acessivel-jinc" -Target "C:\Caminho\Repositorio\wp-acessivel-jinc"`

**ATENÇÃO:** Nunca commite a pasta vendor gerada localmente.

## 3. Empacotamento de Lançamento Local (Dry Run)

Para verificar o pacote antes do envio à produção, os desenvolvedores podem rodar scripts locais que geram o arquivo `.zip` respeitando as regras do `.gitattributes`.

*(Nota: O script `build_release.py` ou equivalente deve ser configurado separadamente para realizar testes de empacotamento locais.)*

## 4. Pipeline de Produção (GitHub Actions)

A infraestrutura de build final é completamente descarregada da máquina local para o GitHub Actions, assegurando padronização em servidores Unix isolados.

**Gatilho do Pipeline:**
O workflow `.github/workflows/release.yml` só é disparado quando uma *tag* de versão (`vX.X.X`) é efetuada no *push*.

**Etapas do Job:**
1. **Checkout:** Clona o código (`actions/checkout@v4`).
2. **Setup PHP:** Prepara a máquina virtual com PHP 8.1 (`shivammathur/setup-php@v2`).
3. **Dependências (Produção):** Roda `composer install --no-dev --optimize-autoloader`. A flag `--no-dev` garante que suítes de teste nunca contaminem o servidor de produção.
4. **Empacotamento (Zip):** Um `.zip` estrito é gerado via `git archive` (obedecendo `.gitattributes`) combinado com o `/vendor` recém-compilado.
5. **Criação da Release:** Publica automaticamente o release no GitHub com o `.zip` anexado como *Asset* final, utilizando a ação `softprops/action-gh-release@v1`.

### Fluxo Exato para Lançamento de Produção

1. Conclua e valide todas as mudanças na branch correspondente (conforme `checklist.py`).
2. Crie a tag anotada:
   `git tag -a v1.0.0 -m "feat: release de producao inicial"`
3. Faça o push da tag para engatilhar a Action:
   `git push origin v1.0.0`
