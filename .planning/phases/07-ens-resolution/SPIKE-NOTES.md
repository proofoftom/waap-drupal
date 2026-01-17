# Phase 7 Spike Notes - ENS Resolution

**Date:** 2025-01-16
**Status:** Work stashed pending proper research/plan ceremony

---

## What Was Built (Now Stashed)

An HTTP-based ENS resolution service was implemented without the `web3p/web3.php` dependency. The code is preserved in git stash.

### Files Created

1. **`src/Service/EnsResolverInterface.php`** (45 lines)
   - `resolveName(string $ens_name): ?string` - Forward resolution (name → address)
   - `resolveAddress(string $address): ?string` - Reverse resolution (address → name)
   - `clearCache(string $identifier): void` - Cache invalidation

2. **`src/Service/EnsResolver.php`** (513 lines)
   - HTTP-based JSON-RPC calls using Guzzle (Drupal's `@http_client`)
   - Implements ENS namehash algorithm using `kornrunner/keccak`
   - RPC failover with multiple providers
   - Caching with configurable TTL
   - Forward verification after reverse lookup (security)
   - ABI encoding/decoding for contract calls

3. **`src/Service/RpcProviderManager.php`** (97 lines)
   - Manages RPC endpoint configuration
   - Default free public endpoints: LlamaRPC, PublicNode, Ankr, Cloudflare
   - URL validation

### Files Modified

1. **`wallet_auth.services.yml`** - Added services:
   ```yaml
   wallet_auth.rpc_provider_manager:
     class: Drupal\wallet_auth\Service\RpcProviderManager
     arguments: ['@config.factory']

   wallet_auth.ens_resolver:
     class: Drupal\wallet_auth\Service\EnsResolver
     arguments:
       - '@http_client'
       - '@wallet_auth.rpc_provider_manager'
       - '@cache.default'
       - '@logger.factory'
   ```

2. **`config/schema/wallet_auth.schema.yml`** - Added:
   - `enable_ens_resolution` (boolean)
   - `enable_reverse_ens_lookup` (boolean)
   - `ens_cache_ttl` (integer)
   - `ethereum_provider_url` (string)
   - `ethereum_fallback_urls` (sequence)

3. **`config/install/wallet_auth.settings.yml`** - Added defaults:
   - `enable_ens_resolution: true`
   - `enable_reverse_ens_lookup: true`
   - `ens_cache_ttl: 3600`
   - `ethereum_provider_url: ''`
   - `ethereum_fallback_urls: []`

---

## Key Technical Decisions Made

### 1. HTTP-based vs web3p/web3.php
**Decision:** Use raw HTTP JSON-RPC calls with Guzzle instead of web3p library.

**Rationale:** User noted web3p is outdated. Guzzle is already available in Drupal core, and ENS resolution only needs `eth_call` RPC method.

**Trade-offs:**
- ✅ No additional dependencies
- ✅ Simpler, more maintainable
- ❌ Manual ABI encoding/decoding
- ❌ No type safety from library

### 2. Function Selectors (Pre-computed)
```php
const RESOLVER_SELECTOR = '0x0178b8bf';  // resolver(bytes32)
const ADDR_SELECTOR = '0x3b3b57de';      // addr(bytes32)
const NAME_SELECTOR = '0x691f3431';      // name(bytes32)
```

### 3. ENS Registry Address
```php
const ENS_REGISTRY_ADDRESS = '0x00000000000C2E074eC69A0dFb2997BA6C7d2e1e';
```
This is the same on mainnet, Sepolia, and most L2s.

---

## Open Questions for Research Phase

1. **Do we need backend ENS resolution at all?**
   - The frontend (WaaP SDK / SIWE message) can provide ENS name
   - We could just store what frontend provides and trust it
   - Backend resolution is more trustless but adds complexity/latency

2. **When should ENS resolution happen?**
   - On every authentication?
   - On first link only?
   - Background job to refresh periodically?

3. **How should ENS names be stored?**
   - On WalletAddress entity (planned for Phase 8)
   - Cached in Drupal cache only?
   - Both?

4. **What about ENS names that change?**
   - Primary ENS can be updated by user
   - How do we detect/handle stale data?

5. **Error handling for RPC failures?**
   - Current implementation: returns NULL, logs error
   - Should auth fail if ENS resolution fails?
   - Should we queue for retry?

6. **Network considerations?**
   - ENS is mainnet-only (primarily)
   - What about L2 ENS (Optimism, Base)?
   - Config currently assumes mainnet RPC endpoints

---

## Code Quality Status

- ❌ PHPCS not run
- ❌ PHPStan not run
- ❌ No tests written

---

## To Restore This Work

```bash
# List stashes
git stash list

# View stash contents
git stash show -p stash@{0}

# Apply stash (keeps stash)
git stash apply stash@{0}

# Pop stash (removes stash)
git stash pop stash@{0}
```

---

## Recommendation

Run `/gsd:research-phase` to investigate:
1. Whether backend ENS resolution is needed vs trusting frontend
2. Review how siwe_login currently uses ENS (is it blocking auth?)
3. Consider simpler approach: just store ENS from SIWE message

The stashed code is a valid implementation if backend resolution is needed, but the research might reveal we can skip this complexity entirely.
