# Drupal + Wallet as a Protocol (WaaP) Starter Kit

A Drupal 10.6+ starter kit with [Wallet as a Protocol (WaaP)](https://docs.wallet.human.tech/) authentication pre-configured. Enable users to sign in with social logins or crypto wallets - no passwords required.

## What is WaaP?

[Wallet as a Protocol (WaaP)](https://docs.wallet.human.tech/) enables **true self-custody without seed phrases**. Unlike Wallet-as-a-Service where wallets are rented, WaaP gives users:

- **Universal access** - No browser extension required
- **Social login** - Email, phone, Google, Twitter, Discord
- **True ownership** - Wallets are free and portable across apps
- **No seed phrases** - Trustless recovery via human attributes

## Features

- Wallet-based authentication (no passwords)
- Automatic user creation on first authentication
- Support for Ethereum, Polygon, Arbitrum, Optimism, BSC, Sepolia
- Multiple wallets per user
- Pre-configured `wallet_auth` module
- Development tools ready (PHPCS, PHPStan, PHPUnit)

## Requirements

- Docker Desktop (for DDEV) or PHP 8.2+
- Composer
- Node.js 18+ (for frontend builds)
- Drush (included with DDEV)

## Quick Start with DDEV

### 1. Clone and Start

```bash
# Clone the repository
git clone <your-repo-url>
cd <project-directory>

# Start DDEV
ddev start
```

### 2. Install Drupal

```bash
# Install Drupal via DDEV
ddev composer install
ddev drush site:install --account-name=admin --account-pass=admin

# Or interactively
ddev drush site:install
```

### 3. Enable Wallet Auth Module

```bash
# Enable the wallet_auth module
ddev drush pm:enable wallet_auth -y

# Clear caches
ddev drush cache:rebuild
```

### 4. Configure Wallet Auth

Visit `https://<project>.ddev.site/admin/config/people/wallet-auth` and configure:

- **Blockchain network** - Select Ethereum Mainnet or testnet (Sepolia)
- **Sign-in button text** - Default: "Sign In"
- **Display mode** - Link (matches nav) or Button (theme styling)
- **Authentication methods** - Enable email, phone, social options
- **Allowed social providers** - Google, Twitter, Discord

### 5. Test Authentication

1. Visit your site in an incognito window
2. Click "Sign In" in the User Account Menu
3. Choose an authentication method
4. Complete authentication and signature request
5. You're logged in!

## Manual Setup (Without DDEV)

### Prerequisites

```bash
# Ensure PHP 8.2+ is installed
php -v

# Install Composer dependencies
composer install

# Install Drupal core
./vendor/bin/drush site:install
```

### Enable Wallet Auth

```bash
# Enable the module
./vendor/bin/drush pm:enable wallet_auth -y

# Clear caches
./vendor/bin/drush cache:rebuild
```

## Project Structure

```
├── composer.json              # Project dependencies
├── phpcs.xml                  # Code quality configuration
├── phpstan.neon               # Static analysis configuration
├── web/                       # Drupal web root
│   ├── core/                  # Drupal core
│   ├── modules/
│   │   ├── contrib/           # Contributed modules
│   │   └── custom/
│   │       └── wallet_auth/   # WaaP authentication module
│   ├── sites/                 # Site-specific files
│   └── phpunit.xml            # Test configuration
└── vendor/                    # PHP dependencies
```

## Development

### Code Quality

```bash
# Run PHP CodeSniffer (Drupal standards)
ddev exec vendor/bin/phpcs

# Auto-fix PHPCS issues
ddev exec vendor/bin/phpcbf

# Run PHPStan (static analysis)
ddev exec vendor/bin/phpstan analyze
```

### Testing

```bash
# Run all wallet_auth tests
ddev exec ../vendor/bin/phpunit -c web/phpunit.xml web/modules/custom/wallet_auth/tests/

# Run Kernel tests only
ddev exec ../vendor/bin/phpunit -c web/phpunit.xml web/modules/custom/wallet_auth/tests/src/Kernel/

# Run Functional tests only
ddev exec ../vendor/bin/phpunit -c web/phpunit.xml web/modules/custom/wallet_auth/tests/src/Functional/
```

### Frontend Build

The `wallet_auth` module uses Vite for frontend builds:

```bash
# Navigate to the module
cd web/modules/custom/wallet_auth/

# Install dependencies
npm install

# Build for production
npm run build

# Development build with watch mode
npm run dev
```

### Drush Commands

```bash
# Clear caches
ddev drush cache:rebuild

# Check module status
ddev drush pm:status

# View logs
ddev drush watchdog:show

# Export configuration
ddev drush config:export

# Import configuration
ddev drush config:import
```

## Managing Wallet Addresses

After users authenticate, manage their wallet addresses at:

**People → Wallets** (`/admin/people/wallets`)

From this page you can:
- View all wallet addresses linked to user accounts
- Edit wallet address ownership
- Reassign orphaned wallets to different users
- Enable/disable wallet addresses

## Security

This implementation follows industry best practices:

- EIP-191/EIP-4361 compliant signature verification
- Cryptographically secure single-use nonces
- Nonces expire after 5 minutes (configurable)
- No private keys stored - only wallet addresses
- SQL injection prevention via parameterized queries
- XSS prevention via output escaping
- CSRF protection via signature verification
- Replay attack prevention via nonce expiration

## Links

- [WaaP Documentation](https://docs.wallet.human.tech/)
- [Sign-In with Ethereum (EIP-4361)](https://eips.ethereum.org/EIPS/eip-4361)
- [SIWE Documentation](https://docs.login.xyz/)
- [Drupal Documentation](https://www.drupal.org/docs)
- [DDEV Documentation](https://ddev.readthedocs.io/)

## License

GPL-2.0-or-later

## Contributing

Contributions welcome! See [CLAUDE.md](CLAUDE.md) for development documentation.

## Support

- **WaaP Support**: [Join the developer Telegram group](https://docs.wallet.human.tech/)
- **Drupal Support**: [Drupal.org Forums](https://www.drupal.org/forum)
- **Issue Tracker**: Use the project's issue tracker for bugs and feature requests