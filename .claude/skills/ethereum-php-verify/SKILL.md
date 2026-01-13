---
name: ethereum-php-verify
description: Implement Ethereum signature verification in PHP. Use when user asks to verify wallet signatures, implement SIWE/EIP-4361, validate Ethereum addresses, or add wallet authentication to PHP/Drupal projects. Trigger phrases include "verify signature", "SIWE", "EIP-4361", "EIP-191", "wallet authentication", "ethereum signature", "personal_sign", or "validate wallet address".
---

# Ethereum Signature Verification in PHP

Implement EIP-191 (personal_sign) and EIP-4361 (SIWE) signature verification in PHP using `kornrunner/keccak` and `simplito/elliptic-php`. Use this when common libraries like `iltumio/siwe-php` have dependency conflicts.

## Prerequisites

Install required packages:

```bash
composer require kornrunner/keccak
composer require simplito/elliptic-php
```

## Quick Reference

### 1. Validate Ethereum Address (with EIP-55 checksum)

```php
use kornrunner\Keccak;

function validateAddress(string $address): bool {
    if (!str_starts_with($address, '0x') || strlen($address) !== 42) {
        return false;
    }
    $hexPart = substr($address, 2);
    if (!ctype_xdigit($hexPart)) return false;

    // All upper or lower is valid
    if (strtoupper($hexPart) === $hexPart || strtolower($hexPart) === $hexPart) {
        return true;
    }

    // Validate EIP-55 checksum
    $hash = Keccak::hash(strtolower($hexPart), 256);
    for ($i = 0; $i < 40; $i++) {
        $char = $hexPart[$i];
        $hashChar = hexdec($hash[$i]); // CRITICAL: Use hexdec(), not (int)
        if (ctype_digit($char)) continue;
        if ($hashChar >= 8 && strtolower($char) === $char) return false;
        if ($hashChar < 8 && strtoupper($char) === $char) return false;
    }
    return true;
}
```

### 2. Hash Message with EIP-191 Prefix

```php
use kornrunner\Keccak;

function hashMessage(string $message): string {
    $prefixed = "\x19Ethereum Signed Message:\n" . strlen($message) . $message;
    return Keccak::hash($prefixed, 256, true); // Binary output
}
```

### 3. Normalize Recovery ID from Signature

```php
function normalizeRecoveryId(int $v): int {
    if ($v >= 35) {
        // EIP-155: chainId * 2 + 35 + recoveryId
        return ($v - 35) % 2;
    } elseif ($v >= 27) {
        // Standard Ethereum: 27 + recoveryId
        return $v - 27;
    }
    // Already normalized (0-3)
    return $v;
}
```

### 4. Verify Signature

```php
use Elliptic\EC;

function verifySignature(string $message, string $signature, string $expectedAddress): bool {
    $sigBin = hex2bin(substr($signature, 2));
    $r = substr($sigBin, 0, 32);
    $s = substr($sigBin, 32, 32);
    $v = ord(substr($sigBin, 64, 1));

    $recoveryId = normalizeRecoveryId($v);
    $hash = hashMessage($message);

    // CRITICAL: Convert to hex for elliptic-php
    $ec = new EC('secp256k1');
    $pubKey = $ec->recoverPubKey(
        bin2hex($hash),
        ['r' => bin2hex($r), 's' => bin2hex($s)],
        $recoveryId
    );

    if ($pubKey === null) return false;

    $recoveredAddress = pubKeyToAddress($pubKey);
    return strtolower($recoveredAddress) === strtolower($expectedAddress);
}

function pubKeyToAddress($pubKey): string {
    $pubKeyHex = $pubKey->encode('hex');
    $pubKeyBin = hex2bin(substr($pubKeyHex, 2)); // Remove 0x04 prefix
    $hash = Keccak::hash($pubKeyBin, 256, true);
    return '0x' . bin2hex(substr($hash, -20));
}
```

### 5. Parse SIWE Message (EIP-4361)

```php
function parseSiweMessage(string $message): ?array {
    $fields = [];
    $lines = explode("\n", $message);

    if (count($lines) < 3) return null;

    // Header: "<domain> wants you to sign in..."
    if (!preg_match('/^(.+?) wants you to sign in with your Ethereum account:$/', $lines[0], $m)) {
        return null;
    }
    $fields['domain'] = trim($m[1]);

    // Address from line 2
    $fields['address'] = trim($lines[1]);
    if (!validateAddress($fields['address'])) return null;

    // Statement (between address and first field)
    $statementEnd = 2;
    for ($i = 2; $i < count($lines); $i++) {
        if (preg_match('/^[A-Za-z\s]+:\s*.+$/', trim($lines[$i]))) break;
        $statementEnd = $i;
    }

    // Parse key-value fields
    for ($i = $statementEnd; $i < count($lines); $i++) {
        $line = trim($lines[$i]);
        if ($line === '' || str_starts_with($line, '- ')) continue;

        if (strpos($line, ':') !== false) {
            [$key, $value] = explode(':', $line, 2);
            $camelKey = lcfirst(str_replace(' ', '', ucwords(str_replace('-', ' ', strtolower(trim($key))))));
            $fields[$camelKey] = trim($value);
        }
    }

    $required = ['domain', 'address', 'uri', 'version', 'nonce', 'issuedAt'];
    foreach ($required as $f) {
        if (empty($fields[$f])) return null;
    }

    return $fields;
}
```

## Critical Gotchas

### 1. The hexdec() Bug
```php
// WRONG - Always 0 for hex characters a-f
$hashChar = (int) $addressHash[$i];

// CORRECT
$hashChar = hexdec($addressHash[$i]);
```

### 2. Binary vs Hex for elliptic-php
```php
// WRONG - Library expects hex strings
$pubKey = $ec->recoverPubKey($hash, ['r' => $rBin, 's' => $sBin], $recoveryId);

// CORRECT - Convert binary to hex first
$pubKey = $ec->recoverPubKey(
    bin2hex($hash),
    ['r' => bin2hex($r), 's' => bin2hex($s)],
    $recoveryId
);
```

### 3. Recovery ID Normalization
Different wallets produce different v values. Always normalize to 0-3 before use.

## Security Checklist

- [ ] Use cryptographically random nonces (32+ bytes)
- [ ] Store nonces with expiration (5 min recommended)
- [ ] Delete nonces after successful verification
- [ ] Validate SIWE timestamp fields (issuedAt, expirationTime)
- [ ] Verify message address matches claimed address
- [ ] Implement rate limiting on authentication endpoint

## Test Example

```php
// Valid checksummed address
assert(validateAddress('0xd8dA6BF26964aF9D7eEd9e03E53415D37aA96045') === true);

// Invalid - too short
assert(validateAddress('0x1234') === false);

// Invalid - bad hex
assert(validateAddress('0xGGGGGGGGGGGGGGGGGGGGGGGGGGGGGGGGGGGGGGGG') === false);
```

## References

- [EIP-191: Signed Data Standard](https://eips.ethereum.org/EIPS/eip-191)
- [EIP-4361: Sign-In with Ethereum](https://eips.ethereum.org/EIPS/eip-4361)
- [EIP-55: Mixed-case checksum](https://eips.ethereum.org/EIPS/eip-55)
- Phase 3 Backend Auth implementation in wallet_auth module
