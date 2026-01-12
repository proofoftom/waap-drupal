# PLAN: Phase 4 — Frontend Wallet Integration

**Project:** Wallet as a Protocol Drupal Login Module
**Phase:** 4 — Frontend Wallet Integration
**Created:** 2025-01-12
**Status:** Ready to Execute

---

## Objective

Implement the frontend JavaScript for wallet connection, message signing, and Drupal integration. This phase creates the user-facing components that interact with the Wallet as a Protocol (WaaP) SDK and communicate with the backend authentication services built in Phase 3.

**Success Criteria:**
- Vite build pipeline configured and generating Drupal-compatible libraries
- WaaP SDK integrated and configured for wallet connection
- JavaScript wrapper handles EIP-1193 events (connect, disconnect, accountsChanged, chainChanged)
- Nonce endpoint provides single-use tokens for message signing
- Drupal block/plugin displays "Connect Wallet" button on login page
- Complete authentication flow works: connect → fetch nonce → sign → authenticate
- Auto-connect logic detects existing WaaP sessions
- Code follows Drupal JavaScript standards and attaches via libraries.yml
- CSS styles match Drupal admin theme conventions

---

## Context

### Project State
- **Mode:** yolo — Execute with minimal confirmation gates
- **Current Status:** Starting Phase 4
- **Completed Phases:**
  - Phase 1: Foundation & Environment Setup (DDEV + Drupal 10 running)
  - Phase 2: Wallet as a Protocol Integration Research (WaaP spec understood)
  - Phase 3: Backend Authentication System (signature verification, user management, REST API)

### Key Research Findings

From `.planning/phases/02-protocol-integration/RESEARCH.md` and `.planning/phases/04-frontend-integration/RESEARCH.md`:

**WaaP SDK Integration:**
- Package: `@human.tech/waap-sdk`
- API: `initWaaP()` sets `window.waap` with EIP-1193 compliant provider
- Login modal: `await window.waap.login()` returns login type ('waap', 'injected', 'walletconnect', null)
- Message signing: `personal_sign` method for EIP-191 signatures
- Auto-connect: WaaP persists sessions — check `eth_requestAccounts` first before showing login modal

**Critical WaaP Patterns:**
1. **Auto-Connect First:** Always check for existing session with `eth_requestAccounts` before calling `login()`
2. **Login Type Handling:** Different UI expectations for 'waap' vs 'injected' vs 'walletconnect'
3. **No WalletConnect Required:** WaaP-only mode (email/social auth) doesn't need WalletConnect project ID

**Build Tool Decision (ADR-005 Update):**
- **Vite** replaces Webpack (2024 standard)
- Package: `@ueberbit/vite-plugin-drupal` for Drupal integration
- Output: UMD/ESM bundles with auto-generated `libraries.yml`
- Benefits: Faster HMR, simpler config, actively maintained

**Drupal JavaScript Patterns:**
- `Drupal.behaviors` for initialization
- `drupalSettings` for passing config to JS
- Libraries defined in `.libraries.yml` files
- Attach via block plugins or preprocess hooks

### Backend API Status

**Existing REST Endpoint (Phase 3):**
- `POST /wallet-auth/authenticate` — Verifies signature and logs user in
- Request format:
  ```json
  {
    "wallet_address": "0x...",
    "signature": "0x...",
    "message": "Sign this message...",
    "nonce": "base64encodednonce"
  }
  ```
- Response format: `{ "success": true, "uid": 123, "username": "wallet_0x1234" }`

**Missing Backend Component:**
- **Nonce endpoint** — Frontend needs a way to fetch a nonce before signing
- This will be added as a prerequisite task (Task 1)

### Module Structure

Current state (`web/modules/custom/wallet_auth/`):
```
├── wallet_auth.info.yml         ✅ Exists
├── wallet_auth.module           ✅ Exists (empty)
├── wallet_auth.routing.yml      ✅ Has authenticate route
├── wallet_auth.services.yml     ✅ Has verification and user_manager
├── wallet_auth.permissions.yml  ✅ Has permissions
├── wallet_auth.install          ✅ Has database schema
├── composer.json                ✅ Exists
├── README.md                    ✅ Exists
├── src/
│   ├── Service/
│   │   ├── WalletVerification.php        ✅ Exists (Phase 3)
│   │   └── WalletUserManager.php         ✅ Exists (Phase 3)
│   └── Controller/
│       └── AuthenticateController.php    ✅ Exists (Phase 3)
├── js/                           ⚠️ Empty (needs to be created)
├── css/                          ⚠️ Doesn't exist
├── templates/                    ⚠️ Doesn't exist
└── package.json                  ⚠️ Doesn't exist (needs Vite setup)
```

### Known Constraints

- **WaaP-Only Mode:** Start with email/social auth only (no WalletConnect project ID needed)
- **Auto-Connect Required:** Must check for existing session before showing login UI
- **Event Handling:** Must handle all EIP-1193 events (connect, disconnect, accountsChanged, chainChanged)
- **Drupal Standards:** Must use `Drupal.behaviors`, `drupalSettings`, and proper library attachment
- **Message Format:** Must use simple message format that matches backend EIP-191 verification
- **Error Handling:** Must handle wallet rejection, network errors, and authentication failures
- **Security:** Never expose nonces in localStorage, validate all inputs

---

## Tasks

### Task 1: Add Nonce Endpoint (Backend)

Create a REST endpoint for fetching authentication nonces. This is a prerequisite for frontend implementation.

**Files:** `web/modules/custom/wallet_auth/src/Controller/NonceController.php`

**Action:**
Create PHP controller class with:
- Namespace: `Drupal\wallet_auth\Controller`
- Route: `/wallet-auth/nonce`
- Methods: `generateNonce()` — GET endpoint
- Constructor injection: `wallet_auth.verification`

**Response Format:**
```json
{
  "nonce": "base64encodednonce...",
  "expires_in": 300
}
```

**Implementation:**
```php
public function generateNonce(): JsonResponse {
  $walletAddress = $this->getRequest()->query->get('wallet_address');

  if (empty($walletAddress) || !$this->verification->validateAddress($walletAddress)) {
    return new JsonResponse(['error' => 'Invalid wallet address'], 400);
  }

  $nonce = $this->verification->generateNonce();
  $this->verification->storeNonce($nonce, $walletAddress);

  return new JsonResponse([
    'nonce' => $nonce,
    'expires_in' => WalletVerification::NONCE_LIFETIME,
  ]);
}
```

