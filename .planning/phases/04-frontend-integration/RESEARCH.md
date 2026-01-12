# Research: Phase 4 - Frontend Wallet Integration

**Phase:** 4 - Frontend Wallet Integration
**Project:** Wallet as a Protocol - Drupal Login Module
**Date:** 2025-01-12
**Status:** Complete

---

## Executive Summary

Phase 4 requires implementing frontend wallet connection and signing functionality for Drupal. This research covers:

1. **Wallet as a Protocol (WaaP) SDK** - Official SDK for wallet connection and signing
2. **NPM/Build Pipeline** - Modern JavaScript tooling for Drupal modules
3. **EIP-1193 Wallet Events** - Standard wallet event handling patterns
4. **Drupal Asset Management** - libraries.yml and attachment patterns

**Key Finding:** The WaaP SDK provides a turnkey solution with EIP-1193 compliance, personal_sign support, and event handling. For build tooling, **Vite with library mode** is the modern standard for Drupal JavaScript libraries in 2024.

---

## 1. Wallet as a Protocol (WaaP) SDK

### Official Package
```bash
npm i @human.tech/waap-sdk
```

### API Overview

The WaaP SDK provides a simplified interface built on top of EIP-1193 standards:

**Initialization:**
```javascript
import { initWaaP } from "@human.tech/waap-sdk";

// Sets window.waap with all necessary methods
initWaaP({
  config: {
    allowedSocials: ['google', 'twitter', 'discord', 'github'],
    authenticationMethods: ['email', 'social'],
    styles: { darkMode: true }
  }
});
```

**Login Modal:**
```javascript
await window.waap.login()
```

**Message Signing (EIP-191):**
```javascript
const message = 'Hello World!';
const signature = await window.waap.request({
  method: "personal_sign",
  params: [message, address],
});
```

**Event Listeners (EIP-1193):**
```javascript
// Connection status changes
window.waap.on("connect", () => {
  console.log("Connected to WaaP");
});

// Account changes
window.waap.on("accountsChanged", (accounts) => {
  if (accounts.length === 0) {
    console.log("Wallet disconnected");
    setConnectedAccount(null);
  } else {
    console.log("Account changed to:", accounts[0]);
    setConnectedAccount(accounts[0]);
  }
});

// Chain changes
window.waap.on("chainChanged", (chainId) => {
  console.log("Chain changed to:", chainId);
  setCurrentChain(chainId);
});

// Disconnect events
window.waap.on("disconnect", (error) => {
  console.log("Wallet disconnected:", error);
  setConnectedAccount(null);
});
```

### Compatibility with Backend

**Perfect Match:** The WaaP SDK's `personal_sign` method aligns exactly with the backend EIP-191 verification already implemented in `WalletVerification.php`.

---

## 1.1 WaaP-Specific Integration Patterns

**Critical patterns from Phase 2 research that differ from generic EIP-1193 wallets:**

### Auto-Connect (Session Persistence)

WaaP automatically handles session persistence. **Don't always show the login modal.**

**❌ Wrong Pattern:**
```javascript
// Always shows login modal on page load
await window.waap.login()
```

**✅ Correct Pattern:**
```javascript
// Check for existing session first
const accounts = await window.waap.request({ method: 'eth_requestAccounts' })
if (accounts.length === 0) {
  // No existing session - show login button
  showLoginButton()
} else {
  // User already logged in - proceed with authentication
  const address = accounts[0]
  authenticateUser(address)
}
```

**Why:** WaaP persists sessions across page loads. Always calling `login()` creates poor UX.

### Login Type Handling

`window.waap.login()` returns the authentication method chosen by the user:

| Return Value | Auth Method | Implications |
|--------------|-------------|--------------|
| `'waap'` | WaaP (email/phone/social) | Full WaaP experience |
| `'injected'` | Browser wallet (MetaMask, etc.) | Standard EIP-1193 wallet |
| `'walletconnect'` | WalletConnect mobile wallet | Mobile-focused |
| `null` | User cancelled | Handle gracefully |

