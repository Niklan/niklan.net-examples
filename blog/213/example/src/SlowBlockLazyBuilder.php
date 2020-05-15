<?php

namespace Drupal\example;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\node\NodeInterface;

/**
 * Provides lazy builder for slow block content.
 */
final class SlowBlockLazyBuilder {

  /**
   * The node storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $nodeStorage;

  /**
   * Constructs a new SlowBlockLazyBuilder object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->nodeStorage = $entity_type_manager->getStorage('node');
  }

  /**
   * Build content with noticeable delay.
   *
   * @return array
   *   The renderable array result for lazy builder.
   */
  public function build(): array {
    sleep(3);

    $node_ids = $this->nodeStorage->getQuery()
      ->condition('status', NodeInterface::PUBLISHED)
      ->condition('type', 'article')
      ->range(0, 20)
      ->addTag('example_random')
      ->execute();
    $nodes = $this->nodeStorage->loadMultiple($node_ids);

    return array_map(function (NodeInterface $node) {
      return [
        '#type' => 'container',
        'link' => $node->toLink()->toRenderable(),
      ];
    }, $nodes);
  }

}