**Files to Update:**
- Add route to `wallet_auth.routing.yml`:
  ```yaml
  wallet_auth.nonce:
    path: '/wallet-auth/nonce'
    defaults:
      _controller: '\Drupal\wallet_auth\Controller\NonceController::generateNonce'
    methods: [GET]
    requirements:
      _access: 'TRUE'
    options:
      no_cache: 'TRUE'
  ```

**Verification:**
- Controller class exists at `src/Controller/NonceController.php`
- Route exists in `wallet_auth.routing.yml`
- `ddev drush route:debug | grep wallet_auth` shows both routes
- Endpoint returns JSON with nonce and expires_in fields
- Nonce is stored in tempstore with wallet address

**Done:** Nonce endpoint accessible for frontend

---

### Task 2: Initialize NPM Project

Set up NPM/package.json for JavaScript dependencies and build pipeline.

**Files:** `web/modules/custom/wallet_auth/package.json`

**Action:**
Create package.json with:
```json
{
  "name": "@drupal/wallet_auth",
  "version": "1.0.0",
  "description": "Wallet authentication for Drupal using Wallet as a Protocol",
  "type": "module",
  "scripts": {
    "build": "vite build",
    "dev": "vite build --watch --mode development"
  },
  "devDependencies": {
    "@human.tech/waap-sdk": "^1.0.0",
    "vite": "^5.0.0",
    "@ueberbit/vite-plugin-drupal": "^1.3.0"
  }
}
```

**Verification:**
- `package.json` exists in module root
- `ddev exec npm install` completes without errors
- `node_modules/` directory created
- Dependencies installed: `@human.tech/waap-sdk`, `vite`, `@ueberbit/vite-plugin-drupal`

**Done:** NPM project initialized with dependencies

---

### Task 3: Create Vite Configuration

Set up Vite with Drupal plugin for library building and libraries.yml generation.

**Files:** `web/modules/custom/wallet_auth/vite.config.js`

**Action:**
Create Vite config with:
```javascript
import { defineConfig } from 'vite';
import uebertool from '@ueberbit/vite-plugin-drupal';

export default defineConfig({
  plugins: [
    uebertool({
      // Auto-generate libraries.yml entries
      libraries: [
        {
          name: 'wallet_auth_connector',
          entry: './src/js/wallet-auth-connector.js',
          loaded: false,
          dependencies: ['core/drupal', 'core/drupalSettings'],
        },
        {
          name: 'wallet_auth_ui',
          entry: './src/js/wallet-auth-ui.js',
          loaded: false,
          dependencies: ['wallet_auth/wallet_auth_connector'],
          css: true, // Process CSS files
        },
      ],
    }),
  ],
  build: {
    // Output to module's js/dist directory
    outDir: 'js/dist',
    emptyOutDir: true,
    // Generate UMD for browser compatibility
    lib: {
      formats: ['umd'],
    },
    rollupOptions: {
      // Externalize WaaP SDK (load via CDN or separate library)
      external: ['@human.tech/waap-sdk'],
      output: {
        globals: {
          '@human.tech/waap-sdk': 'WaaP',
        },
      },
    },
  },
});
```

**Note:** This config uses `@ueberbit/vite-plugin-drupal` to auto-generate `wallet_auth.libraries.yml` from the libraries array.

**Alternative (Manual Libraries):**
If the plugin doesn't work, create manual config:
```javascript
export default defineConfig({
  build: {
    lib: {
      entry: {
        connector: './src/js/wallet-auth-connector.js',
        ui: './src/js/wallet-auth-ui.js',
      },
      name: 'WalletAuth',
      formats: ['iife'],
      fileName: (format, entryName) => `wallet-auth-${entryName}.js`,
    },
    outDir: 'js/dist',
    rollupOptions: {
      external: ['@human.tech/waap-sdk'],
    },
  },
});
```

**Verification:**
- `vite.config.js` exists in module root
- Valid JavaScript syntax
- Configuration references correct entry points
- Output directory is `js/dist`

**Done:** Vite configured for Drupal library building

---

### Task 4: Create Source Directory Structure

Set up JavaScript and CSS source directories.

**Action:**
Create directory structure:
```
web/modules/custom/wallet_auth/
├── src/
│   ├── js/
│   │   ├── wallet-auth-connector.js   # WaaP SDK wrapper
│   │   └── wallet-auth-ui.js          # UI logic and Drupal behaviors
│   └── css/
│       └── wallet-auth.css            # Component styles
├── js/
│   └── dist/                          # Build output (generated by Vite)
└── templates/
    └── wallet-login-button.html.twig  # Button template
```

**Steps:**
1. Create `src/js/` directory
2. Create `src/css/` directory
3. Create `js/dist/` directory
4. Create `templates/` directory

**Verification:**
- All directories created
- Write permissions correct (check with `ls -la`)

**Done:** Source structure ready for JavaScript and CSS files

---

### Task 5: Create WaaP SDK Wrapper

Create a wrapper module for WaaP SDK that handles initialization and provides a clean API.

**Files:** `web/modules/custom/wallet_auth/src/js/wallet-auth-connector.js`

