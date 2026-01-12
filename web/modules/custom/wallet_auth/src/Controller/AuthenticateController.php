<?php

declare(strict_types=1);

namespace Drupal\wallet_auth\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\wallet_auth\Service\WalletVerification;
use Drupal\wallet_auth\Service\WalletUserManager;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Controller for wallet authentication REST endpoint.
 */
class AuthenticateController extends ControllerBase {

  /**
   * The wallet verification service.
   *
   * @var \Drupal\wallet_auth\Service\WalletVerification
   */
  protected WalletVerification $verification;

  /**
   * The wallet user manager service.
   *
   * @var \Drupal\wallet_auth\Service\WalletUserManager
   */
  protected WalletUserManager $userManager;

  /**
   * The logger channel.
   *
   * @var \Drupal\Core\Logger\LoggerChannelInterface
   */
  protected $logger;

  /**
   * Constructs an AuthenticateController.
   *
   * @param \Drupal\wallet_auth\Service\WalletVerification $verification
   *   The wallet verification service.
   * @param \Drupal\wallet_auth\Service\WalletUserManager $user_manager
   *   The wallet user manager service.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger_factory
   *   The logger channel factory.
   */
  public function __construct(
    WalletVerification $verification,
    WalletUserManager $user_manager,
    LoggerChannelFactoryInterface $logger_factory,
  ) {
    $this->verification = $verification;
    $this->userManager = $user_manager;
    $this->logger = $logger_factory->get('wallet_auth');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('wallet_auth.verification'),
      $container->get('wallet_auth.user_manager'),
      $container->get('logger.factory'),
    );
  }

  /**
   * Authenticate a user using wallet signature.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The HTTP request.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   JSON response with authentication result.
   */
  public function authenticate(Request $request): JsonResponse {
    try {
      // 1. Parse request
      $data = json_decode($request->getContent(), TRUE);
      $walletAddress = $data['wallet_address'] ?? '';
      $signature = $data['signature'] ?? '';
      $message = $data['message'] ?? '';
      $nonce = $data['nonce'] ?? '';

      // 2. Validate address format
      if (!$this->verification->validateAddress($walletAddress)) {
        $this->logger->warning('Authentication attempt with invalid wallet address format: @address', [
          '@address' => $walletAddress,
        ]);
        return new JsonResponse([
          'success' => FALSE,
          'error' => 'Invalid wallet address',
        ], 400);
      }

      // 3. Verify signature and SIWE message (includes nonce validation)
      if (!$this->verification->verifySignature($message, $signature, $walletAddress)) {
        $this->logger->warning('Authentication failed for wallet @wallet: invalid signature', [
          '@wallet' => $walletAddress,
        ]);
        return new JsonResponse([
          'success' => FALSE,
          'error' => 'Invalid signature',
        ], 401);
      }

      // 4. Delete nonce after successful verification
      $this->verification->deleteNonce($nonce);

      // 5. Load or create user
      $user = $this->userManager->loginOrCreateUser($walletAddress);

      // 6. Log user in
      user_login_finalize($user);

      $this->logger->info('User authenticated successfully via wallet @wallet', [
        '@wallet' => $walletAddress,
        'uid' => $user->id(),
      ]);

      // 7. Return success
      return new JsonResponse([
        'success' => TRUE,
        'uid' => $user->id(),
        'username' => $user->getAccountName(),
      ]);
    }
    catch (\Exception $e) {
      $this->logger->error('Authentication error: @message', [
        '@message' => $e->getMessage(),
      ]);
      return new JsonResponse([
        'success' => FALSE,
        'error' => 'Authentication failed',
      ], 500);
    }
  }

}
