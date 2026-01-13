---
name: drupal-testing-companion
description: Expert guide for testing Drupal modules including PHPUnit setup, Kernel tests, Functional tests, crypto testing patterns, and coverage analysis. Use when creating tests for Drupal contrib modules, especially authentication, API, or Web3 modules.
---

# Drupal Testing Companion

Expert guidance for comprehensive testing of Drupal modules. This skill covers PHPUnit setup, Kernel vs Functional testing patterns, crypto operation testing, and achieving production-ready coverage.

## When to Use This Skill

Use this skill when you need to:
- Set up PHPUnit for a new Drupal module
- Write Kernel tests for services and database operations
- Write Functional tests for REST APIs, routes, and forms
- Test cryptographic operations (signatures, hashing, SIWE)
- Generate and analyze code coverage reports
- Test authentication flows and user management
- Test blocks, forms, and admin interfaces

## Quick Reference

### Test Type Decision Tree

| Scenario | Test Type | Base Class |
|----------|-----------|------------|
| Service logic, DB operations | Kernel | `KernelTestBase` |
| REST API endpoints | Functional | `BrowserTestBase` |
| Forms, admin pages | Functional | `BrowserTestBase` |
| Blocks rendering | Functional | `BrowserTestBase` |
| Crypto verification | Kernel | `KernelTestBase` |

## Part 1: PHPUnit Setup

### Initial Configuration

```bash
# Copy PHPUnit config from Drupal core
cp web/core/phpunit.xml.dist phpunit.xml
```

Key edits needed in `phpunit.xml`:

```xml
<!-- Set bootstrap to Drupal core -->
<phpunit bootstrap="web/core/tests/bootstrap.php">

<!-- Configure test database (SQLite for speed) -->
<env name="SIMPLETEST_BASE_URL" value="http://127.0.0.1:8888"/>
<env name="SIMPLETEST_DB" value="sqlite://localhost/sites/default/files/.sqlite"/>

<!-- Point to your module's tests -->
<testsuites>
    <testsuite name="wallet_auth">
        <directory>web/modules/custom/wallet_auth/tests</directory>
    </testsuite>
</testsuites>
```

### Directory Structure

```
web/modules/custom/your_module/
├── tests/
│   ├── Kernel/
│   │   ├── YourServiceTest.php
│   │   └── .gitkeep
│   └── Functional/
│       ├── YourApiTest.php
│       └── .gitkeep
```

### Verify Setup

```bash
# List test suites
./vendor/bin/phpunit -c phpunit.xml --list-testsuites

# Run all tests
./vendor/bin/phpunit -c phpunit.xml web/modules/custom/your_module/tests
```

## Part 2: Kernel Tests

### Purpose
Test service logic, database operations, and crypto verification in isolation. No browser, no HTTP layer.

### Base Template

```php
<?php

declare(strict_types=1);

namespace Drupal\Tests\your_module\Kernel;

use Drupal\KernelTests\KernelTestBase;

/**
 * Tests YourService.
 *
 * @coversDefaultClass \Drupal\your_module\Service\YourService
 * @group your_module
 */
class YourServiceTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'system',
    'user',
    'your_module',
    // Add other required modules
  ];

  /**
   * The service being tested.
   *
   * @var \Drupal\your_module\Service\YourService
   */
  protected $yourService;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Install entity schemas if needed
    $this->installEntitySchema('user');

    // Install database tables if needed
    $this->installSchema('your_module', ['your_table_name']);

    // Get the service from container
    $this->yourService = $this->container->get('your_module.your_service');
  }

  /**
   * Tests basic functionality.
   */
  public function testBasicFunctionality(): void {
    $result = $this->yourService->doSomething();
    $this->assertEquals('expected', $result);
  }

}
```

### Common Kernel Test Patterns

#### Database Operations