**Action:**
Create JavaScript module with:
```javascript
/**
 * @file
 * WaaP SDK wrapper for wallet authentication.
 */

import { initWaaP } from '@human.tech/waap-sdk';

/**
 * Wallet connector service.
 *
 * Wraps WaaP SDK with Drupal-specific patterns and error handling.
 */
class WalletConnector {
  constructor(config = {}) {
    this.config = {
      authenticationMethods: ['email', 'social'],
      allowedSocials: ['google', 'twitter', 'discord'],
      ...config,
    };
    this.provider = null;
    this.account = null;
    this.chainId = null;
    this.listeners = new Map();
  }

  /**
   * Initialize WaaP SDK.
   *
   * Must be called before any other methods.
   */
  async init() {
    if (this.provider) {
      return; // Already initialized
    }

    try {
      // Initialize WaaP SDK
      initWaaP({
        config: this.config,
        useStaging: false,
      });

      // Get provider
      this.provider = window.waap;

      if (!this.provider) {
        throw new Error('WaaP provider not available after initialization');
      }

      // Set up event listeners
      this.attachEventListeners();

      console.log('WaaP SDK initialized successfully');
    } catch (error) {
      console.error('Failed to initialize WaaP SDK:', error);
      throw error;
    }
  }

  /**
   * Attach EIP-1193 event listeners.
   */
  attachEventListeners() {
    if (!this.provider) return;

    // Connect event
    this.provider.on('connect', (connectInfo) => {
      this.chainId = connectInfo.chainId;
      this.notifyListeners('connect', connectInfo);
      console.log('Wallet connected:', connectInfo);
    });

    // Disconnect event
    this.provider.on('disconnect', (error) => {
      this.account = null;
      this.chainId = null;
      this.notifyListeners('disconnect', error);
      console.log('Wallet disconnected:', error);
    });

    // Account changed event
    this.provider.on('accountsChanged', (accounts) => {
      if (accounts.length === 0) {
        // User disconnected wallet
        this.account = null;
        this.notifyListeners('disconnect', null);
      } else {
        this.account = accounts[0];
        this.notifyListeners('accountChanged', accounts);
      }
      console.log('Accounts changed:', accounts);
    });

    // Chain changed event
    this.provider.on('chainChanged', (chainId) => {
      this.chainId = chainId;
      this.notifyListeners('chainChanged', chainId);
      // Recommended: reload page on chain change
      window.location.reload();
    });
  }

  /**
   * Check for existing session (auto-connect).
   *
   * Returns account if user is already authenticated, null otherwise.
   */
  async checkSession() {
    if (!this.provider) {
      await this.init();
    }

    try {
      const accounts = await this.provider.request({
        method: 'eth_requestAccounts',
      });

      if (accounts && accounts.length > 0) {
        this.account = accounts[0];
        console.log('Auto-connected with existing session:', this.account);
        return this.account;
      }

      return null;
    } catch (error) {
      // No existing session or user rejected
      console.log('No existing session found');
      return null;
    }
  }

  /**
   * Show WaaP login modal.
   *
   * Returns login type ('waap', 'injected', 'walletconnect', null).
   */
  async login() {
    if (!this.provider) {
      await this.init();
    }

    try {
      const loginType = await this.provider.login();
      console.log('Login type:', loginType);

      if (loginType) {
        // Get accounts after login
        const accounts = await this.provider.request({
          method: 'eth_requestAccounts',
        });
        this.account = accounts[0];
      }

      return loginType;
    } catch (error) {
      console.error('Login failed:', error);
      throw error;
    }
  }

  /**
   * Sign a message using personal_sign (EIP-191).
   *
   * @param {string} message
   *   The message to sign.
   *
   * @return {string}
   *   The signature (0x-prefixed hex).
   */
  async signMessage(message) {
    if (!this.provider || !this.account) {
      throw new Error('Wallet not connected');
    }

    try {
      const signature = await this.provider.request({
        method: 'personal_sign',
        params: [message, this.account],
      });

      console.log('Message signed successfully');
      return signature;
    } catch (error) {
      console.error('Message signing failed:', error);
      throw error;
    }
  }

  /**
   * Get current wallet address.
   */
  getAddress() {
    return this.account;
  }

  /**
   * Get current chain ID.
   */
  getChainId() {
    return this.chainId;
  }

  /**
   * Check if wallet is connected.
   */
  isConnected() {
    return !!this.account;
  }

  /**
   * Add event listener.
   */
  on(event, callback) {
    if (!this.listeners.has(event)) {
      this.listeners.set(event, []);
    }
    this.listeners.get(event).push(callback);
  }

  /**
   * Remove event listener.
   */
  off(event, callback) {
    if (!this.listeners.has(event)) return;
    const callbacks = this.listeners.get(event);
    const index = callbacks.indexOf(callback);
    if (index > -1) {
      callbacks.splice(index, 1);
    }
  }

  /**
   * Notify all listeners of an event.
   */
  notifyListeners(event, data) {
    if (!this.listeners.has(event)) return;
    this.listeners.get(event).forEach(callback => callback(data));
  }

  /**
   * Logout and disconnect wallet.
   */
  async logout() {
    if (!this.provider) return;

    try {
      await this.provider.logout();
      this.account = null;
      this.chainId = null;
      console.log('Logged out successfully');
    } catch (error) {
      console.error('Logout failed:', error);
    }
  }

  /**
   * Cleanup event listeners.
   */
  destroy() {
    if (this.provider) {
      this.provider.removeAllListeners();
    }
    this.listeners.clear();
    this.provider = null;
    this.account = null;
    this.chainId = null;
  }
}

// Export for use in Drupal behaviors
window.WalletAuthConnector = WalletConnector;

export default WalletConnector;
```

**Verification:**
- File exists at `src/js/wallet-auth-connector.js`
- Implements all required methods (init, checkSession, login, signMessage, etc.)
- Handles EIP-1193 events correctly
- Has proper error handling and logging

**Done:** WaaP SDK wrapper created with Drupal-compatible API

---

### Task 6: Create Drupal Behaviors and UI Logic

Create the main JavaScript file that integrates with Drupal's behaviors system.

**Files:** `web/modules/custom/wallet_auth/src/js/wallet-auth-ui.js`

