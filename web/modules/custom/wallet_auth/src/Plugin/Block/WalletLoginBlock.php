<?php

declare(strict_types=1);

namespace Drupal\wallet_auth\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
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
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected ConfigFactoryInterface $configFactory;

  /**
   * Constructs a WalletLoginBlock.
   *
   * @param array $configuration
   *   The plugin configuration.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    ConfigFactoryInterface $config_factory
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->configFactory = $config_factory;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('config.factory')
    );
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

    // Read configuration.
    $config = $this->configFactory->get('wallet_auth.settings');

    $build['#theme'] = 'wallet_login_button';
    $build['#attached'] = [
      'library' => [
        'wallet_auth/wallet_auth_ui',
      ],
      'drupalSettings' => [
        'walletAuth' => [
          'apiEndpoint' => '/wallet-auth',
          'network' => $config->get('network') ?? 'mainnet',
          'enableAutoConnect' => $config->get('enable_auto_connect') ?? TRUE,
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