```php
public function testDatabaseInsert(): void {
  // Use Connection service
  $database = $this->container->get('database');

  $database->insert('your_table')
    ->fields(['field1' => 'value1'])
    ->execute();

  // Verify insertion
  $count = $database->select('your_table', 't')
    ->countQuery()
    ->execute()
    ->fetchField();

  $this->assertEquals(1, $count);
}
```

#### Service Dependencies

```php
public function testServiceInteraction(): void {
  $mockService = $this->createMock(SomeInterface::class);
  $mockService->method('someMethod')->willReturn('mocked');

  // Inject mock into container (advanced - requires container modification)
  // Or test the service with its real dependencies
  $result = $this->yourService->methodThatCallsDependency();
  $this->assertEquals('expected', $result);
}
```

## Part 3: Functional Tests

### Purpose
Test full HTTP requests, routes, permissions, and form interactions. Uses a simulated browser.

### Base Template

```php
<?php

declare(strict_types=1);

namespace Drupal\Tests\your_module\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Tests your REST API.
 *
 * @coversDefaultClass \Drupal\your_module\Controller\YourController
 * @group your_module
 */
class YourApiTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'system',
    'user',
    'your_module',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Rebuild routes to ensure your module's routes are registered
    \Drupal::service('router.builder')->rebuild();
  }

  /**
   * Tests route exists.
   */
  public function testRouteExists(): void {
    $url = \Drupal::service('router.route_provider')
      ->getRouteByName('your_module.route_name');
    $this->assertNotNull($url);
    $this->assertEquals('/your/path', $url->getPath());
  }

  /**
   * Tests route permission.
   */
  public function testRoutePermission(): void {
    $url = \Drupal::service('router.route_provider')
      ->getRouteByName('your_module.route_name');
    $requirement = $url->getRequirement('_permission');
    $this->assertEquals('your permission', $requirement);
  }

}
```

### REST API Testing

```php
public function testAuthenticationEndpoint(): void {
  $user = $this->createUser([], 'test_user');
  $this->drupalLogin($user);

  $payload = json_encode([
    'field1' => 'value1',
    'field2' => 'value2',
  ]);

  $response = $this->drupalPost(
    '/your-module/endpoint',
    'application/json',
    $payload
  );

  $this->assertSession()->statusCodeEquals(200);

  // Verify response body
  $data = json_decode($response, TRUE);
  $this->assertEquals('success', $data['status']);
}
```

### Form Testing

```php
public function testSettingsForm(): void {
  $admin_user = $this->createUser(['administer site configuration']);
  $this->drupalLogin($admin_user);

  // Access form
  $this->drupalGet('/admin/config/your-module/settings');
  $this->assertSession()->statusCodeEquals(200);

  // Submit form
  $this->submitForm([
    'your_field' => 'test_value',
  ], 'Save configuration');

  $this->assertSession()->pageTextContains('The configuration options have been saved');

  // Verify config saved
  $config = $this->config('your_module.settings');
  $this->assertEquals('test_value', $config->get('your_field'));
}
```

### Block Testing

```php
public function testBlockRender(): void {
  // Create and login a user
  $user = $this->createUser();
  $this->drupalLogin($user);

  // Place block
  $this->placeBlock('your_module_block', [
    'region' => 'content',
  ]);

  // Visit page
  $this->drupalGet('<front>');
  $this->assertSession()->statusCodeEquals(200);

  // Verify block content
  $this->assertSession()->pageTextContains('Your Block Content');
}
```

## Part 4: Testing Crypto Operations

### Testing Signature Verification

Use known test vectors from reputable sources (web3.js, eth-account):