**Action:**
Create JavaScript file with:
```javascript
/**
 * @file
 * Wallet authentication UI integration with Drupal behaviors.
 */

(function ($, Drupal, drupalSettings) {
  'use strict';

  /**
   * Wallet authentication behavior.
   */
  Drupal.behaviors.walletAuth = {
    connector: null,
    state: 'idle', // idle, connecting, connected, signing, error

    attach: function (context, settings) {
      // Only attach once
      if ($('body').once('wallet-auth-init').length) {
        this.init(context, settings);
      }
    },

    /**
     * Initialize wallet authentication.
     */
    init: function (context, settings) {
      const self = this;

      // Get configuration from drupalSettings
      const config = settings.walletAuth || {};

      // Initialize connector
      this.connector = new WalletAuthConnector({
        authenticationMethods: config.authenticationMethods || ['email', 'social'],
        allowedSocials: config.allowedSocials || ['google', 'twitter', 'discord'],
      });

      // Bind login button
      const $loginButton = $('.wallet-auth-login-btn', context);
      $loginButton.on('click', function (e) {
        e.preventDefault();
        self.handleLogin();
      });

      // Bind disconnect button
      const $disconnectButton = $('.wallet-auth-disconnect-btn', context);
      $disconnectButton.on('click', function (e) {
        e.preventDefault();
        self.handleDisconnect();
      });

      // Initialize connector and check for existing session
      this.connector.init().then(() => {
        return this.connector.checkSession();
      }).then((account) => {
        if (account) {
          // User already authenticated
          self.setState('connected');
          self.updateUI();
          // Auto-proceed with authentication
          self.authenticate(account);
        } else {
          // Show login button
          self.setState('idle');
          self.updateUI();
        }
      }).catch((error) => {
        console.error('Initialization error:', error);
        self.setState('error');
        self.showError(error.message);
      });

      // Listen for disconnect events
      this.connector.on('disconnect', () => {
        self.setState('idle');
        self.updateUI();
      });

      // Listen for account changes
      this.connector.on('accountChanged', (accounts) => {
        if (accounts.length > 0) {
          self.authenticate(accounts[0]);
        } else {
          self.setState('idle');
          self.updateUI();
        }
      });
    },

    /**
     * Handle login button click.
     */
    handleLogin: function () {
      const self = this;

      this.setState('connecting');

      this.connector.login().then((loginType) => {
        if (!loginType) {
          // User cancelled
          this.setState('idle');
          return;
        }

        console.log('Logged in via:', loginType);
        this.setState('connected');

        // Proceed with authentication
        const address = this.connector.getAddress();
        this.authenticate(address);

      }).catch((error) => {
        console.error('Login error:', error);
        this.setState('error');
        this.showError(error.message);
      });
    },

    /**
     * Handle disconnect button click.
     */
    handleDisconnect: function () {
      this.connector.logout();
      this.setState('idle');
      this.updateUI();
    },

    /**
     * Complete authentication flow: fetch nonce, sign, verify.
     */
    authenticate: function (address) {
      const self = this;

      this.setState('signing');
      this.updateUI();

      // Step 1: Fetch nonce from backend
      this.fetchNonce(address).then((data) => {
        const nonce = data.nonce;

        // Step 2: Create message to sign
        const message = this.createSignMessage(address, nonce);

        // Step 3: Request signature from wallet
        return this.connector.signMessage(message);

      }).then((signature) => {
        // Step 4: Send signature to backend for verification
        return this.sendAuthentication(address, signature);

      }).then((response) => {
        if (response.success) {
          // Authentication successful
          this.setState('authenticated');
          this.showSuccess(`Logged in as ${response.username}`);
          // Optionally redirect or update page
          if (drupalSettings.walletAuth.redirectOnSuccess) {
            window.location.href = drupalSettings.walletAuth.redirectOnSuccess;
          } else {
            window.location.reload();
          }
        } else {
          throw new Error(response.error || 'Authentication failed');
        }

      }).catch((error) => {
        console.error('Authentication error:', error);
        this.setState('error');
        this.showError(error.message || 'Authentication failed');
      });
    },

    /**
     * Fetch nonce from backend.
     */
    fetchNonce: function (address) {
      const apiEndpoint = drupalSettings.walletAuth.apiEndpoint;

      return fetch(`${apiEndpoint}/nonce?wallet_address=${address}`, {
        method: 'GET',
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json',
        },
      }).then((response) => {
        if (!response.ok) {
          throw new Error('Failed to fetch nonce');
        }
        return response.json();
      });
    },

    /**
     * Create message for signing.
     *
     * Uses simple format compatible with backend EIP-191 verification.
     */
    createSignMessage: function (address, nonce) {
      return `Sign this message to prove ownership of ${address}.\n\nNonce: ${nonce}`;
    },

    /**
     * Send authentication data to backend.
     */
    sendAuthentication: function (address, signature) {
      const apiEndpoint = drupalSettings.walletAuth.apiEndpoint;
      const nonce = this.connector.lastNonce; // Store this when fetching

      const message = this.createSignMessage(address, nonce);

      return fetch(apiEndpoint, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json',
        },
        body: JSON.stringify({
          wallet_address: address,
          signature: signature,
          message: message,
          nonce: nonce,
        }),
      }).then((response) => {
        if (!response.ok) {
          return response.json().then((data) => {
            throw new Error(data.error || 'Authentication failed');
          });
        }
        return response.json();
      });
    },

    /**
     * Set current state.
     */
    setState: function (state) {
      this.state = state;
      $('body').attr('data-wallet-auth-state', state);
    },

    /**
     * Update UI based on current state.
     */
    updateUI: function () {
      const $container = $('.wallet-auth-container');
      const $loginButton = $('.wallet-auth-login-btn');
      const $disconnectButton = $('.wallet-auth-disconnect-btn');
      const $status = $('.wallet-auth-status');

      // Hide all by default
      $loginButton.addClass('visually-hidden');
      $disconnectButton.addClass('visually-hidden');

      switch (this.state) {
        case 'idle':
          $loginButton.removeClass('visually-hidden');
          $status.text('Connect your wallet to login');
          break;

        case 'connecting':
          $loginButton.removeClass('visually-hidden').prop('disabled', true);
          $status.text('Connecting to wallet...');
          break;

        case 'connected':
          $disconnectButton.removeClass('visually-hidden');
          $status.text(`Connected: ${this.formatAddress(this.connector.getAddress())}`);
          break;

        case 'signing':
          $status.text('Please sign the message in your wallet...');
          break;

        case 'authenticated':
          $status.text('Authentication successful!');
          break;

        case 'error':
          $loginButton.removeClass('visually-hidden').prop('disabled', false);
          $status.text('Authentication failed. Please try again.');
          break;
      }
    },

    /**
     * Show error message.
     */
    showError: function (message) {
      // Use Drupal messages or custom UI
      if (Drupal.behaviors.walletAuth.showMessage) {
        Drupal.behaviors.walletAuth.showMessage(message, 'error');
      } else {
        alert(message);
      }
    },

    /**
     * Show success message.
     */
    showSuccess: function (message) {
      if (Drupal.behaviors.walletAuth.showMessage) {
        Drupal.behaviors.walletAuth.showMessage(message, 'status');
      } else {
        console.log(message);
      }
    },

    /**
     * Format address for display.
     */
    formatAddress: function (address) {
      if (!address) return '';
      return `${address.substring(0, 6)}...${address.substring(address.length - 4)}`;
    },

    /**
     * Detach behavior (cleanup).
     */
    detach: function (context, settings) {
      if (this.connector) {
        this.connector.destroy();
      }
    },
  };

  /**
   * Helper to show Drupal messages.
   */
  Drupal.behaviors.walletAuth.showMessage = function (message, type = 'status') {
    // Add message to Drupal's message container
    const $messages = $('.messages__wrapper', context);
    if ($messages.length) {
      const $message = $(`<div class="messages messages--${type}">${message}</div>`);
      $messages.append($message);
      // Auto-remove after 5 seconds
      setTimeout(() => $message.fadeOut(), 5000);
    }
  };

})(jQuery, Drupal, drupalSettings);
```

