# CLAUDE.md

## Project Overview

This is a Drupal 10.6+ project that provides wallet-based authentication using cryptographic signatures (EIP-191 personal_sign). The project enables users to authenticate with Ethereum wallets (MetaMask, WalletConnect, etc.) instead of traditional username/password credentials.

**Core Technology:**
- Drupal 10.6+ (PHP 8.2)
- Ethereum wallet authentication (EIP-191)
- External Auth module integration
- Vite-based frontend build system

## Repository Structure

This project uses a **two-repository architecture**:

1. **Main Project Repository** (`waap-test`): Contains the Drupal installation and project configuration
2. **wallet_auth Module Repository** (`drupal_wallet_auth`): The custom module has its own Git repository at `web/modules/custom/wallet_auth/`

When working on the wallet_auth module, note that it is a Git submodule/separate repository.

## Directory Structure

```
/Users/proofoftom/Code/os-decoupled/fresh2/
├── composer.json                    # Root Composer config (Drupal 10.6)
├── vendor/                          # PHP dependencies (at project root)
├── phpcs.xml                        # PHPCS configuration (Drupal standards)
├── phpstan.neon                     # PHPStan configuration (level 6)
├── web/                             # Drupal web root
│   ├── core/                        # Drupal core
│   ├── modules/
│   │   ├── contrib/                 # Contributed modules
│   │   └── custom/
│   │       └── wallet_auth/         # Wallet Auth module (separate Git repo)
│   │           ├── src/             # PHP source (Services, Controllers, Forms, etc.)
│   │           ├── tests/           # PHPUnit tests (Kernel, Functional)
│   │           ├── js/              # Frontend JavaScript (Vite build)
│   │           ├── css/             # Stylesheets
│   │           ├── templates/       # Twig templates
│   │           ├── config/          # Module configuration schema
│   │           ├── phpcs.xml        # Module-specific PHPCS config
│   │           ├── phpstan.neon     # Module-specific PHPStan config
│   │           ├── package.json     # NPM dependencies (ethers.js, vite)
│   │           └── vite.config.*.js # Vite build configs (ui, connector)
│   ├── themes/                      # Drupal themes
│   └── phpunit.xml                  # PHPUnit configuration for custom modules
```

## wallet_auth Module

The wallet_auth module is a full-stack Drupal module with:

- **Backend (PHP)**: Services for signature verification and user management
- **Frontend (JavaScript)**: Wallet connection UI built with ethers.js and Vite
- **Database**: Custom table for wallet-to-user mapping
- **REST API**: Authentication endpoint at `/wallet-auth/authenticate`

**Key Features:**
- EIP-191 signature verification using `kornrunner/keccak` and `simplito/elliptic-php`
- Automatic user creation on first wallet authentication
- Nonce-based replay attack prevention
- Block plugin for wallet login UI
- Configuration form for blockchain network selection

## Development Commands

### Code Quality

All commands should be run from the **project root** (`/Users/proofoftom/Code/os-decoupled/fresh2/`):

```bash
# PHPCS - Check coding standards (Drupal)
vendor/bin/phpcs

# PHPCS - Auto-fix issues
vendor/bin/phpcbf

# PHPStan - Static analysis (level 6)
vendor/bin/phpstan analyze
```

### Testing

