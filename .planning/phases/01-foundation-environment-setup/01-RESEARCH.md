# Research: Phase 1 — Foundation & Environment Setup

**Project:** Wallet as a Protocol Drupal Login Module
**Phase:** 1 — Foundation & Environment Setup
**Research Date:** 2025-01-12

---

## Executive Summary

Drupal 10 in 2025 has a well-established ecosystem for local development using Composer + DDEV. The standard approach uses `drupal/recommended-project` as the starting point, with quality tooling (PHPUnit, PHPStan, PHPCS) integrated via `drupal/core-dev`. Module structure follows PSR-4 conventions with YAML-based configuration.

**Key Finding:** The minimal foundation approach aligns well with modern Drupal practices — you can start with a working DDEV environment and add quality tooling progressively.

---

## 1. Project Scaffolding

### Standard Approach: `drupal/recommended-project`

Drupal core provides an official Composer template that creates a "relocated document root" structure:

```bash
composer create-project drupal/recommended-project my-project
cd my-project
```

**Directory Structure:**
```
my-project/
├── composer.json       # Project dependencies
├── composer.lock
├── vendor/             # PHP dependencies (outside webroot)
├── web/                # Document root (webserver points here)
│   ├── core/           # Drupal core
│   ├── modules/
│   ├── themes/
│   ├── sites/
│   └── index.php
└── drush/              # If using drush/drush (optional)
```

**Why this structure?**
- **Security:** `vendor/` directory is outside the webroot, preventing direct access
- **Best practice:** Recommended by Drupal core team since 8.8.0
- **Scaffolding:** Uses `drupal/core-composer-scaffold` plugin for managing files like `index.php`, `robots.txt`, `.htaccess`

**Alternative:** `drupal/legacy-project` — only use if you have specific reasons not to use the recommended layout (note: being removed in Drupal 12).

### Composer Dependencies for Development

To enable testing and quality tooling:

```bash
# PHPUnit and core testing dependencies
composer require --dev drupal/core-dev

# Drush (Drupal CLI) - optional but highly recommended
composer require drush/drush
```

---

## 2. DDEV Configuration

### Basic DDEV Setup for Drupal 10

DDEV provides the fastest path to a working local environment:

```bash
# From project root
ddev config --project-type=drupal10 --docroot=web
ddev start
```

**For a new Drupal project within DDEV:**

```bash
mkdir my-drupal-site && cd my-drupal-site
ddev config --project-type=drupal10 --docroot=web
ddev start
ddev composer create-project "drupal/recommended-project:^10"
ddev composer require drush/drush
ddev drush site:install --account-name=admin --account-pass=admin -y
ddev launch
```

### DDEV + Drupal Integration

DDEV automatically:
- Creates a `settings.ddev.php` file with database connection settings
- Manages database credentials (db/db/db)
- Handles PHP version configuration
- Provides mailhog for email testing
- Sets xdebug for debugging

**Key DDEV Files:**
```
.ddev/
├── config.yaml          # Main DDEV configuration
├── docker-compose.yaml  # Docker services
└── .ddev-docker-compose-full.yaml
```

---

## 3. Quality Tooling Integration

### PHPUnit (Testing)

PHPUnit is the standard for Drupal 8+ testing. Installed via `drupal/core-dev`:

```bash
composer require --dev drupal/core-dev
```

**Test Types:**
- **Unit tests** (`UnitTestCase`) — Fast, isolated, no Drupal bootstrap
- **Kernel tests** (`KernelTestBase`) — Bootstrap Drupal, no browser
- **Functional tests** (`BrowserTestBase`) — Full Drupal with browser
- **JavaScript tests** (`WebDriverTestBase`) — JavaScript-enabled browser tests

**Running Tests:**
```bash
# Run all tests
./vendor/bin/phpunit

# Run specific test suite
./vendor/bin/phpunit --testsuite unit

# Run specific test file
./vendor/bin/phpunit modules/custom/wallet_auth/tests/src/Unit/WalletTest.php
```

### PHPStan (Static Analysis)

PHPStan provides static analysis for Drupal code. Setup requires Drupal-specific extensions:

```bash
composer require --dev phpstan/phpstan \
    phpstan/extension-installer \
    mglaman/phpstan-drupal \
    phpstan/phpstan-deprecation-rules
```

**Configuration (`phpstan.neon`):**
```neon
parameters:
    level: 1
    paths:
        - web/modules/custom
    # Drupal-specific configuration
    drupal:
        drupalRoot: web
    excludes_analyse:
        - */tests/PHPUnit/*
```