**Verification:**
- File exists at `src/js/wallet-auth-ui.js`
- Implements Drupal.behaviors.walletAuth
- Handles all authentication states (idle, connecting, connected, signing, error)
- Integrates with WalletConnector from Task 5
- Uses drupalSettings for configuration
- Has proper cleanup in detach method

**Done:** Drupal behaviors and UI logic created

---

### Task 7: Create CSS Styles

Create styles for wallet authentication components.

**Files:** `web/modules/custom/wallet_auth/src/css/wallet-auth.css`

**Action:**
Create CSS file with:
```css
/**
 * @file
 * Wallet authentication component styles.
 */

/**
 * Wallet auth container.
 */
.wallet-auth-container {
  margin: 1rem 0;
  max-width: 400px;
}

/**
 * Login button.
 */
.wallet-auth-login-btn {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 0.5rem;
  padding: 0.75rem 1.5rem;
  background-color: #6c757d;
  color: #fff;
  border: 2px solid #6c757d;
  border-radius: 4px;
  font-size: 1rem;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s ease;
  text-decoration: none;
}

.wallet-auth-login-btn:hover,
.wallet-auth-login-btn:focus {
  background-color: #5a6268;
  border-color: #5a6268;
  outline: none;
}

.wallet-auth-login-btn:disabled {
  background-color: #ccc;
  border-color: #ccc;
  cursor: not-allowed;
}

/**
 * Disconnect button.
 */
.wallet-auth-disconnect-btn {
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.5rem 1rem;
  background-color: transparent;
  color: #6c757d;
  border: 1px solid #6c757d;
  border-radius: 4px;
  font-size: 0.875rem;
  cursor: pointer;
  transition: all 0.2s ease;
}

.wallet-auth-disconnect-btn:hover,
.wallet-auth-disconnect-btn:focus {
  background-color: #f8f9fa;
}

/**
 * Status text.
 */
.wallet-auth-status {
  margin-top: 0.5rem;
  font-size: 0.875rem;
  color: #6c757d;
}

/**
 * Loading spinner.
 */
.wallet-auth-loading {
  display: inline-block;
  width: 1rem;
  height: 1rem;
  border: 2px solid rgba(255, 255, 255, 0.3);
  border-top-color: #fff;
  border-radius: 50%;
  animation: wallet-auth-spin 0.6s linear infinite;
}

@keyframes wallet-auth-spin {
  to {
    transform: rotate(360deg);
  }
}

/**
 * State-specific styles.
 */
[data-wallet-auth-state="connecting"] .wallet-auth-login-btn {
  position: relative;
}

[data-wallet-auth-state="connecting"] .wallet-auth-login-btn::before {
  content: '';
  position: absolute;
  left: 0.75rem;
  width: 1rem;
  height: 1rem;
  border: 2px solid rgba(255, 255, 255, 0.3);
  border-top-color: #fff;
  border-radius: 50%;
  animation: wallet-auth-spin 0.6s linear infinite;
}

[data-wallet-auth-state="connecting"] .wallet-auth-login-btn span {
  padding-left: 1.5rem;
}

/**
 * Error state.
 */
.wallet-auth-container[data-wallet-auth-state="error"] .wallet-auth-status {
  color: #dc3545;
}

/**
 * Success state.
 */
.wallet-auth-container[data-wallet-auth-state="authenticated"] .wallet-auth-status {
  color: #28a745;
}

/**
 * Wallet icon (SVG background or emoji).
 */
.wallet-auth-icon {
  display: inline-block;
  width: 1.25rem;
  height: 1.25rem;
  background-size: contain;
  background-repeat: no-repeat;
  background-position: center;
}

/**
 * Responsive adjustments.
 */
@media (max-width: 600px) {
  .wallet-auth-container {
    max-width: 100%;
  }

  .wallet-auth-login-btn {
    width: 100%;
  }
}
```

**Verification:**
- File exists at `src/css/wallet-auth.css`
- Styles for all UI components (buttons, status, loading, states)
- Responsive design included
- Follows Drupal naming conventions

**Done:** CSS styles created

---

### Task 8: Create Login Button Block Plugin

Create a Drupal block plugin for rendering the wallet login button.

**Files:** `web/modules/custom/wallet_auth/src/Plugin/Block/WalletLoginBlock.php`

**Action:**
Create PHP block class with:
```php
<?php

declare(strict_types=1);

namespace Drupal\wallet_auth\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Provides a wallet login button block.
 *
 * @Block(
 *   id = "wallet_login_block",
 *   admin_label = @Translation("Wallet Login Button"),
 *   category = @Translation("User")
 * )
 */
class WalletLoginBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $build = [];

    // Only show for anonymous users
    $current_user = \Drupal::currentUser();
    if ($current_user->isAuthenticated()) {
      return $build;
    }

    // Get API endpoint from settings or use default
    $api_endpoint = '/wallet-auth';

    $build['#theme'] = 'wallet_login_button';
    $build['#attached'] = [
      'library' => [
        'wallet_auth/wallet_auth_ui',
      ],
      'drupalSettings' => [
        'walletAuth' => [
          'apiEndpoint' => $api_endpoint,
          'authenticationMethods' => ['email', 'social'],
          'allowedSocials' => ['google', 'twitter', 'discord'],
          'redirectOnSuccess' => '/user',
        ],
      ],
    ];

    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function blockAccess(AccountInterface $account) {
    // Only show for anonymous users
    return $account->isAnonymous() ? AccessResult::allowed() : AccessResult::forbidden();
  }

}
```

