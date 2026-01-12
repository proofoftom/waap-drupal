# PLAN: Phase 1 — Foundation & Environment Setup

**Project:** Wallet as a Protocol Drupal Login Module
**Phase:** 1 — Foundation & Environment Setup
**Created:** 2025-01-12
**Status:** Ready to Execute

---

## Objective

Establish a working Drupal 10 development environment with proper tooling and scaffold the initial module structure. This phase creates the foundation for all subsequent development.

**Success Criteria:**
- DDEV environment running with Drupal 10 installed
- Module scaffold created with proper .info.yml and composer.json
- Quality tools (PHPUnit, PHPStan, PHPCS) configured and working
- Environment validated via working Drupal site and module enabled

---

## Context

### Project State
- **Mode:** yolo — Execute with minimal confirmation gates
- **Current Status:** Starting Phase 1
- **Completed Phases:** None

### Key Research Findings

From `01-RESEARCH.md`, the following approaches are validated:

1. **Drupal Installation:** Use `drupal/recommended-project` via Composer
2. **Local Environment:** DDEV with PHP 8.2, Drupal 10 project type
3. **Module Structure:** PSR-4 with standard info.yml, composer.json
4. **Quality Tools:** PHPUnit (via core-dev), PHPStan, PHPCS
5. **Reference Pattern:** safe_smart_accounts for NPM integration (Phase 4)

### Critical Decisions

- **Project Root:** Current directory `/Users/proofoftom/Code/os-decoupled/fresh2`
- **Docroot:** `web/` (following drupal/recommended-project convention)
- **Module Name:** `wallet_auth`
- **Module Location:** `web/modules/custom/wallet_auth/`
- **PHP Version:** 8.2 (Drupal 10 requirement: >=8.1)

### Known Constraints

- Avoid committing `vendor/`, `web/sites/default/files/`
- Use DDEV for all commands to match containerized environment
- Follow Drupal coding standards (PHPCS compliance required)
- Module must be Drupal.org contrib-ready (GPL-2.0-or-later)

---

## Tasks

### Task 1: Initialize DDEV Project

Create DDEV configuration for Drupal 10 development.

**Steps:**
1. Create `.ddev/config.yaml` with:
   - `project_type: drupal10`
   - `docroot: web`
   - `php_version: "8.2"`
2. Start DDEV: `ddev start`
3. Verify DDEV is running: `ddev describe`

**Verification:**
- `.ddev/config.yaml` exists with correct settings
- `ddev describe` shows project as healthy/running
- PHP version 8.2 is configured

---

### Task 2: Install Drupal 10 via Composer

Use DDEV to create a fresh Drupal 10 installation.

**Steps:**
1. Run: `ddev composer create-project "drupal/recommended-project:^10"`
2. Wait for Composer to complete (may take several minutes)
3. Verify directory structure: `web/core`, `web/modules`, etc.

**Verification:**
- `web/core` directory exists
- `composer.json` and `composer.lock` present
- `web/index.php` exists

---

### Task 3: Install Drush

Add Drush for Drupal CLI operations.

**Steps:**
1. Run: `ddev composer require drush/drush`
2. Verify Drush is available: `ddev drush status`

**Verification:**
- `drush/drush` in composer.json require section
- `ddev drush status` outputs Drupal status (may show errors pre-install)

---

### Task 4: Install Drupal Site

Run Drupal installation via Drush.

**Steps:**
1. Ensure `web/sites/default` is writable: `ddev exec chmod u+w web/sites/default`
2. Install site: `ddev drush site:install --account-name=admin --account-pass=admin -y`
3. Launch site to verify: `ddev launch`

**Verification:**
- Browser opens to Drupal site
- Can log in as admin/admin
- `web/sites/default/settings.php` exists
- `web/sites/default/files` directory exists

---

### Task 5: Install Quality Tooling Dependencies

Add PHPUnit, PHPStan, and PHPCS to project.

