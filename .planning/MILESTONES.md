# Milestones

## v1.0 — Wallet as a Protocol Drupal Login Module ✅
**Status:** Complete (2025-01-12)
**Phases:** 1-6

Core wallet authentication module implementation. Users can authenticate to Drupal using cryptographic signatures from wallets (MetaMask, WalletConnect, social logins) instead of passwords.

**Deliverables:**
- WalletAddress entity for storing wallet-to-user mappings
- EIP-191 signature verification service
- REST API endpoints for nonce and authentication
- Frontend integration with WaaP SDK
- Admin configuration interface
- Comprehensive test suite (64 tests)

---

## v2.0 — Merge siwe_login into wallet_auth & Refactor safe_smart_accounts 🚧
**Status:** In Progress
**Phases:** 7-14

Consolidate wallet authentication into a single `wallet_auth` module by merging `siwe_login` functionality, then refactor `safe_smart_accounts` to use the `WalletAddress` entity instead of `field_ethereum_address`.

**Key Decisions:**
- Any linked wallet can authenticate user (social auth pattern)
- WalletAddress entity supports multiple wallets per user
- User-selectable display wallet for profile
- Default display: ENS name if any wallet has one, else first linked wallet
- ENS support migrated from siwe_login
- No migrations needed (neither module is live in production)
- Remove field_ethereum_address entirely (clean break from anti-pattern)

**Deliverables:**
- ENS resolution service in wallet_auth
- Extended WalletAddress entity with ENS name field
- WalletAddressResolver service (replaces UserSignerResolver pattern)
- Migrated email verification and username creation flows
- Refactored safe_smart_accounts to use wallet_auth services
- Removal of siwe_login module
