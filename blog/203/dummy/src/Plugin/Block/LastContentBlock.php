<?php

namespace Drupal\dummy\Plugin\Block;

use Drupal\Component\Utility\Html;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\node\NodeInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a last content. block.
 *
 * @Block(
 *   id = "dummy_last_content",
 *   category = @Translation("Custom"),
 *   deriver = "Drupal\dummy\Plugin\Derivative\LastContentBlockDeriver",
 * )
 */
class LastContentBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * The node storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $nodeStorage;

  /**
   * Constructs a new LastContentBlock instance.
   *
   * @param array $configuration
   *   The plugin configuration, i.e. an array with configuration values keyed
   *   by configuration option name. The special key 'context' may be used to
   *   initialize the defined contexts by setting it to an array of context
   *   values keyed by context names.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    EntityTypeManagerInterface $entity_type_manager
  ) {

    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->nodeStorage = $entity_type_manager->getStorage('node');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'limit' => 10,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $form['limit'] = [
      '#type' => 'number',
      '#title' => $this->t('The amount of links to show'),
      '#min' => 1,
      '#max' => 50,
      '#default_value' => $this->configuration['limit'],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    $this->configuration['limit'] = $form_state->getValue('limit');
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $node_type = $this->getDerivativeId();
    $node_ids = $this
      ->nodeStorage
      ->getQuery()
      ->condition('status', NodeInterface::PUBLISHED)
      ->condition('type', $node_type)
      ->range(0, $this->configuration['limit'])
      ->sort('created', 'DESC')
      ->execute();

    if (empty($node_ids)) {
      return;
    }

    $nodes = $this->nodeStorage->loadMultiple($node_ids);

    $modifier_class = Html::getClass('last-content-list--' . str_replace('_', '-', $node_type));
    $build['content'] = [
      '#type' => 'html_tag',
      '#tag' => 'ul',
      '#attributes' => [
        'class' => [
          'last-content-list',
          $modifier_class,
        ],
      ],
    ];

    foreach ($nodes as $node) {
      $link = [
        '#type' => 'link',
        '#title' => $node->label(),
        '#url' => $node->toUrl('canonical', ['absolute' => TRUE]),
        '#attributes' => ['class' => 'last-content-list__link'],
      ];

      $build['content'][] = [
        '#type' => 'html_tag',
        '#tag' => 'li',
        '#attributes' => ['class' => 'last-content-list__item'],
        '0' => $link,
      ];
    }

    return $build;
  }

}
