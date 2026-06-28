import os
import re
import sys
import yaml

# Constantes de Cores e Configurações (Mantidas do contexto original)
class Colors:
    HEADER, GREEN, RED, ENDC = '\033[95m', '\033[92m', '\033[91m', '\033[0m'

EXEMPT_FILES = ['readme.md', 'architecture.md', 'changelog.md']

# Funções de Regex proibidas para manipulação de HTML/DOM (ADR-001)
FORBIDDEN_REGEX_FUNCTIONS = [
    'preg_replace', 'preg_match', 'preg_match_all', 'preg_split',
    'preg_replace_callback', 'preg_replace_callback_array',
    'preg_grep', 'preg_last_error', 'preg_quote',
]

# Comandos de banco de dados proibidos (ADR-002: Lean Database)
FORBIDDEN_DB_COMMANDS = [
    'CREATE TABLE', 'dbDelta', 'ALTER TABLE', 'DROP TABLE',
]


def validate_php_sdd_rules(filepath: str) -> bool:
    """Validates a PHP file against the strict SDD rules:
    1. Must have declare(strict_types=1) (ADR-004)
    2. Must NOT use regex functions for DOM/HTML manipulation (ADR-001)
    3. Must NOT create custom database tables (ADR-002)
    """
    filename = os.path.basename(filepath).lower()
    violations = []

    try:
        with open(filepath, 'r', encoding='utf-8-sig') as f:
            content = f.read()

        # --- Rule 1: declare(strict_types=1) is mandatory ---
        if 'declare(strict_types=1)' not in content:
            violations.append('MISSING declare(strict_types=1) — ADR-004')

        # --- Rule 2: No regex functions for HTML/DOM parsing ---
        for func in FORBIDDEN_REGEX_FUNCTIONS:
            # Search for function calls (e.g. preg_replace(...) )
            # We use a simple text search to avoid false negatives.
            # The pattern looks for the function name followed by '(' 
            pattern = re.compile(r'\b' + re.escape(func) + r'\s*\(')
            matches = pattern.findall(content)
            if matches:
                violations.append(f'FORBIDDEN regex function "{func}" detected — ADR-001')

        # --- Rule 3: No custom DB table creation ---
        for cmd in FORBIDDEN_DB_COMMANDS:
            if cmd.lower() in content.lower():
                violations.append(f'FORBIDDEN DB command "{cmd}" detected — ADR-002 (Lean Database)')

        if violations:
            print(f"{Colors.RED}❌ Falha em {filename}:{Colors.ENDC}")
            for v in violations:
                print(f"   → {v}")
            return False
        else:
            print(f"{Colors.GREEN}✅ Validado (PHP SDD): {filename}{Colors.ENDC}")
            return True

    except Exception as e:
        print(f"{Colors.RED}❌ Erro crítico em {filename}: {str(e)}{Colors.ENDC}")
        return False


def validate_sdd_contract(filepath: str) -> bool:
    if not os.path.exists(filepath):
        print(f"{Colors.RED}❌ Arquivo não encontrado: {filepath}{Colors.ENDC}")
        return False

    filename = os.path.basename(filepath).lower()

    # Bypass para regras core e arquivos isentos
    if filename in EXEMPT_FILES or '/.agents/rules/' in filepath.replace('\\', '/').lower():
        print(f"{Colors.HEADER}⏭️ Isento: {filename}{Colors.ENDC}")
        return True

    # ── PHP-specific validation (bypass YAML, apply SDD rules) ──
    if filename.endswith('.php'):
        return validate_php_sdd_rules(filepath)

    # ── Documentation file validation (YAML frontmatter) ──
    try:
        with open(filepath, 'r', encoding='utf-8-sig') as f:
            content = f.read()

        # Extração Robusta: Captura o bloco YAML ignorando espaços iniciais
        yaml_match = re.search(r'^\s*---\s*\n(.*?)\n---\s*\n', content, re.S | re.M)
        
        meta = {}
        if yaml_match:
            try:
                meta = yaml.safe_load(yaml_match.group(1)) or {}
            except yaml.YAMLError:
                meta = {} # Fallback para busca literal se o YAML for inválido

        # Validação de chaves (Prioridade Semântica)
        has_name = isinstance(meta, dict) and 'name' in meta
        has_desc = isinstance(meta, dict) and 'description' in meta

        # Fallback de Compatibilidade (Busca literal nos primeiros 1000 chars)
        if not (has_name and has_desc):
            header_sample = content[:1000].lower()
            has_name = has_name or ('name:' in header_sample)
            has_desc = has_desc or ('description:' in header_sample)

        if has_name and has_desc:
            print(f"{Colors.GREEN}✅ Validado: {filename}{Colors.ENDC}")
            return True
        else:
            # Feedback detalhado para o log do GitHub Actions
            found_keys = list(meta.keys()) if isinstance(meta, dict) else "Formato Inválido"
            print(f"{Colors.RED}❌ Falha em {filename}: Chaves incompletas. Detectado: {found_keys}{Colors.ENDC}")
            return False

    except Exception as e:
        print(f"{Colors.RED}❌ Erro crítico em {filename}: {str(e)}{Colors.ENDC}")
        return False

if __name__ == "__main__":
    files = sys.argv[1:]
    if not files:
        sys.exit(0)
    success = all(validate_sdd_contract(f) for f in files)
    sys.exit(0 if success else 1)