Tests must be run from the **web/** directory due to split vendor structure:

```bash
# Change to web directory
cd web/

# Run all wallet_auth tests
../vendor/bin/phpunit -c phpunit.xml modules/custom/wallet_auth/tests/

# Run specific test suite
../vendor/bin/phpunit -c phpunit.xml modules/custom/wallet_auth/tests/Kernel/
../vendor/bin/phpunit -c phpunit.xml modules/custom/wallet_auth/tests/Functional/

# Run specific test file
../vendor/bin/phpunit -c phpunit.xml modules/custom/wallet_auth/tests/Kernel/WalletVerificationTest.php
```

**Test Coverage:**
- 64 tests, 339 assertions
- 82% code coverage for critical services
- Kernel tests: Signature verification, nonce management, user creation
- Functional tests: REST API, block rendering, settings form

### Frontend Build (wallet_auth module)

The wallet_auth module uses Vite for frontend builds:

```bash
# Change to module directory
cd web/modules/custom/wallet_auth/

# Install dependencies
npm install

# Build for production
npm run build

# Development build with watch
npm run dev
```

**Build artifacts:**
- `js/dist/wallet-auth-ui.js` - Main UI bundle
- `js/dist/wallet-auth-connector.js` - Wallet connector library

### Drupal Commands

```bash
# Enable module
drush pm:enable wallet_auth -y

# Clear caches
drush cache:rebuild

# Check module status
drush pm:status

# View logs
drush watchdog:show
```

## Coding Standards

### PHP (Drupal Standards)

**PHPCS Configuration:**
- Ruleset: `Drupal` and `DrupalPractice`
- Excluded rules: Documentation comments (DocComment, FunctionComment, InlineComment)
- Parallel processing: 8 files simultaneously
- Target: `web/modules/custom/`

**PHPStan Configuration:**
- Level: 6 (strict analysis)
- Target: `web/modules/custom/`
- Ignored: `Unsafe usage of new static()` (Drupal pattern)

### Key Conventions

1. **Namespace:** `Drupal\wallet_auth\[Subdirectory]`
2. **Services:** Injected via dependency injection, defined in `wallet_auth.services.yml`
3. **Database:** Use Drupal's Query API (prepared statements)
4. **Security:** All inputs validated, outputs escaped via Twig
5. **Configuration:** Use Configuration API, schema defined in `config/schema/`
6. **Testing:** Follow Drupal test patterns (Kernel, Functional)

### Important Notes

- **Never commit `node_modules/`** - Already gitignored in wallet_auth module
- **Respect two-repo structure** - Module has separate Git history
- **Use absolute paths for tests** - Due to vendor split structure
- **Run PHPCS/PHPStan from project root** - Configurations are at project level
- **Run PHPUnit from web/ directory** - Bootstrap requires web root context

## Configuration Files

- **Root phpcs.xml**: Scans all custom modules, parallel processing enabled
- **Root phpstan.neon**: Level 6 analysis for custom modules
- **web/phpunit.xml**: Test suites for Unit, Kernel, Functional, FunctionalJavascript
- **wallet_auth/phpcs.xml**: Module-specific overrides (if different from root)
- **wallet_auth/phpstan.neon**: Module-specific PHPStan settings

## Common Tasks

### Adding a new service
1. Define in `wallet_auth.services.yml`
2. Create class in `src/[Subdirectory]/`
3. Add interface if appropriate
4. Write Kernel tests

### Adding a new route
1. Define in `wallet_auth.routing.yml`
2. Create controller in `src/Controller/`
3. Add access callback/permission
4. Write Functional tests

### Modifying frontend
1. Edit source in `js/src/`
2. Run `npm run build` in module directory
3. Clear Drupal caches
4. Test in browser

### Running quality checks before commit
```bash
# From project root
vendor/bin/phpcs
vendor/bin/phpstan analyze

# From web/ directory
cd web
../vendor/bin/phpunit -c phpunit.xml modules/custom/wallet_auth/tests/
```

## Dependencies

**PHP Packages (Composer):**
- `drupal/core-recommended: ^10.6`
- `drupal/externalauth: ^2.0`
- `kornrunner/keccak: ^1.1` - Keccak-256 hashing
- `simplito/elliptic-php: ^1.0` - Elliptic curve crypto (secp256k1)
- `drush/drush: ^13.6`

**JavaScript Packages (NPM - wallet_auth module):**
- `ethers: ^6.x` - Ethereum library
- `vite: ^5.x` - Build tool

**Dev Dependencies:**
- `drupal/core-dev: ^10.6` - Includes PHPUnit, Behat, etc.
- PHPStan and PHPCS installed via core-dev

## Project Context

This project is part of a "Wallet as a Protocol" integration effort, enabling Drupal sites to use Web3 wallet authentication. The wallet_auth module provides production-ready authentication with:

- Support for Ethereum mainnet and testnets (Sepolia, Polygon, BSC, Arbitrum, Optimism)
- Cryptographically secure nonce generation and verification
- EIP-191 compliant signature verification
- Automatic user provisioning
- Comprehensive test coverage

The module is designed to be contributed to Drupal.org as a standalone package.