**Example:**
```javascript
const loginType = await window.waap.login()

switch (loginType) {
  case 'waap':
    console.log('User chose WaaP auth (email/social)')
    break
  case 'injected':
    console.log('User chose injected wallet (MetaMask, etc.)')
    break
  case 'walletconnect':
    console.log('User chose WalletConnect')
    break
  case null:
    console.log('User cancelled login')
    return
}
```

**Important:** All login types result in an EIP-1193 compliant provider, so subsequent code works identically.

### Authentication Methods Configuration

```javascript
initWaaP({
  config: {
    // Enable different auth methods
    authenticationMethods: ['email', 'phone', 'social', 'wallet'],
    allowedSocials: ['google', 'twitter', 'discord', 'github'],
  },
  // ⚠️ Only required if 'wallet' is enabled
  walletConnectProjectId: process.env.WALLETCONNECT_PROJECT_ID
})
```

### WalletConnect Project ID

**When You Need It:**
- Only required if you enable `'wallet'` in `authenticationMethods`
- Allows users to connect external wallets (MetaMask, Rainbow, etc.) via WalletConnect
- Get your project ID at: https://cloud.walletconnect.com/

**When You Don't Need It:**
- If only using WaaP's built-in email/phone/social auth
- WaaP works out-of-box without WalletConnect for these methods

### External Wallets Support Decision

**Option A: WaaP-Only (Simpler)**
```javascript
authenticationMethods: ['email', 'social']
```
- No WalletConnect project ID needed
- Simpler integration
- Users get WaaP's unified experience

**Option B: WaaP + External Wallets (Flexible)**
```javascript
authenticationMethods: ['email', 'social', 'wallet']
walletConnectProjectId: '<YOUR_PROJECT_ID>'
```
- Requires WalletConnect setup
- Users can choose MetaMask/Rainbow/etc.
- More complex, but familiar to Web3 users

**Recommendation for Drupal Module:** Start with WaaP-only (Option A). Can add external wallet support later if demand exists.

---

## 2. NPM/Build Pipeline for Drupal Modules

### ADR-005 Update: Vite Replaces Webpack

**Phase 2 Decision (ADR-005):** Webpack 5 with `drupal-libraries-webpack-plugin`

**Rationale for Change to Vite:**

| Factor | Webpack (ADR-005) | Vite (2024 Update) |
|--------|-------------------|---------------------|
| **Development Speed** | Slow cold starts, 3-5s HMR | Instant HMR, sub-second cold starts |
| **Configuration** | Complex, verbose | Minimal, sensible defaults |
| **Drupal Integration** | `drupal-libraries-webpack-plugin` (last updated 2023) | `@ueberbit/vite-plugin-drupal` (92 versions, actively maintained) |
| **Bundle Size** | Larger dependencies | Smaller footprint |
| **Ecosystem Maturity (2024)** | Mature but declining | Dominant, rapidly growing |
| **Output** | UMD/ESM bundles | UMD/ESM bundles (equivalent) |

**Key Insight:** Both tools produce equivalent output (UMD/ESM bundles + libraries.yml generation). The difference is purely developer experience and build speed. Since ADR-005's primary rationale was "industry standard" and "Drupal integration," and Vite has surpassed Webpack on both counts in 2024, the change is justified.

**Consequences:**
- ✅ Faster development iteration
- ✅ Simpler configuration to maintain
- ✅ Equivalent production output
- ⚠️ Slightly different plugin ecosystem (both have Drupal plugins)

### Modern Standard: Vite with Library Mode

**Why Vite in 2024:**
- Dominant build tool in 2024 (replacing Webpack/Rollup)
- Lightning-fast HMR for development
- Library mode produces optimized bundles for Drupal
- Smaller dependency footprint than Webpack
- Strong plugin ecosystem for Drupal integration

