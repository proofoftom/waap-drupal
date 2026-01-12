# Wallet as a Protocol — Drupal Login Module

A Drupal 10 contrib module for wallet-based authentication using Wallet as a Protocol (https://docs.wallet.human.tech/).

## One-Liner

Simple "Login with Wallet" for Drupal — connect wallet, verify ownership, create/access account.

## Vision

Make wallet-based authentication a first-class citizen in Drupal, following the Wallet as a Protocol specification. Users authenticate by connecting their wallet and signing a simple message — no passwords, no traditional login forms.

## Requirements

### Validated

(None yet — ship to validate)

### Active

- [ ] Wallet connection — user connects wallet via browser extension
- [ ] Message signing — user signs a message to prove ownership
- [ ] Account creation — Drupal creates new user account on first successful auth
- [ ] Account login — existing user logs in via wallet on subsequent visits
- [ ] Drupal integration — follows Drupal coding standards, hook system, and patterns
- [ ] NPM package build — includes and builds Wallet as a Protocol SDK following safe_smart_accounts pattern
- [ ] DDEV setup — fresh Drupal 10 project with composer for development/testing

### Out of Scope

- [Advanced admin UI] — Drupal admins can configure and understand it easily, but no advanced admin panel in v1
- [SIWE full spec] — Simple wallet connect, not full EIP-4361/SIWE with domain, nonce, expiration
- [Account linking] — v1 is wallet-only login, no linking wallet to existing password accounts

## Key Decisions

| Decision | Rationale | Outcome |
|----------|-----------|---------|
| Simple wallet connect | Minimize friction, get something working | — Pending |
| Drupal integration focus | Module must feel native to Drupal admins | — Pending |
| Safe Smart Accounts pattern | Proven approach for NPM packages in Drupal modules | — Pending |

## Context

### References

- Wallet as a Protocol: https://docs.wallet.human.tech/
- Context7 docs: https://context7.com/websites/wallet_human_tech
- SIWE Login module (inspiration): https://git.drupalcode.org/project/siwe_login
- Safe Smart Accounts (NPM build pattern): https://git.drupalcode.org/project/safe_smart_accounts

### Development Environment

- Fresh Drupal 10 project via composer
- DDEV for local development
- Production-ready code quality (stable contrib module)

## Constraints

| Constraint | Details |
|------------|---------|
| Production-ready | Must be stable, secure, ready for contrib release |
| Drupal 10 | Target Drupal 10.x, PHP 8.1+ |
| Wallet as a Protocol spec | Must align with protocol specification |

---
*Last updated: 2025-01-12 after initialization*
