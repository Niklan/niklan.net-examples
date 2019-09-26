<?php

namespace Drupal\dummy\Plugin\EntityReferenceSelection;

use Drupal\Component\Utility\Html;
use Drupal\Core\Entity\Plugin\EntityReferenceSelection\DefaultSelection;

/**
 * Provides autocomplete selection for commerce order item with SKU support.
 *
 * @EntityReferenceSelection(
 *   id = "dummy:commerce_product_variation",
 *   label = @Translation("Order Item selection with SKU"),
 *   entity_types = {"commerce_product_variation"},
 *   group = "default",
 *   weight = 5
 * )
 */
class ProductVariationWithSkuSelection extends DefaultSelection {

  /**
   * {@inheritdoc}
   */
  public function getReferenceableEntities($match = NULL, $match_operator = 'CONTAINS', $limit = 0) {
    $target_type = $this->getConfiguration()['target_type'];

    // Pass 'NULL' for match to pass title build.
    $query = $this->buildEntityQuery(NULL, $match_operator);
    // Define our special condition, which will looking in 'title' and 'sku'.
    $or = $query->orConditionGroup();
    $or->condition('title', $match, $match_operator);
    $or->condition('sku', $match, $match_operator);
    $query->condition($or);

    if ($limit > 0) {
      $query->range(0, $limit);
    }

    $result = $query->execute();

    if (empty($result)) {
      return [];
    }

    $options = [];
    /** @var \Drupal\commerce_product\Entity\ProductVariationInterface[] $entities */
    $entities = $this->entityTypeManager->getStorage($target_type)->loadMultiple($result);
    foreach ($entities as $entity_id => $entity) {
      $bundle = $entity->bundle();
      $label = $this->entityRepository->getTranslationFromContext($entity)->label();
      $sku = $entity->getSku();
      // Pass SKU in label as well.
      $options[$bundle][$entity_id] = Html::escape("({$sku}) {$label}");
    }

    return $options;
  }

}