**Running PHPStan:**
```bash
vendor/bin/phpstan analyse
```

### PHPCS (Code Sniffer)

PHPCS enforces Drupal coding standards:

```bash
# Install PHPCS and Drupal Coder standards
composer require --dev squizlabs/php_codesniffer drupal/coder

# Set installed paths for PHPCS
vendor/bin/phpcs --config-set installed_paths vendor/drupal/coder/coder_sniffer
```

**Configuration (`phpcs.xml`):**
```xml
<?xml version="1.0"?>
<ruleset name="Drupal Project">
    <description>Drupal coding standards</description>
    <rule ref="Drupal">
        <exclude name="Drupal.Commenting.DocComment.MissingShort"/>
    </rule>
    <rule ref="DrupalPractice"/>
    <file>web/modules/custom</file>
    <exclude-pattern>*/tests/*</exclude-pattern>
</ruleset>
```

**Running PHPCS:**
```bash
vendor/bin/phpcs
vendor/bin/phpcbf  # Auto-fix issues
```

### Running Quality Tools in DDEV

Since DDEV runs commands in the container, use `ddev` prefix:

```bash
ddev exec vendor/bin/phpstan analyse
ddev exec vendor/bin/phpcs
ddev exec ./vendor/bin/phpunit
```

---

## 4. Drupal 10 Module Structure

### Minimal Module Requirements

The absolute minimum for a Drupal module to be recognized:

```
modules/
└── custom/
    └── wallet_auth/
        └── wallet_auth.info.yml    # Required - module metadata
```

**`wallet_auth.info.yml`:**
```yaml
name: Wallet Auth
description: 'Wallet-based authentication using Wallet as a Protocol'
type: module
core_version_requirement: ^10
package: Web3
```

### Standard Module Structure

A well-organized Drupal 10 module:

```
wallet_auth/
├── wallet_auth.info.yml          # Module metadata (REQUIRED)
├── wallet_auth.module            # Hook implementations (optional)
├── wallet_auth.routing.yml       # Route definitions
├── wallet_auth.services.yml      # Service definitions
├── wallet_auth.permissions.yml   # Permission definitions
├── wallet_auth.libraries.yml     # JS/CSS library definitions
├── composer.json                 # Composer package definition
├── README.md                     # Documentation
├── config/
│   ├── install/                  # Default configuration
│   │   └── wallet_auth.settings.yml
│   └── schema/                   # Configuration schema
│       └── wallet_auth.schema.yml
├── src/
│   ├── Controller/               # Route controllers
│   ├── Form/                     # Form classes
│   ├── Service/                  # Business logic services
│   ├── Plugin/                   # Plugin implementations
│   └── Entity/                   # Custom entities (if needed)
├── tests/                        # PHPUnit tests
│   └── src/
│       ├── Unit/
│       ├── Kernel/
│       └── Functional/
├── js/                           # JavaScript files
├── css/                          # Stylesheet files
└── libraries/                    # External libraries
```

### composer.json for Contrib Modules

Every Drupal contrib module should have a `composer.json`:

```json
{
  "name": "drupal/wallet_auth",
  "description": "Wallet-based authentication using Wallet as a Protocol",
  "type": "drupal-module",
  "license": "GPL-2.0-or-later",
  "require": {
    "php": ">=8.1",
    "drupal/core": "^10"
  },
  "autoload": {
    "psr-4": {
      "Drupal\\wallet_auth\\": "src/"
    }
  },
  "extra": {
    "drupal": {
      "version": "1.0.0"
    }
  }
}
```

**Key Points:**
- `type: "drupal-module"` — Required for Drupal.org packaging
- `autoload.psr-4` — Maps namespace to src/ directory
- `license: "GPL-2.0-or-later"` — Required for Drupal.org

---

## 5. NPM Package Integration Pattern (from safe_smart_accounts)

The **safe_smart_accounts** module demonstrates the canonical pattern for integrating NPM packages with Drupal modules:

### Pattern Overview

