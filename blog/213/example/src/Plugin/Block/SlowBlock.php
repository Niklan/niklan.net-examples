<?php

namespace Drupal\example\Plugin\Block;

use Drupal\Core\Block\BlockBase;

/**
 * Provides a slow block.
 *
 * @Block(
 *   id = "example_slow_block",
 *   admin_label = @Translation("Slow block"),
 *   category = @Translation("Custom")
 * )
 */
final class SlowBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build(): array {
    // Rand argument to exclude from internal caching.
    $rand = rand(0, 1000);
    $build['content'] = [
      '#lazy_builder' => ['example.slow_block_lazy_builder:build', [$rand]],
      '#create_placeholder' => TRUE,
    ];
    return $build;
  }

}