**Note:** Add `use Drupal\Core\Access\AccessResult;` at top of file.

**Verification:**
- Block plugin exists at `src/Plugin/Block/WalletLoginBlock.php`
- Has @Block annotation with correct metadata
- Attaches wallet_auth_ui library
- Passes drupalSettings to JavaScript
- Only visible to anonymous users

**Done:** Login button block plugin created

---

### Task 9: Create Twig Template

Create Twig template for rendering the login button.

**Files:** `web/modules/custom/wallet_auth/templates/wallet-login-button.html.twig`

**Action:**
Create Twig template with:
```twig
{#
/**
 * @file
 * Default theme implementation for wallet login button.
 *
 * Available variables:
 * - None (all config via drupalSettings)
 */
#}
<div class="wallet-auth-container" data-wallet-auth-state="idle">
  <button class="wallet-auth-login-btn">
    <span>Connect Wallet</span>
  </button>
  <button class="wallet-auth-disconnect-btn visually-hidden">
    <span>Disconnect</span>
  </button>
  <div class="wallet-auth-status"></div>
</div>
```

**Register Theme Hook:**
Add to `wallet_auth.module`:
```php
/**
 * Implements hook_theme().
 */
function wallet_auth_theme($existing, $type, $theme, $path) {
  return [
    'wallet_login_button' => [
      'variables' => [],
    ],
  ];
}
```

**Verification:**
- Twig template exists at `templates/wallet-login-button.html.twig`
- hook_theme() implementation exists in `wallet_auth.module`
- Template has all required classes and structure

**Done:** Twig template created and registered

---

### Task 10: Build JavaScript with Vite

Run Vite build to generate minified JavaScript bundles.

**Steps:**
1. Run: `ddev exec npm run build`
2. Verify output files exist in `js/dist/`:
   - `wallet-auth-connector.js` (and .map)
   - `wallet-auth-ui.js` (and .map)
3. Check that `wallet_auth.libraries.yml` was generated (if using Drupal plugin)

**Expected Output Files:**
```
js/dist/
├── wallet-auth-connector.js
├── wallet-auth-connector.js.map
├── wallet-auth-ui.js
├── wallet-auth-ui.js.map
└── assets/ (if using plugin)
```

**If Libraries.yml Not Auto-Generated:**
Create manually at `wallet_auth.libraries.yml`:
```yaml
wallet_auth_connector:
  version: 1.0.0
  js:
    js/dist/wallet-auth-connector.js: {}
  dependencies:
    - core/drupal
    - core/drupalSettings

wallet_auth_ui:
  version: 1.0.0
  js:
    js/dist/wallet-auth-ui.js: {}
  css:
    component:
      src/css/wallet-auth.css: {}
  dependencies:
    - wallet_auth/wallet_auth_connector
```

**Verification:**
- Build completes without errors
- JavaScript files exist in `js/dist/`
- `wallet_auth.libraries.yml` exists (auto-generated or manual)
- Files are minified (production) or readable (development)

**Done:** JavaScript built and libraries.yml configured

---

### Task 11: Clear Caches and Rebuild

Clear Drupal caches to recognize new block and library definitions.

**Steps:**
1. Clear all caches: `ddev drush cache:rebuild`
2. Rebuild registry: `ddev drush registry:rebuild`
3. Verify block is available: `ddev drush block:debug | grep wallet`
4. Verify library is available: `ddev drush library:debug | grep wallet_auth`

**Verification:**
- Cache cleared successfully
- Block appears in block list
- Library appears in library list
- No errors in Drupal logs

**Done:** Caches cleared, new components recognized

---

### Task 12: Place Login Block on User Login Page

Add the wallet login button block to the user login page.

**Steps:**
1. Navigate to block layout: `/admin/structure/block`
2. Find "Wallet Login Button" block
3. Place block in "Content" region on user login page
4. Configure block title (optional: hide title)
5. Save block placement

**Alternative (Drush):**
```bash
ddev drush block:place wallet_login_block --region=content --path=/user/login
```

**Verification:**
- Block appears on `/user/login` page
- "Connect Wallet" button is visible (for anonymous users)
- JavaScript library is attached (check page source)
- drupalSettings are present (check browser console)

**Done:** Login block placed on user login page

---

### Task 13: Fix Backend Nonce Storage Issue

Update WalletVerification service to store nonces properly for frontend access.

**Issue:** The current `storeNonce()` implementation stores nonces in private tempstore keyed by nonce, but the frontend needs to know the nonce value.

**Fix Needed:** The frontend needs to receive the nonce from the `/wallet-auth/nonce` endpoint and then send it back with the signature. The nonce should be stored with a timestamp for expiration checking.

**Files:** `web/modules/custom/wallet_auth/src/Service/WalletVerification.php`

**Action:**
Review the `storeNonce()` method to ensure it:
1. Stores the nonce with the wallet address
2. Includes a timestamp for expiration checking
3. Allows retrieval by nonce value for verification

**Current Implementation Check:**
The existing implementation in Phase 3 should already handle this correctly:
- `generateNonce()` creates random nonce
- `storeNonce()` stores nonce with wallet address and timestamp
- `verifyNonce()` checks nonce exists, matches wallet, and hasn't expired
- `deleteNonce()` removes nonce after use

**Verification:**
- Nonce generation works correctly
- Nonce storage includes timestamp
- Nonce verification checks expiration (5 minutes)
- Nonce is single-use (deleted after verification)

**Done:** Backend nonce handling verified (already implemented in Phase 3)

---

### Task 14: Test Complete Authentication Flow

Manually test the complete authentication flow in the browser.

**Steps:**
1. Open site in browser: `ddev launch`
2. Navigate to `/user/login` (or logout if already logged in)
3. Open browser developer tools (Console tab)
4. Click "Connect Wallet" button
5. Select WaaP login method (email/social)
6. Complete WaaP authentication flow
7. Approve message signing in wallet
8. Verify user is logged in to Drupal
9. Check browser console for errors
10. Check Drupal logs: `ddev drush log:watch`