### Recommended Package: @ueberbit/vite-plugin-drupal

```bash
pnpm i -D @ueberbit/vite-plugin-drupal
```

**Features:**
- Automatic `libraries.yml` generation
- Asset bundling with zero config
- Tailwind/PostCSS preconfigured
- HMR for Twig templates
- Auto-import of JS/TS files and Vue components

**Configuration:**
```javascript
// vite.config.ts
import { defineConfig } from 'vite'
import uebertool from '@ueberbit/vite-plugin-drupal'

export default defineConfig({
  plugins: [uebertool()],
})
```

### Alternative: Vite Library Mode (Manual)

For a simpler setup without the Drupal-specific plugin:

```javascript
// vite.config.js
import { defineConfig } from 'vite';

export default defineConfig({
  build: {
    lib: {
      entry: './src/wallet-auth.js',
      name: 'WalletAuth',
      fileName: (format) => `wallet-auth.${format}.js`,
      formats: ['es', 'umd']
    },
    rollupOptions: {
      // Externalize dependencies that should not be bundled
      external: ['@human.tech/waap-sdk'],
      output: {
        globals: {
          '@human.tech/waap-sdk': 'WaaP'
        }
      }
    }
  }
});
```

### Alternative Webpack Plugins (Legacy)

These exist but are **NOT recommended** for new projects in 2024:
- `drupal-libraries-webpack-plugin` - Last updated 2023
- `drupal-librarify-webpack-plugin` - 2 stars, inactive
- `webpack-drupal-plugin` - Last updated 2021

---

## 3. EIP-1193 Wallet Event Patterns

### Standard Events to Handle

All EIP-1193 compliant wallets emit these events:

| Event | Description | Required Handling |
|-------|-------------|-------------------|
| `connect` | Provider connected to chain | Update UI state |
| `disconnect` | Provider disconnected | Clear auth state, redirect |
| `accountsChanged` | User switched accounts | Re-authenticate or clear session |
| `chainChanged` | User switched networks | May need to reload page |

### Event Listener Pattern

```javascript
// Standard EIP-1193 event handling
provider.on('connect', (connectInfo) => {
  console.log('Connected:', connectInfo.chainId);
});

provider.on('disconnect', (error) => {
  console.log('Disconnected:', error);
  // Clear session, redirect to login
});

provider.on('accountsChanged', (accounts) => {
  if (accounts.length === 0) {
    // User disconnected wallet
    handleLogout();
  } else {
    // User switched accounts - may need re-auth
    handleAccountSwitch(accounts[0]);
  }
});

provider.on('chainChanged', (chainId) => {
  // Recommended: reload page on chain change
  window.location.reload();
});
```

### Common Pitfalls

**❌ DON'T:** Forget to handle `accountsChanged` with empty array
- This means user disconnected, but `disconnect` event may not fire

**❌ DON'T:** Ignore `chainChanged`
- Can cause stale provider state, requires page reload

**❌ DON'T:** Attach multiple listeners without cleanup
- Memory leaks in SPA contexts

**✅ DO:** Use cleanup functions
```javascript
const cleanup = () => {
  provider.removeListener('accountsChanged', handleAccountsChanged);
  provider.removeListener('chainChanged', handleChainChanged);
  // ...
};
```

---

## 4. Drupal Asset Attachment Patterns

### Defining Libraries (wallet_auth.libraries.yml)

```yaml
wallet_auth_connector:
  version: 1.0.0
  js:
    js/wallet-auth-connector.js: {}
  dependencies:
    - core/drupal
    - core/drupalSettings

wallet_auth_ui:
  version: 1.0.0
  js:
    js/wallet-auth-ui.js: {}
  css:
    component:
      css/wallet-auth.css: {}
  dependencies:
    - wallet_auth/wallet_auth_connector
```

### Attaching from Block Plugin