```php
/**
 * Tests signature verification with known test vector.
 */
public function testVerifyValidSignature(): void {
  // Known test vector from web3.js
  $address = '0x71C7656EC7ab88b098defB751B7401B5f6d8976F';
  $message = 'Hello World!';
  $signature = '0x3a8122c8cfcf2dbcbf6c3567490a7c1d0816a4914e37a7500fa31d56c31bff32a56deb618d55ba0f94076bfc1bb2d9e8e56a1f9a0f950e6e41eb71c7cd1975a1c';

  $result = $this->walletVerification->verifySignature($message, $signature, $address);

  $this->assertTrue($result);
}

/**
 * Tests that invalid signatures are rejected.
 */
public function testVerifyInvalidSignature(): void {
  $address = '0x71C7656EC7ab88b098defB751B7401B5f6d8976F';
  $message = 'Hello World!';
  // Wrong signature
  $signature = '0x' . str_repeat('00', 65);

  $result = $this->walletVerification->verifySignature($message, $signature, $address);

  $this->assertFalse($result);
}
```

### Testing Time-Based Operations

```php
/**
 * Tests nonce expiration.
 */
public function testNonceExpiration(): void {
  $nonce = 'expired_nonce';
  $walletAddress = '0x1234567890123456789012345678901234567890';

  // Manually store an old nonce
  $this->tempStore->set($nonce, [
    'wallet_address' => $walletAddress,
    'created' => \time() - 301, // 301 seconds ago
  ]);

  // Verify it's expired
  $result = $this->walletVerification->verifyNonce($nonce, $walletAddress);
  $this->assertFalse($result, 'Expired nonce should be rejected');
}

/**
 * Tests clock skew tolerance.
 */
public function testFutureNonceTolerance(): void {
  $nonce = 'future_nonce';
  $walletAddress = '0x1234567890123456789012345678901234567890';

  // Store nonce slightly in future (within 30s tolerance)
  $this->tempStore->set($nonce, [
    'wallet_address' => $walletAddress,
    'created' => \time() + 20,
  ]);

  $result = $this->walletVerification->verifyNonce($nonce, $walletAddress);
  $this->assertTrue($result, 'Future nonce within tolerance should be accepted');
}
```

### Testing Address Validation

```php
/**
 * Tests valid Ethereum addresses.
 *
 * @dataProvider validAddressProvider
 */
public function testValidAddresses(string $address): void {
  $this->assertTrue($this->walletVerification->validateAddress($address));
}

public function validAddressProvider(): array {
  return [
    'checksummed' => ['0x71C7656EC7ab88b098defB751B7401B5f6d8976F'],
    'lowercase' => ['0x71c7656ec7ab88b098defb751b7401b5f6d8976f'],
    'uppercase' => ['0X71C7656EC7AB88B098DEFB751B7401B5F6D8976F'],
  ];
}

/**
 * Tests invalid Ethereum addresses.
 *
 * @dataProvider invalidAddressProvider
 */
public function testInvalidAddresses(string $address): void {
  $this->assertFalse($this->walletVerification->validateAddress($address));
}

public function invalidAddressProvider(): array {
  return [
    'no_prefix' => ['71C7656EC7ab88b098defB751B7401B5f6d8976F'],
    'too_short' => ['0x1234'],
    'too_long' => ['0x' . str_repeat('12', 43)],
    'bad_hex' => ['0xGGGGGGGGGGGGGGGGGGGGGGGGGGGGGGGGGGGGGGGG'],
  ];
}
```

## Part 5: User Management Testing

### Testing User Creation

```php
/**
 * Tests user creation from wallet.
 */
public function testCreateUserFromWallet(): void {
  $walletAddress = '0x1234567890123456789012345678901234567890';

  $user = $this->userManager->createUserFromWallet($walletAddress);

  $this->assertInstanceOf(UserInterface::class, $user);
  $this->assertTrue($user->isActive());
  $this->assertStringContainsString('wallet_auth_', $user->getAccountName());

  // Verify wallet link
  $linkedUser = $this->userManager->loadUserByWalletAddress($walletAddress);
  $this->assertEquals($user->id(), $linkedUser->id());
}

/**
 * Tests username collision handling.
 */
public function testUsernameCollision(): void {
  $walletAddress1 = '0x1234567890123456789012345678901234567890';
  $walletAddress2 = '0x1234567890123456789012345678901234567891'; // Different wallet, same first 8 chars

  $user1 = $this->userManager->createUserFromWallet($walletAddress1);
  $user2 = $this->userManager->createUserFromWallet($walletAddress2);

  // Should have different usernames
  $this->assertNotEquals($user1->getAccountName(), $user2->getAccountName());
  $this->assertStringEndsWith('_1', $user2->getAccountName());
}
```