**Steps:**
1. Install core dev tools: `ddev composer require --dev drupal/core-dev`
2. Install PHPStan: `ddev composer require --dev phpstan/phpstan phpstan/extension-installer mglaman/phpstan-drupal phpstan/phpstan-deprecation-rules`
3. Install PHPCS: `ddev composer require --dev squizlabs/php_codesniffer drupal/coder`
4. Configure PHPCS installed paths: `ddev exec vendor/bin/phpcs --config-set installed_paths vendor/drupal/coder/coder_sniffer`

**Verification:**
- All packages in `composer.json` "require-dev" section
- `ddev exec vendor/bin/phpcs --version` works
- `ddev exec vendor/bin/phpstan --version` works

---

### Task 6: Create Quality Tool Configuration Files

Set up PHPStan and PHPCS configuration.

**Steps:**
1. Create `phpstan.neon` in project root with:
   - Level 1 (start conservative)
   - Paths: `web/modules/custom`
   - Drupal root: `web`
2. Create `phpcs.xml` in project root with:
   - Drupal and DrupalPractice rules
   - Target: `web/modules/custom`

**Verification:**
- `phpstan.neon` exists with proper configuration
- `phpcs.xml` exists with proper configuration
- `ddev exec vendor/bin/phpstan analyse` runs (may have no files yet)
- `ddev exec vendor/bin/phpcs` runs (may have no files yet)

---

### Task 7: Scaffold Module Directory Structure

Create the wallet_auth module skeleton.

**Steps:**
1. Create module directory: `web/modules/custom/wallet_auth/`
2. Create subdirectories:
   - `src/`
   - `config/install/`
   - `config/schema/`
   - `tests/src/Unit/`
   - `tests/src/Kernel/`
   - `tests/src/Functional/`
3. Create `wallet_auth.info.yml` with:
   - name: "Wallet Auth"
   - description: "Wallet-based authentication using Wallet as a Protocol"
   - type: module
   - core_version_requirement: ^10
   - package: Web3
4. Create empty `wallet_auth.module` file (for future hooks)
5. Create `wallet_auth.routing.yml` (empty for now)
6. Create `wallet_auth.permissions.yml` (empty for now)
7. Create `wallet_auth.services.yml` (empty for now)

**Verification:**
- All directories created
- `wallet_auth.info.yml` exists with valid YAML
- Module would be recognized by Drupal (if enabled)

---

### Task 8: Create Module composer.json

Add composer.json for the module (required for Drupal.org packaging).

**Steps:**
1. Create `web/modules/custom/wallet_auth/composer.json` with:
   - name: "drupal/wallet_auth"
   - type: "drupal-module"
   - license: "GPL-2.0-or-later"
   - require: php >=8.1, drupal/core ^10
   - autoload: PSR-4 mapping Drupal\wallet_auth\ → src/

**Verification:**
- `composer.json` exists in module root
- Valid JSON (use `ddev exec composer validate --no-check-all web/modules/custom/wallet_auth/composer.json`)
- PSR-4 autoload configured correctly

---

### Task 9: Regenerate Autoloader

Update Composer autoloader to recognize the new module namespace.

**Steps:**
1. Run: `ddev composer dump-autoload`
2. Verify autoload_classmap.php includes Drupal\wallet_auth namespace

**Verification:**
- Command completes without errors
- Namespace is registered (check vendor/composer/autoload_classmap.php for reference)

---

### Task 10: Enable Module in Drupal

Enable the wallet_auth module via Drush.

**Steps:**
1. Run: `ddev drush pm:enable wallet_auth -y`
2. Verify module is enabled: `ddev drush pm:list --type=module --status=enabled | grep wallet_auth`
3. Check admin interface: Navigate to /admin/modules (logged in as admin)

**Verification:**
- `ddev drush pm:list` shows wallet_auth as enabled
- Module appears in admin/modules page with "Enabled" status
- No errors in Drupal logs

---

### Task 11: Validate Environment

Run comprehensive validation of the development environment.

**Steps:**
1. Check DDEV status: `ddev describe` (should show healthy)
2. Check Drupal status: `ddev drush status`
3. Run PHPCS on module (even if empty): `ddev exec vendor/bin/phpcs web/modules/custom/wallet_auth/`
4. Run PHPStan on module: `ddev exec vendor/bin/phpstan analyse web/modules/custom/wallet_auth/`
5. Verify site is accessible: `ddev launch`
6. Log in as admin/admin and verify module is enabled