```php
public function build() {
  $build['content'] = [
    '#theme' => 'wallet_login_button',
    '#attached' => [
      'library' => [
        'wallet_auth/wallet_auth_ui',
      ],
      'drupalSettings' => [
        'walletAuth' => [
          'apiEndpoint' => '/wallet-auth/authenticate',
          'enabledNetworks' => [1, 5, 137], // Mainnet, Goerli, Polygon
        ],
      ],
    ],
  ];
  return $build;
}
```

### Attaching via Preprocess Function

```php
/**
 * Implements hook_preprocess_HOOK().
 */
function wallet_auth_preprocess_page(&$variables) {
  if (\Drupal::routeMatch()->getRouteName() === 'user.login') {
    $variables['#attached']['library'][] = 'wallet_auth/wallet_auth_ui';
  }
}
```

### Using Drupal.behaviors in JavaScript

```javascript
(function ($, Drupal, drupalSettings) {
  'use strict';

  Drupal.behaviors.walletAuth = {
    attach: function (context, settings) {
      // Initialize WaaP SDK
      if (typeof window.initWaaP !== 'undefined') {
        window.initWaaP({
          config: {
            // Configuration from drupalSettings
          }
        });
      }
    }
  };

})(jQuery, Drupal, drupalSettings);
```

---

## 5. Architecture Recommendations

### Recommended Stack

| Component | Recommendation | Rationale |
|-----------|---------------|-----------|
| **Build Tool** | Vite (library mode) | Modern standard, fast HMR, clean output |
| **Drupal Plugin** | `@ueberbit/vite-plugin-drupal` | Auto-generates libraries.yml, handles assets |
| **Wallet SDK** | `@human.tech/waap-sdk` | Official WaaP SDK, EIP-1193 compliant |
| **Drupal JS Pattern** | `Drupal.behaviors` | Standard Drupal JS initialization pattern |

### Project Structure

```
web/modules/custom/wallet_auth/
├── wallet_auth.libraries.yml        # Generated by Vite plugin
├── wallet_auth.module                # Drupal hooks
├── src/
│   ├── Plugin/
│   │   └── Block/
│   │       └── WalletLoginBlock.php  # Login button block
│   └── Controller/
│       └── LoginController.php       # Login page route
├── js/                               # Vite entrypoint
│   ├── wallet-auth-connector.js      # WaaP SDK wrapper
│   └── wallet-auth-ui.js             # UI logic
├── css/
│   └── wallet-auth.css               # Component styles
├── package.json                      # NPM config
├── vite.config.js                    # Vite configuration
└── dist/                             # Built assets (generated)
    ├── wallet-auth-connector.es.js
    ├── wallet-auth-connector.umd.js
    └── assets/
```

---

## 6. Common Pitfalls to Avoid

### WaaP-Specific Pitfalls

1. **Always Calling login() on Page Load**
   - **❌ Don't:** Call `window.waap.login()` immediately on every page load
   - **✅ Do:** Check for existing session with `eth_requestAccounts` first
   - **Why:** WaaP auto-handles sessions. Always showing the modal creates poor UX

2. **Ignoring Login Type**
   - **❌ Don't:** Assume user always chose WaaP auth
   - **✅ Do:** Check the return value of `window.waap.login()`
   - **Why:** User may have chosen injected wallet or WalletConnect - different UI expectations

3. **Adding WalletConnect Project ID Unnecessarily**
   - **❌ Don't:** Require WalletConnect project ID if only using email/social auth
   - **✅ Do:** Only add `walletConnectProjectId` if enabling `'wallet'` auth method
   - **Why:** Unnecessary configuration complexity for WaaP-only deployments

4. **Not Handling Auto-Connect in UI**
   - **❌ Don't:** Always show "Connect Wallet" button even when user is already connected
   - **✅ Do:** Hide login button if `eth_requestAccounts` returns accounts
   - **Why:** Confusing UX - user appears logged in but sees login button

### Build Pipeline Pitfalls