## Part 6: Code Coverage

### Generate Coverage Report

```bash
# HTML coverage report
./vendor/bin/phpunit -c phpunit.xml --coverage-html coverage web/modules/custom/your_module/tests

# Console coverage summary
./vendor/bin/phpunit -c phpunit.xml --coverage-text web/modules/custom/your_module/tests
```

### Coverage Targets

| Component | Target Coverage |
|-----------|-----------------|
| Critical services | 80%+ |
| Controllers | 70%+ |
| Forms | 60%+ |
| Blocks | 60%+ |

### Interpreting Coverage

- **Green (>80%)**: Excellent coverage
- **Yellow (60-80%)**: Acceptable, add more tests for critical paths
- **Red (<60%)**: Insufficient, needs more tests

### What to Test First

1. Public methods (most important)
2. Conditional branches (if/else)
3. Exception handling
4. Edge cases (empty input, null, invalid data)

## Part 7: Running Tests

### Run All Tests

```bash
./vendor/bin/phpunit -c phpunit.xml web/modules/custom/your_module/tests
```

### Run Specific Test File

```bash
./vendor/bin/phpunit -c phpunit.xml web/modules/custom/your_module/tests/Kernel/YourServiceTest.php
```

### Run Specific Test Method

```bash
./vendor/bin/phpunit -c phpunit.xml --filter testMethodName web/modules/custom/your_module/tests/Kernel/YourServiceTest.php
```

### Run with Verbose Output

```bash
./vendor/bin/phpunit -c phpunit.xml --verbose web/modules/custom/your_module/tests
```

### Stop on First Failure

```bash
./vendor/bin/phpunit -c phpunit.xml --stop-on-failure web/modules/custom/your_module/tests
```

## Common Pitfalls

### 1. Forgetting to Install Schema/EntitySchema

```php
protected function setUp(): void {
  parent::setUp();

  // Don't forget these!
  $this->installEntitySchema('user');
  $this->installSchema('your_module', ['your_table']);
}
```

### 2. Not Rebuilding Routes in Functional Tests

```php
protected function setUp(): void {
  parent::setUp();

  // Routes won't exist without this
  \Drupal::service('router.builder')->rebuild();
}
```

### 3. Time-Dependent Tests

```php
// Bad: Flaky due to timing
$this->assertTrue($time > $created + 300);

// Good: Use mock time or tolerance
$this->assertTrue(($time - $created) >= 300, 'Nonce should expire after lifetime');
```

### 4. Hardcoded Database Prefixes

```php
// Bad: Assumes specific database
$this->assertSelect('SELECT * FROM default.users_field_data');

// Good: Use Drupal API
$query = $this->container->get('database')->select('users_field_data', 'u');
```

## Testing Checklist

Before considering a module "production ready":

- [ ] PHPUnit configured (phpunit.xml)
- [ ] Kernel tests for all services
- [ ] Functional tests for all REST endpoints
- [ ] Functional tests for all forms
- [ ] Functional tests for all blocks
- [ ] Tests for validation logic
- [ ] Tests for error handling
- [ ] Tests for edge cases
- [ ] Code coverage >80% for critical services
- [ ] All tests passing consistently
- [ ] Manual E2E testing completed

## Additional Resources

- [Drupal PHPUnit Documentation](https://www.drupal.org/docs/automated-testing/phpunit-in-drupal)
- [BrowserTestBase API](https://api.drupal.org/api/drupal/core%21tests%21Drupal%21Tests%21BrowserTestBase/class/BrowserTestBase)
- [KernelTestBase API](https://api.drupal.org/api/drupal/core%21tests%21Drupal%21KernelTests%21KernelTestBase/class/KernelTestBase)
