# Roadmap: Wallet as a Protocol — Drupal Login Module

**Project:** Simple "Login with Wallet" for Drupal 10
**Mode:** yolo | **Depth:** standard
**Created:** 2025-01-12

---

## Overview

This roadmap breaks down the implementation of a Drupal 10 contrib module for wallet-based authentication using Wallet as a Protocol. The module enables users to authenticate by connecting their wallet and signing a message — no passwords required.

---

## Phase 1: Foundation & Environment Setup ✅

**Goal:** Establish working Drupal 10 development environment with project structure

**Status:** Complete (2025-01-12)

**Deliverables:**
- ✅ Fresh Drupal 10 project via composer (10.6.2)
- ✅ DDEV configuration for local development
- ✅ Module scaffold with basic Drupal structure
- ✅ Development environment validated

**Commits:** 13 (db04bdd through e1e9e1e)

**Artifacts:**
- `.ddev/config.yaml` - DDEV configuration
- `phpstan.neon`, `phpcs.xml` - Quality tools
- `web/modules/custom/wallet_auth/` - Module directory

---

## Phase 2: Wallet as a Protocol Integration Research

**Goal:** Understand Wallet as a Protocol spec and SDK requirements

**Deliverables:**
- Clear understanding of WaaP authentication flow
- Documented integration approach
- NPM package strategy following safe_smart_accounts pattern

**Unknowns to research:**
- [research] Wallet as a Protocol specification details
- [research] WaaP SDK availability and usage
- [research] safe_smart_accounts NPM build pattern for Drupal
- [research] Message signing flow for wallet ownership verification

**Tasks:**
1. Study Wallet as a Protocol documentation
2. Analyze safe_smart_accounts module for NPM package pattern
3. Design JavaScript SDK integration approach
4. Document authentication flow (connect wallet → sign message → verify)
5. Map WaaP endpoints to Drupal services

---

## Phase 3: Backend Authentication System

**Goal:** Implement Drupal backend for wallet authentication

**Deliverables:**
- Wallet verification service
- User creation/linking logic
- Database schema for wallet-address mapping
- Authentication provider implementation

**Tasks:**
1. Create database schema for wallet_address to user mapping
2. Implement WalletVerification service
3. Create authentication provider plugin
4. Implement user account creation on first auth
5. Implement user login on subsequent auth
6. Add route for signature verification endpoint
7. Add proper permission and security controls

---

## Phase 4: Frontend Wallet Integration

**Goal:** Implement wallet connection and signing UI

**Deliverables:**
- NPM package build pipeline
- Wallet connection UI component
- Message signing integration
- Login button/block for Drupal

**Tasks:**
1. Set up NPM/build pipeline following safe_smart_accounts pattern
2. Install/configure Wallet as a Protocol SDK
3. Create wallet connection JavaScript
4. Implement message signing flow
5. Build Drupal block/plugin for login button
6. Attach JS library to Drupal pages
7. Handle wallet connection state

---

## Phase 5: Integration & Polish

**Goal:** Complete Drupal integration and refine UX

**Deliverables:**
- Configurable module settings
- Proper error handling and user feedback
- Drupal coding standards compliance
- Basic admin configuration

**Tasks:**
1. Create module configuration form
2. Add admin settings (network configuration, etc.)
3. Implement error handling for failed auth
4. Add user-facing messages for auth states
5. Ensure Drupal coding standards compliance
6. Add basic documentation (README, inline comments)
7. Test complete authentication flow

---

## Phase 6: Testing & Validation

**Goal:** Ensure production-ready quality

**Deliverables:**
- Working authentication flow end-to-end
- Tested on fresh Drupal install
- Security review completed
- Ready for contrib release

**Tasks:**
1. Test complete flow: connect → sign → login
2. Test account creation on first auth
3. Test existing user login
4. Security review (signature verification, XSS, etc.)
5. Code quality review
6. Documentation finalization
7. Prepare for Drupal.org contrib release

---

## Summary

**6 Phases** spanning from environment setup through production-ready release

**Critical path:** Phase 1 → 2 → 3 → 4 → 5 → 6

**Progress:** 1/6 phases complete (17%)

**Estimated complexity:** Medium — Leverages existing patterns (safe_smart_accounts) and clear protocol spec

---

*Last updated: 2025-01-12*