1. **Bundling WaaP SDK**
   - **❌ Don't:** Bundle the WaaP SDK into your bundle
   - **✅ Do:** Mark as external in Vite config, load via CDN or separate library

2. **Missing Source Maps**
   - **❌ Don't:** Disable source maps in production
   - **✅ Do:** Enable for debugging, disable in prod builds

3. **Wrong Output Format**
   - **❌ Don't:** Build only ESM modules (Drupal doesn't support)
   - **✅ Do:** Build UMD or IIFE for browser compatibility

### Drupal Integration Pitfalls

1. **Missing drupalSettings**
   - **❌ Don't:** Hardcode API endpoints in JS
   - **✅ Do:** Pass via `drupalSettings` for flexibility

2. **Wrong Dependency Order**
   - **❌ Don't:** Forget to declare dependencies in libraries.yml
   - **✅ Do:** Explicitly list all dependencies

3. **Attaching to Wrong Context**
   - **❌ Don't:** Attach wallet JS to every page globally
   - **✅ Do:** Attach only to login block/page

### Wallet Event Pitfalls

1. **Not Handling Empty accountsChanged**
   - **❌ Don't:** Assume accountsChanged always has an account
   - **✅ Do:** Handle `accounts.length === 0` as disconnect

2. **Memory Leaks**
   - **❌ Don't:** Add listeners without cleanup
   - **✅ Do:** Store cleanup functions, call on detach

---

## 7. What NOT to Hand-Roll

### Use Existing Solutions

| Problem | Use Instead |
|---------|-------------|
| Wallet connection UI | WaaP SDK's built-in modal |
| Message signing | WaaP SDK's `personal_sign` |
| Event handling | EIP-1193 standard events |
| Build config | `@ueberbit/vite-plugin-drupal` |
| Asset management | Drupal's libraries.yml system |

### Don't Implement

- ❌ Custom wallet connector (use WaaP SDK)
- ❌ Manual signature verification in JS (backend already does it)
- ❌ Custom event bus (use EIP-1193 events)
- ❌ Manual asset copying (use Vite)
- ❌ Custom nonce generation (backend handles this)

---

## 8. Integration Flow

### Complete Authentication Flow

```
1. User visits login page
   └─> Block renders with wallet_auth_ui library attached

2. JavaScript initializes
   └─> Drupal.behaviors.walletAuth.attach() runs
   └─> initWaaP() called with config from drupalSettings

3. Check for existing session (AUTO-CONNECT)
   └─> window.waap.request({ method: 'eth_requestAccounts' })
   └─> If accounts returned: Skip to step 5 (user already authenticated)
   └─> If empty: Show "Connect Wallet" button

4. User clicks "Connect Wallet" button
   └─> loginType = await window.waap.login()
   └─> User chooses auth method:
       ├─ 'waap': Email/phone/social via WaaP modal
       ├─ 'injected': MetaMask or other browser wallet
       ├─ 'walletconnect': Mobile wallet via WalletConnect
       └─ null: User cancelled (abort)

5. WaaP emits 'connect' event with account
   └─> Get address from provider
   └─> Fetch nonce from backend: GET /wallet-auth/nonce
   └─> Request signature: personal_sign(message, address)

6. Send signature to backend
   └─> POST /wallet-auth/authenticate
   └─> Backend verifies signature (EIP-191)
   └─> Creates/logs in Drupal user

7. Backend returns session/token
   └─> Update UI to show logged-in state
   └─> Store wallet address in session storage
```

### Auto-Connect Flow Diagram

```
┌─────────────────────────────────────────────────────────────────┐
│                     Page Load                                    │
└────────────────────────┬────────────────────────────────────────┘
                         │
                         ▼
              ┌──────────────────────┐
              │  initWaaP()          │
              └──────────────────────┘
                         │
                         ▼
              ┌──────────────────────┐
              │ eth_requestAccounts  │
              └──────────────────────┘
                         │
            ┌────────────┴────────────┐
            │                         │
      ▼                     ▼
┌─────────────────┐   ┌─────────────────┐
│ accounts.length │   │ accounts.length │
│      > 0        │   │     === 0       │
└─────────────────┘   └─────────────────┘
        │                     │
        ▼                     ▼
┌─────────────────┐   ┌─────────────────┐
│ Auto-connect!   │   │ Show login btn  │
│ Skip auth flow  │   │ Wait for click  │
└─────────────────┘   └─────────────────┘
```