**Expected Flow:**
```
User clicks "Connect Wallet"
  → WaaP login modal opens
  → User selects auth method (e.g., Google)
  → User completes OAuth flow
  → WaaP emits 'connect' event with address
  → Frontend fetches nonce from /wallet-auth/nonce
  → Frontend requests signature via personal_sign
  → User approves signature in WaaP
  → Frontend sends signature to /wallet-auth/authenticate
  → Backend verifies signature (EIP-191)
  → Backend creates/logs in Drupal user
  → Frontend receives success response
  → Page reloads with user logged in
```

**Verification:**
- WaaP modal opens correctly
- User can complete authentication
- Nonce is fetched successfully
- Signature request appears in wallet
- User can approve signature
- Backend receives authentication request
- User is created/logged in to Drupal
- No errors in browser console
- No errors in Drupal logs

**Done:** Complete authentication flow works end-to-end

---

### Task 15: Test Auto-Connect Flow

Verify that existing WaaP sessions are detected on page load.

**Steps:**
1. Complete a successful authentication (from Task 14)
2. Stay logged in to WaaP (don't logout)
3. Logout of Drupal (but stay on site)
4. Refresh the page or navigate to `/user/login`
5. Verify that user is automatically authenticated without clicking "Connect Wallet"
6. Check console for "Auto-connected with existing session" message

**Expected Behavior:**
- On page load, `checkSession()` is called
- Existing WaaP session is detected
- Authentication flow proceeds automatically
- User is logged in without manual button click

**Verification:**
- Auto-connect works for existing WaaP sessions
- Login button is hidden when auto-connected
- Console shows "Auto-connected with existing session"
- User is logged in to Drupal automatically

**Done:** Auto-connect flow works correctly

---

### Task 16: Test Error Handling

Verify proper error handling for various failure scenarios.

**Test Cases:**

1. **User Cancels Login:**
   - Click "Connect Wallet"
   - Close WaaP modal without completing auth
   - Verify: State returns to 'idle', error message shown

2. **User Rejects Signature:**
   - Connect wallet successfully
   - Reject signature request in wallet
   - Verify: Error state, appropriate error message

3. **Invalid Signature:**
   - (Hard to test manually) Send invalid signature to backend
   - Verify: Backend returns 401, frontend shows error

4. **Expired Nonce:**
   - Fetch nonce
   - Wait 6 minutes (nonce expires in 5)
   - Try to authenticate
   - Verify: Backend returns 400, frontend shows error

5. **Network Error:**
   - Disconnect from internet
   - Try to authenticate
   - Verify: Error state, network error message

6. **Wallet Disconnect:**
   - Connect and authenticate
   - Disconnect wallet from WaaP interface
   - Verify: UI updates to show disconnected state

**Verification:**
- All error cases handled gracefully
- User sees helpful error messages
- No console errors
- State machine works correctly (idle → connecting → connected → signing → authenticated/error)
- User can retry after error

**Done:** Error handling works for all scenarios

---

### Task 17: Test Multiple Browsers/Sessions

Verify authentication works across different browsers and sessions.

**Steps:**
1. Test in Chrome: Complete authentication flow
2. Test in Firefox: Complete authentication flow
3. Test in Safari (if available): Complete authentication flow
4. Test in private/incognito mode
5. Test with multiple browser windows open simultaneously

**Verification:**
- Flow works in all major browsers
- Each browser session is independent
- Incognito mode works correctly
- Multiple windows don't interfere with each other

**Done:** Cross-browser compatibility verified

---

### Task 18: Verify Code Quality

Run JavaScript linters and check Drupal coding standards.

**Steps:**
1. Install ESLint (if desired): `ddev exec npm install --save-dev eslint`
2. Create `.eslintrc.json` for Drupal JavaScript standards
3. Run linter: `ddev exec npm run lint` (if configured)
4. Check for console.log statements in production code (should use Drupal logger)
5. Verify no hardcoded values (should use drupalSettings)

**JavaScript Standards to Verify:**
- Use 'use strict' mode
- No global variables (except for exports)
- Proper JSDoc comments
- No console.log in production (use Drupal.logger or remove)
- Consistent code style (indentation, quotes, etc.)

**Verification:**
- No linting errors
- Code follows Drupal JavaScript standards
- JSDoc comments present
- No debug console.log statements
- Proper error handling

**Done:** JavaScript code quality verified

---

### Task 19: Update README with Frontend Instructions

Document the frontend integration in the module README.

**Files:** `web/modules/custom/wallet_auth/README.md`

**Action:**
Add sections to README:
```markdown
## Frontend Integration

### JavaScript Libraries

The module includes the following JavaScript libraries:

- `wallet_auth_connector` - WaaP SDK wrapper
- `wallet_auth_ui` - UI logic and Drupal behaviors

### Usage

#### Using the Block

1. Navigate to Structure > Blocks
2. Place the "Wallet Login Button" block
3. Configure visibility (recommended: anonymous users only)

#### Using in Custom Code

```php
// Attach library to your page
$build['#attached']['library'][] = 'wallet_auth/wallet_auth_ui';

// Pass configuration to JavaScript
$build['#attached']['drupalSettings']['walletAuth'] = [
  'apiEndpoint' => '/wallet-auth',
  'authenticationMethods' => ['email', 'social'],
  'allowedSocials' => ['google', 'twitter', 'discord'],
];
```

### Configuration

The following options can be set via `drupalSettings.walletAuth`:

- `apiEndpoint` - Path to authentication endpoints (default: '/wallet-auth')
- `authenticationMethods` - Array of auth methods: ['email', 'social', 'phone', 'wallet']
- `allowedSocials` - Array of social providers: ['google', 'twitter', 'discord', 'github']
- `redirectOnSuccess` - Path to redirect after successful login (default: '/user')

### Building JavaScript

To rebuild the JavaScript:

```bash
cd web/modules/custom/wallet_auth
npm install
npm run build
```

### Development

To build in development mode with source maps:

```bash
npm run dev
```
```

**Verification:**
- README updated with frontend documentation
- Usage examples provided
- Build instructions included
- Configuration options documented

**Done:** Frontend documentation complete

---

### Task 20: Create Tests for Frontend (Optional)

Create JavaScript tests for wallet authentication components.

**Files:** `web/modules/custom/wallet_auth/tests/js/wallet-auth.test.js`

**Action:**
Create tests using a testing framework (e.g., Jest, QUnit):

Test cases:
- WalletConnector initialization
- checkSession() with existing session
- checkSession() without existing session
- login() returns correct login type
- signMessage() creates valid signature
- Event listeners work correctly
- State machine transitions
- Error handling

**Note:** This is optional for Phase 4 completion. Tests can be added in Phase 5 or 6.

**Verification:**
- Test file exists
- Tests can be run
- Critical paths are tested

**Done:** Frontend tests created (optional)

---

## Execution Order

Tasks must be completed in sequential order due to dependencies:

```
1 → 2 → 3 → 4 → 5 → 6 → 7 → 8 → 9 → 10 → 11 → 12 → 13 → 14 → 15 → 16 → 17 → 18 → 19 → 20
```

**Checkpoints:**
- **After Task 3:** Vite build pipeline configured
- **After Task 6:** JavaScript code complete
- **After Task 9:** All frontend files created
- **After Task 10:** JavaScript built and ready
- **After Task 12:** Block visible on login page
- **After Task 14:** Complete authentication flow works
- **After Task 16:** Error handling verified

---

## Success Criteria

Phase 4 is complete when ALL of the following are true:

1. **Build Pipeline:** Vite configured and building JavaScript successfully
2. **WaaP SDK:** Integrated and configured for wallet connection
3. **JavaScript Modules:** Connector and UI modules created
4. **Nonce Endpoint:** Backend provides nonces for authentication
5. **Drupal Block:** Login button block appears on user login page
6. **Libraries.yml:** JavaScript libraries properly registered
7. **drupalSettings:** Configuration passed to JavaScript
8. **Auto-Connect:** Existing WaaP sessions detected automatically
9. **Authentication Flow:** Complete flow works (connect → nonce → sign → authenticate)
10. **Error Handling:** All error cases handled gracefully
11. **CSS:** Component styles match Drupal theme
12. **Code Quality:** JavaScript follows Drupal standards
13. **Documentation:** README updated with frontend instructions
14. **Cross-Browser:** Works in Chrome, Firefox, Safari
15. **User Experience:** Clear feedback at each step of authentication

---

## Output Artifacts

After completing this phase, the following will exist:

**New Files:**
```
web/modules/custom/wallet_auth/
├── package.json                          # NPM configuration
├── vite.config.js                        # Vite build configuration
├── wallet_auth.libraries.yml             # Auto-generated or manual
├── src/
│   ├── js/
│   │   ├── wallet-auth-connector.js      # WaaP SDK wrapper
│   │   └── wallet-auth-ui.js             # Drupal behaviors and UI
│   ├── css/
│   │   └── wallet-auth.css               # Component styles
│   └── Controller/
│       └── NonceController.php           # Nonce endpoint controller
├── templates/
│   └── wallet-login-button.html.twig     # Button template
└── js/
    └── dist/
        ├── wallet-auth-connector.js      # Built connector
        ├── wallet-auth-connector.js.map  # Source map
        ├── wallet-auth-ui.js             # Built UI
        └── wallet-auth-ui.js.map         # Source map
```

**Updated Files:**
- `wallet_auth.module` — hook_theme() implementation
- `wallet_auth.routing.yml` — nonce route added
- `README.md` — frontend documentation added

**NPM Dependencies:**
- `@human.tech/waap-sdk` — Wallet as a Protocol SDK
- `vite` — Build tool
- `@ueberbit/vite-plugin-drupal` — Drupal integration plugin

**REST Endpoints:**
- `GET /wallet-auth/nonce` — Fetch authentication nonce
- `POST /wallet-auth/authenticate` — Verify signature and login (existing from Phase 3)

**Drupal Components:**
- `wallet_login_block` — Block plugin for login button
- `wallet_auth_connector` library — WaaP SDK wrapper
- `wallet_auth_ui` library — UI logic and styles
- `wallet_login_button` theme hook — Twig template

---

## Next Steps

After Phase 4 completion, proceed to **Phase 5: Integration & Polish** which will:

1. Add module configuration form (admin settings)
2. Implement configurable options (network selection, auth methods)
3. Add comprehensive error handling and user feedback
4. Ensure full Drupal coding standards compliance
5. Add inline documentation and comments
6. Create admin interface for module configuration

---

## Notes

- **DDEV Commands:** Always use `ddev` prefix for shell commands
- **Build Commands:** Use `ddev exec npm run build` to build JavaScript
- **Development Mode:** Use `ddev exec npm run dev` for watch mode with source maps
- **Browser Console:** Always check browser console for JavaScript errors
- **Drupal Logs:** Use `ddev drush log:watch` to check backend logs
- **Clear Cache:** After changes to libraries.yml or routing, run `ddev drush cr`
- **WaaP Documentation:** https://docs.wallet.human.tech/
- **Vite Documentation:** https://vitejs.dev/guide/build.html#library-mode
- **Drupal JS API:** https://www.drupal.org/docs/core-modules-and-themes/javascript-api/jquery-in-drupal

**WaaP-Specific Reminders:**
- ALWAYS check for existing session before showing login modal (auto-connect)
- Check the return value of `window.waap.login()` for login type
- WaaP-only mode (email/social) doesn't need WalletConnect project ID
- Handle all EIP-1193 events (connect, disconnect, accountsChanged, chainChanged)
- Use `personal_sign` for EIP-191 compatible signatures

**Security Reminders:**
- NEVER store nonces in localStorage (use session storage or memory only)
- ALWAYS validate addresses on frontend before sending to backend
- NEVER expose private keys or sensitive data in JavaScript
- ALWAYS use HTTPS for production authentication
- CLEAR sensitive data from memory after authentication

**Drupal Patterns:**
- Use `Drupal.behaviors` for initialization
- Use `drupalSettings` for configuration
- Use `hook_theme()` for template registration
- Use libraries.yml for asset management
- Follow PSR-4 for file organization
- Use proper namespaces and DocBlocks

---

*Last updated: 2025-01-12*