**Verification:**
- DDEV reports healthy
- Drupal status shows no errors
- PHPCS shows no errors (may have warnings, which is OK)
- PHPStan shows no errors
- Site loads in browser
- Module is enabled and visible

---

### Task 12: Create Documentation

Add basic documentation for the module.

**Steps:**
1. Create `web/modules/custom/wallet_auth/README.md` with:
   - Module description
   - Requirements (Drupal 10, PHP 8.1+)
   - Installation instructions
   - Roadmap link to this plan
2. Add inline comments to .info.yml explaining each field

**Verification:**
- README.md exists with content
- .info.yml has descriptive comments

---

## Execution Order

Tasks must be completed in sequential order due to dependencies:

```
1 → 2 → 3 → 4 → 5 → 6 → 7 → 8 → 9 → 10 → 11 → 12
```

**Checkpoint after Task 4:** Drupal site is installed and accessible
**Checkpoint after Task 6:** Quality tools are configured
**Checkpoint after Task 10:** Module is enabled in Drupal

---

## Success Criteria

Phase 1 is complete when ALL of the following are true:

1. **DDEV Environment:** Running and healthy (`ddev describe` shows OK)
2. **Drupal Installed:** Site accessible at https://wallet-auth-drupal.ddev.site (or similar)
3. **Admin Access:** Can log in as admin/admin
4. **Module Enabled:** wallet_auth appears in enabled modules list
5. **Module Structure:** All required files and directories exist
6. **Quality Tools:** PHPCS and PHPStan can run without errors
7. **PSR-4 Loading:** Namespace Drupal\wallet_auth is autoloadable
8. **Documentation:** README.md exists with basic info

---

## Output Artifacts

After completing this phase, the following will exist:

**Project Structure:**
```
fresh2/
├── .ddev/
│   └── config.yaml                    # DDEV configuration
├── web/
│   ├── core/                          # Drupal core
│   ├── modules/
│   │   └── custom/
│   │       └── wallet_auth/           # Our module
│   │           ├── wallet_auth.info.yml
│   │           ├── wallet_auth.module
│   │           ├── wallet_auth.routing.yml
│   │           ├── wallet_auth.services.yml
│   │           ├── wallet_auth.permissions.yml
│   │           ├── composer.json
│   │           ├── README.md
│   │           ├── src/
│   │           ├── config/
│   │           │   ├── install/
│   │           │   └── schema/
│   │           └── tests/
│   │               └── src/
│   │                   ├── Unit/
│   │                   ├── Kernel/
│   │                   └── Functional/
│   ├── sites/
│   │   └── default/
│   │       ├── settings.php
│   │       └── files/
│   └── index.php
├── composer.json                      # Project composer.json
├── composer.lock
├── phpstan.neon                       # PHPStan config
├── phpcs.xml                          # PHPCS config
└── vendor/
```

**Configuration Files:**
- `.ddev/config.yaml` - DDEV setup
- `phpstan.neon` - Static analysis config
- `phpcs.xml` - Code standards config
- `web/modules/custom/wallet_auth/composer.json` - Module packaging

**Documentation:**
- `web/modules/custom/wallet_auth/README.md` - Module documentation
- Comments in `.info.yml` explaining configuration

---

## Next Steps

After Phase 1 completion, proceed to **Phase 2: Wallet as a Protocol Integration Research** which will:

1. Study the Wallet as a Protocol specification
2. Analyze the safe_smart_accounts NPM integration pattern
3. Design the authentication flow
4. Prepare for backend implementation in Phase 3

---

## Notes

- **DDEV Commands:** Always use `ddev` prefix for Composer/Drush/PHP commands to run inside the container
- **Permissions:** If `composer` commands fail with permission errors, run `ddev exec chmod u+w web/sites/default`
- **Git Ignore:** Ensure `vendor/`, `web/sites/default/files/`, and `.ddev/.dbimage.yml` are gitignored (DDEV provides default .gitignore)
- **Mode:** Running in "yolo" mode — execute tasks without asking for confirmation at each step

---

*Last updated: 2025-01-12*