---

## 9. Security Considerations

### Frontend Security

1. **Origin Validation**
   - Verify backend API endpoint matches expected origin
   - Use Drupal's CSRF token system for AJAX

2. **Nonce Storage**
   - Don't store nonces in localStorage (XSS vulnerable)
   - Use session storage or memory only

3. **Message Signing**
   - Always use `personal_sign` (EIP-191), not raw signing
   - Include timestamp in signed message

4. **Error Handling**
   - Never expose raw error messages to users
   - Sanitize all wallet addresses before display

---

## 10. Next Steps for Planning

With this research complete, planning Phase 4 should focus on:

1. **Vite Configuration** - Set up build pipeline with `@ueberbit/vite-plugin-drupal`
2. **WaaP SDK Integration** - Wrapper around WaaP for Drupal-specific patterns
3. **Block Plugin** - Login button block with library attachment
4. **Auto-Connect Logic** - Implement session check before showing login UI
5. **Login Type Handling** - Handle 'waap', 'injected', 'walletconnect', null appropriately
6. **Event Handling** - EIP-1193 event listeners with proper cleanup
7. **API Integration** - Fetch nonce, submit signature to `/wallet-auth/authenticate`
8. **UI States** - Loading, connected, signing, error states
9. **Auth Method Decision** - Choose WaaP-only vs WaaP + external wallets
10. **Testing** - Manual testing with WaaP test credentials

### Decision Point: External Wallets Support

Before planning, decide:

**Question:** Should the module support external wallets (MetaMask, Rainbow, etc.) via WalletConnect?

| Option | Pros | Cons |
|--------|------|------|
| **WaaP-Only** | Simpler integration, no WalletConnect setup, unified UX | Less familiar to Web3-native users |
| **WaaP + External** | Familiar to Web3 users, maximum flexibility | Requires WalletConnect setup, more complex |

**Recommendation:** Start with WaaP-only. Can add external wallet support in future phase if users request it.

---

## Sources

### Primary Research
- Phase 2 Research: `.planning/phases/02-protocol-integration/RESEARCH.md` - WaaP architecture, auto-connect patterns, login type handling
- Phase 3 Implementation: Backend EIP-191 signature verification in `WalletVerification.php`

### External Documentation
- Wallet as a Protocol SDK Docs: https://docs.wallet.human.tech/
- WaaP Quick Start: https://docs.wallet.human.tech/quick-start
- WaaP Methods Reference: https://docs.wallet.human.tech/guides/methods
- EIP-1193 Standard: https://eips.ethereum.org/EIPS/eip-1193
- Vite Documentation: https://vitejs.dev/guide/build.html#library-mode
- @ueberbit/vite-plugin-drupal: https://www.npmjs.com/package/@ueberbit/vite-plugin-drupal
- Drupal Asset Library API: https://www.drupal.org/docs/develop/theming-drupal/adding-assets-css-js-to-a-drupal-theme-via-librariesyml
- Ethers.js v6 Docs: https://docs.ethers.org/v6/
- MetaMask Provider API: https://docs.metamask.io/wallet/reference/provider-api/
- WalletConnect: https://cloud.walletconnect.com/

### Community Resources
- WaaP Examples: https://github.com/holonym-foundation/waap-examples
- Drupal Web3 Auth Module: https://www.drupal.org/project/web3_auth
- Drupal SIWE Login Module: https://www.drupal.org/project/siwe_login

---

*Research completed 2025-01-12*
*Updated with Phase 2 integration patterns: 2025-01-12*
