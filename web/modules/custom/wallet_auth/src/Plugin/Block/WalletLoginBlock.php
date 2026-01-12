<?php

declare(strict_types=1);

namespace Drupal\wallet_auth\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;

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