1. **package.json** defines NPM dependencies and build process
2. **postinstall script** automatically bundles SDKs for browser use
3. **esbuild** creates an IIFE bundle with Node.js polyfills
4. **libraries/** directory stores the bundled output
5. **.libraries.yml** file registers the library for Drupal

### package.json Structure

```json
{
  "name": "wallet_auth",
  "version": "1.0.0",
  "private": true,
  "description": "Wallet Auth Drupal module - WaaP SDK dependencies",
  "scripts": {
    "postinstall": "node scripts/sync-libraries.js"
  },
  "dependencies": {
    "@wallet-as-a-protocol/sdk": "^1.0.0"
  },
  "devDependencies": {
    "esbuild": "^0.24.0",
    "esbuild-plugin-polyfill-node": "^0.3.0"
  },
  "engines": {
    "node": ">=18.0.0"
  }
}
```

### Sync Script (scripts/sync-libraries.js)

```javascript
const esbuild = require('esbuild');
const { polyfillNode } = require('esbuild-plugin-polyfill-node');
const fs = require('fs');
const path = require('path');

const MODULE_ROOT = path.resolve(__dirname, '..');
const LIBRARIES_DIR = path.join(MODULE_ROOT, 'libraries', 'waap-sdk');

async function main() {
  // Create entry point
  const entryContent = `
import WaaP from '@wallet-as-a-protocol/sdk';
window.WaaP = WaaP;
export { WaaP };
  `;

  // Bundle using esbuild
  await esbuild.build({
    entryPoints: ['./entry.js'],
    bundle: true,
    format: 'iife',
    platform: 'browser',
    outfile: path.join(LIBRARIES_DIR, 'waap.bundle.js'),
    minify: true,
    sourcemap: true,
    plugins: [
      polyfillNode({
        polyfills: {
          buffer: true,
          crypto: true,
          stream: true,
          util: true,
          events: true,
          process: true,
        },
      }),
    ],
  });
}

main();
```

### Registering in Drupal (.libraries.yml)

```yaml
wallet_auth_sdk:
  version: 1.0.0
  js:
    libraries/waap-sdk/waap.bundle.js: { minified: true }
  dependencies:
    - core/drupal
    - core/once
```

### Why This Pattern?

- **Automatic:** Runs on `npm install` — no manual build step
- **Browser-compatible:** Polyfills Node.js built-ins for browser use
- **Drupal-native:** Bundled output can be loaded via Drupal's asset system
- **Version-locked:** SDK version locked in package.json and committed
- **Sourcemaps:** Enables debugging in browser dev tools

---

## 6. Common Pitfalls to Avoid

### 1. Wrong Project Type
- **Issue:** Using `drupal/legacy-project` without justification
- **Fix:** Always use `drupal/recommended-project` unless you have specific constraints

### 2. Missing core_version_requirement
- **Issue:** Module not recognized in Drupal 10
- **Fix:** Always include `core_version_requirement: ^10` in .info.yml

### 3. Permissions Issues
- **Issue:** Composer fails with `Could not delete web/sites/default/...`
- **Fix:** `chmod u+w web/sites/default` before composer commands

### 4. PSR-4 Autoloading Not Regenerated
- **Issue:** New classes not found, requires cache clear
- **Fix:** Run `composer dump-autoload` after adding new classes

### 5. DDEV PHP Version Mismatch
- **Issue:** Drupal 10 requires PHP 8.1+, DDEV defaults to older version
- **Fix:** Set `php_version: "8.2"` in `.ddev/config.yaml`

### 6. Quality Tools Not Running in DDEV
- **Issue:** Tools work locally but fail in CI or vice versa
- **Fix:** Always use `ddev exec vendor/bin/phpcs` to match container environment

### 7. Forgetting to Add Drupal Repository
- **Issue:** Composer can't find drupal modules
- **Fix:** Add to project's composer.json (not module's):
```json
"repositories": [
  {
    "type": "composer",
    "url": "https://packages.drupal.org/8"
  }
]
```

### 8. Hardcoding Database Credentials
- **Issue:** Settings files committed with hardcoded credentials
- **Fix:** Use DDEV's `settings.ddev.php` which is git-ignored

---

## 7. Reference Projects

### safe_smart_accounts
- **URL:** https://git.drupalcode.org/project/safe_smart_accounts
- **Why relevant:** Demonstrates NPM package integration for Web3 Drupal modules
- **Key patterns:**
  - esbuild-based bundling with Node.js polyfills
  - postinstall script for automatic SDK bundling
  - PSR-4 structure with Entity/Form/Controller organization
  - Proper .info.yml and composer.json setup

### Well-Maintained Contrib Modules (for reference)
- **Rules for selection:** Active maintenance, security coverage, Drupal 10 compatible
- **Resources:**
  - [What I Look For in Drupal Contrib Modules](https://hussainweb.me/what-i-look-for-in-drupal-contrib-modules/)
  - [Drupal Best Practices GitHub](https://github.com/theodorosploumis/drupal-best-practices)

---

## 8. What NOT to Hand-Roll

### Use Existing Solutions For:

1. **Drupal Core Scaffolding** — Use `drupal/core-composer-scaffold`, don't manually manage files
2. **DDEV Database Setup** — DDEV handles this automatically
3. **PSR-4 Autoloading** — Composer's autoload generator handles this
4. **Quality Tooling Configuration** — Use `mglaman/phpstan-drupal`, not custom rulesets
5. **JavaScript Bundling** — Use esbuild or similar, don't write custom bundlers

### Build Custom Solutions Only For:

- Business logic specific to wallet authentication
- WaaP protocol integration (new SDK)
- Custom entities for wallet/user mapping
- Authentication provider plugins

---

## 9. Recommended Starter Commands

```bash
# 1. Create project with DDEV
mkdir wallet-auth-drupal && cd wallet-auth-drupal
ddev config --project-type=drupal10 --docroot=web
ddev start

# 2. Install Drupal core via Composer
ddev composer create-project "drupal/recommended-project:^10"

# 3. Install Drush
ddev composer require drush/drush

# 4. Install Drupal
ddev drush site:install --account-name=admin --account-pass=admin -y

# 5. Add quality tooling
ddev composer require --dev drupal/core-dev
ddev composer require --dev phpstan/phpstan phpstan/extension-installer mglaman/phpstan-drupal
ddev composer require --dev squizlabs/php_codesniffer drupal/coder

# 6. Create module scaffold
mkdir -p web/modules/custom/wallet_auth/{src,config/install,tests}

# 7. Launch site
ddev launch
```

---

## 10. Sources

### Drupal Official Documentation
- [Starting a Site Using Drupal Composer Project Templates](https://www.drupal.org/docs/develop/using-composer/starting-a-site-using-drupal-composer-project-templates)
- [Creating Modules](https://www.drupal.org/docs/develop/creating-modules)
- [Let Drupal know about your module with an .info.yml file](https://www.drupal.org/docs/develop/creating-modules/let-drupal-know-about-your-module-with-an-infoyml-file)
- [PHPUnit in Drupal](https://www.drupal.org/docs/develop/automated-testing/phpunit-in-drupal)
- [Managing dependencies for a contributed project](https://www.drupal.org/docs/develop/using-composer/managing-dependencies-for-a-contributed-project)
- [Add a composer.json file](https://www.drupal.org/docs/develop/using-composer/add-a-composerjson-file)

### DDEV Documentation
- [Install Drupal Locally with DDEV](https://drupalize.me/tutorial/install-drupal-locally-ddev)
- [CMS Quickstarts - DDEV Docs](https://ddev.readthedocs.io/en/stable/users/quickstart)
- [Managing CMS Settings - DDEV Docs](https://ddev.readthedocs.io/en/stable/users/usage/cms-settings)

### Quality Tooling
- [Configure PHPCS and PHPStan in DDEV for Drupal](https://eduardotelaya.com/blog/technology/2025-07-21-configure-phpcs-and-phpstan-in-ddev-for-drupal/)
- [Getting started with PHPStan for Drupal](https://www.drupal.org/docs/develop/development-tools/phpstan/getting-started)
- [mglaman/phpstan-drupal on GitHub](https://github.com/mglaman/phpstan-drupal)
- [Writing better Drupal code with static analysis using PHPStan](https://mglaman.dev/blog/writing-better-drupal-code-static-analysis-using-phpstan)

### Reference Projects
- [safe_smart_accounts on GitLab](https://git.drupalcode.org/project/safe_smart_accounts)

### Best Practices
- [What I Look For in Drupal Contrib Modules](https://hussainweb.me/what-i-look-for-in-drupal-contrib-modules/)
- [Drupal Best Practices GitHub](https://github.com/theodorosploumis/drupal-best-practices)
- [Drupal 8 Development Best Practices](https://www.gregboggs.com/drupal-development-best-practices/)

---

## Summary

Drupal 10 in 2025 has a mature, well-documented ecosystem for local development. The combination of:

1. **Composer** (`drupal/recommended-project`)
2. **DDEV** (local environment)
3. **PSR-4 structure** (module organization)
4. **Quality tooling** (PHPUnit, PHPStan, PHPCS)
5. **NPM build pattern** (esbuild + postinstall)

...provides a solid foundation for building the Wallet as a Protocol authentication module.

The minimal foundation approach you described is fully supported by these tools — you can validate the environment works immediately, then add features iteratively.

---

*Next Step:* Use this research to create a detailed implementation plan (`PLAN.md`) for Phase 1.
