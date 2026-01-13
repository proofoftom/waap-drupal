# Ethereum Signature Verification in PHP

**Version:** 1.0
**Author:** Claude (from Phase 3 Backend Auth implementation)
**Use Case:** Verifying Ethereum wallet signatures in PHP when common SIWE libraries have dependency conflicts

---

## Overview

This skill teaches you how to implement EIP-191 (personal_sign) and EIP-4361 (SIWE) signature verification in PHP using `kornrunner/keccak` and `simplito/elliptic-php`. This approach is useful when libraries like `iltumio/siwe-php` have dependency conflicts with your project (e.g., react/promise version mismatches).

---

## Prerequisites

Install the required packages:

```bash
composer require kornrunner/keccak
composer require simplito/elliptic-php
```

---

## Core Concepts

### 1. EIP-191 Message Prefix

Ethereum signatures use a prefixed message format:

```
"\x19Ethereum Signed Message:\n" + message length + message
```

This prefix prevents signing of transactions (which are not prefixed).

### 2. Signature Components

An Ethereum signature is 65 bytes:
- `r`: 32 bytes - first half of signature
- `s`: 32 bytes - second half of signature
- `v`: 1 byte - recovery ID (but encoding varies!)

### 3. Recovery ID Normalization

Different wallets encode `v` differently:

| Format | v Range | Recovery ID Calculation |
|--------|---------|------------------------|
| Standard Ethereum | 27-28 | `recovery_id = v - 27` |
| EIP-155 (chainId) | 35+ | `recovery_id = (v - 35) % 2` |
| Direct (some SDKs) | 0-3 | `recovery_id = v` (already normalized) |

**Critical:** Always normalize v before using elliptic-php!

---

## Implementation

### Step 1: Verify Address Format and Checksum

```php
use kornrunner\Keccak;

function validateAddress(string $address): bool {
    // Check basic format: 0x prefix, 42 characters, hex chars
    if (!str_starts_with($address, '0x') || strlen($address) !== 42) {
        return false;
    }

    $hexPart = substr($address, 2);
    if (!ctype_xdigit($hexPart)) {
        return false;
    }

    // All uppercase or lowercase is valid
    if (strtoupper($hexPart) === $hexPart || strtolower($hexPart) === $hexPart) {
        return true;
    }

    // Validate EIP-55 checksum
    return validateChecksum($address);
}

function validateChecksum(string $address): bool {
    $address = substr($address, 2);
    $hash = Keccak::hash(strtolower($address), 256);

    for ($i = 0; $i < 40; $i++) {
        $char = $address[$i];
        $hashChar = hexdec($hash[$i]); // CRITICAL: Use hexdec(), not (int) cast

        if (ctype_digit($char)) continue;

        if ($hashChar >= 8 && strtolower($char) === $char) return false;
        if ($hashChar < 8 && strtoupper($char) === $char) return false;
    }

    return true;
}
```

### Step 2: Hash the Message with EIP-191 Prefix

```php
use kornrunner\Keccak;

function hashMessage(string $message): string {
    $prefixedMessage = "\x19Ethereum Signed Message:\n" . strlen($message) . $message;
    return Keccak::hash($prefixedMessage, 256, true); // Returns binary
}
```

### Step 3: Parse and Normalize Signature

```php
function parseSignature(string $signature): array {
    // Remove 0x prefix and convert to binary
    $signatureBin = hex2bin(substr($signature, 2));

    if (strlen($signatureBin) !== 65) {
        throw new InvalidArgumentException('Signature must be 65 bytes');
    }

    $r = substr($signatureBin, 0, 32);
    $s = substr($signatureBin, 32, 32);
    $v = ord(substr($signatureBin, 64, 1));

    // Normalize v to recovery ID (0-3)
    $recoveryId = $v;

    if ($v >= 35) {
        // EIP-155 signature
        $recoveryId = ($v - 35) % 2;
    } elseif ($v >= 27) {
        // Standard Ethereum signature
        $recoveryId = $v - 27;
    }
    // Else: v is already recovery ID (0-3)

    if ($recoveryId < 0 || $recoveryId > 3) {
        throw new InvalidArgumentException('Invalid recovery ID');
    }

    return ['r' => $r, 's' => $s, 'recoveryId' => $recoveryId];
}
```

### Step 4: Recover Public Key and Verify

