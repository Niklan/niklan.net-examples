<?php

namespace Drupal\example\EventSubscriber;

use Drupal\Component\Diff\Diff;
use Drupal\Component\Render\FormattableMarkup;
use Drupal\Core\Config\ConfigCrudEvent;
use Drupal\Core\Config\ConfigEvents;
use Drupal\Core\Diff\DiffFormatter;
use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\Core\Render\Markup;
use Drupal\Core\Render\Renderer;
use Drupal\Core\Serialization\Yaml;
use Drupal\Core\Session\AccountProxyInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Provides subscriber for configuration changes logging.
 */
class ConfigSaveSubscriber implements EventSubscriberInterface {

  /**
   * The logger channel.
   *
   * @var \Drupal\Core\Logger\LoggerChannelInterface
   */
  protected $logger;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;

  /**
   * The diff formatter.
   *
   * @var \Drupal\Core\Diff\DiffFormatter
   */
  protected $diffFormatter;

  /**
   * The renderer.
   *
   * @var \Drupal\Core\Render\Renderer
   */
  protected $renderer;

  /**
   * Constructs a new ConfigSaveSubscriber object.
   *
   * @param \Drupal\Core\Logger\LoggerChannelInterface $logger
   *   The channel logger.
   * @param \Drupal\Core\Session\AccountProxyInterface $current_user
   *   The current user.
   * @param \Drupal\Core\Diff\DiffFormatter $diff_formatter
   *   The diff formatter.
   * @param \Drupal\Core\Render\Renderer $renderer
   *   The renderer.
   */
  public function __construct(LoggerChannelInterface $logger, AccountProxyInterface $current_user, DiffFormatter $diff_formatter, Renderer $renderer) {
    $this->logger = $logger;
    $this->currentUser = $current_user;
    $this->diffFormatter = $diff_formatter;
    $this->renderer = $renderer;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    return [
      ConfigEvents::SAVE => 'onConfigSave',
    ];
  }

  /**
   * Reacts to config save and log changes if they made not by admin.
   *
   * @param \Drupal\Core\Config\ConfigCrudEvent $event
   *   The config event.
   */
  public function onConfigSave(ConfigCrudEvent $event) {
    $config = $event->getConfig();
    if ($config->isNew()) {
      return;
    }

    // Perform nothing if config changed by uid 1 (root user).
    if ($this->currentUser->id() == 1) {
      return;
    }

    $original_data = explode("\n", Yaml::encode($config->getOriginal()));
    $current_data = explode("\n", Yaml::encode($config->get()));
    $diff = new Diff($original_data, $current_data);

    $build['diff'] = [
      '#type' => 'table',
      '#header' => [
        ['data' => 'From', 'colspan' => '2'],
        ['data' => 'To', 'colspan' => '2'],
      ],
      '#rows' => $this->diffFormatter->format($diff),
    ];
    $diff_html = $this->renderer->renderPlain($build);

    $message = new FormattableMarkup('<p>The %username user has changed the configuration of %config_id.</p> @changes', [
      '%username' => $this->currentUser->getDisplayName(),
      '%config_id' => $config->getName(),
      '@changes' => Markup::create($diff_html),
    ]);
    $this->logger->notice($message);
  }

}