```php
use Elliptic\EC;

function verifySignature(string $message, string $signature, string $expectedAddress): bool {
    // Hash the message
    $hash = hashMessage($message);

    // Parse signature
    $sig = parseSignature($signature);

    // CRITICAL: Convert binary to hex for elliptic-php
    $rHex = bin2hex($sig['r']);
    $sHex = bin2hex($sig['s']);
    $hashHex = bin2hex($hash);

    // Recover public key
    $ec = new EC('secp256k1');
    $pubKey = $ec->recoverPubKey($hashHex, ['r' => $rHex, 's' => $sHex], $sig['recoveryId']);

    if ($pubKey === null) {
        return false;
    }

    // Derive address from public key
    $recoveredAddress = pubKeyToAddress($pubKey);

    // Compare addresses (case-insensitive)
    return strtolower($recoveredAddress) === strtolower($expectedAddress);
}

function pubKeyToAddress($pubKey): string {
    // Get uncompressed public key (remove first byte: 0x04)
    $pubKeyHex = $pubKey->encode('hex');
    $pubKeyBin = hex2bin(substr($pubKeyHex, 2));

    // Hash with Keccak-256
    $hash = Keccak::hash($pubKeyBin, 256, true);

    // Take last 20 bytes and add 0x prefix
    return '0x' . bin2hex(substr($hash, -20));
}
```

---

## SIWE Message Parsing (EIP-4361)

For Sign-In with Ethereum messages, parse the structured format:

```php
function parseSiweMessage(string $message): ?array {
    $fields = [];
    $lines = explode("\n", $message);

    if (count($lines) < 3) return null;

    // Parse: "<domain> wants you to sign in with your Ethereum account:"
    if (!preg_match('/^(.+?) wants you to sign in with your Ethereum account:$/', $lines[0], $match)) {
        return null;
    }
    $fields['domain'] = trim($match[1]);

    // Parse address from second line
    $address = trim($lines[1]);
    if (!validateAddress($address)) return null;
    $fields['address'] = $address;

    // Find statement (between address and first field)
    $statementEnd = 2;
    $statementParts = [];
    for ($i = 2; $i < count($lines); $i++) {
        $line = trim($lines[$i]);
        if (preg_match('/^[A-Za-z\s]+:\s*.+$/', $line)) break;
        if ($line !== '') $statementParts[] = $line;
        $statementEnd = $i;
    }
    if (!empty($statementParts)) {
        $fields['statement'] = implode("\n", $statementParts);
    }

    // Parse key-value fields
    for ($i = $statementEnd; $i < count($lines); $i++) {
        $line = trim($lines[$i]);
        if ($line === '' || str_starts_with($line, '- ')) continue;

        if (strpos($line, ':') !== false) {
            [$key, $value] = explode(':', $line, 2);
            $camelKey = lcfirst(str_replace(' ', '', ucwords(str_replace('-', ' ', strtolower(trim($key)))));
            $fields[$camelKey] = trim($value);
        }
    }

    // Validate required fields
    $required = ['domain', 'address', 'uri', 'version', 'nonce', 'issuedAt'];
    foreach ($required as $field) {
        if (empty($fields[$field])) return null;
    }

    return $fields;
}
```

---

## Common Pitfalls

### 1. Using `(int)` Cast on Hex Characters

**Wrong:**
```php
$hashChar = (int) $addressHash[$i]; // Always 0 for 'a'-'f'
```

**Correct:**
```php
$hashChar = hexdec($addressHash[$i]); // Proper value 0-15
```

### 2. Passing Binary to elliptic-php

**Wrong:**
```php
$pubKey = $ec->recoverPubKey($hash, ['r' => $rBin, 's' => $sBin], $recoveryId);
```

**Correct:**
```php
$hashHex = bin2hex($hash);
$rHex = bin2hex($r);
$sHex = bin2hex($s);
$pubKey = $ec->recoverPubKey($hashHex, ['r' => $rHex, 's' => $sHex], $recoveryId);
```

### 3. Not Normalizing Recovery ID

Different wallets produce different v values. Always normalize to 0-3 range before using.

---

## Security Considerations

1. **Nonce Management**: Use cryptographically random nonces (32+ bytes), store with expiration (5 min), delete after use
2. **Timestamp Validation**: Check `issuedAt` isn't in future (allow 30s clock skew), check `expirationTime` hasn't passed
3. **Address Matching**: Verify SIWE message address matches claimed address
4. **Replay Prevention**: Never reuse nonces, implement rate limiting

---

## Testing

Test with known valid signatures:

```php
// Test address validation
assert(validateAddress('0xd8dA6BF26964aF9D7eEd9e03E53415D37aA96045') === true);
assert(validateAddress('0xinvalid') === false);

// Test checksum validation
assert(validateAddress('0x8ba1f109551bD432803012645Ac136ddd64DBA72') === true);
```

---

## References

- [EIP-191: Signed Data Standard](https://eips.ethereum.org/EIPS/eip-191)
- [EIP-4361: Sign-In with Ethereum](https://eips.ethereum.org/EIPS/eip-4361)
- [EIP-55: Mixed-case checksum](https://eips.ethereum.org/EIPS/eip-55)
- [kornrunner/keccak](https://github.com/kornrunner/php-keccak)
- [simplito/elliptic-php](https://github.com/simplito/elliptic-php)
