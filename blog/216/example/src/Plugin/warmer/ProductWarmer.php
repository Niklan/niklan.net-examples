<?php

declare(strict_types=1);

namespace Drupal\example\Plugin\warmer;

use Drupal\Core\Url;

/**
 * Provides warmer for products.
 *
 * @Warmer(
 *   id = "example_product",
 *   label = @Translation("Products"),
 *   description = @Translation("Warms specific products based on categories and pagination."),
 * )
 */
final class ProductWarmer extends CatalogBasedWarmerBase {

  /**
   * {@inheritdoc}
   */
  protected function prepareUrls(): array {
    $product_ids = [];
    $category_ids = $this->loadCategoryIds();
    foreach ($category_ids as $category_id) {
      $count = $this->countProductsInCategory($category_id);
      if ($count == 0) {
        continue;
      }
      $page_count = (int) \ceil($count / $this->getItemsPerPage());
      $product_ids = \array_merge($product_ids, $this->prepareFirstProductIdsToWarm($page_count, $category_id));
      $product_ids = \array_merge($product_ids, $this->prepareLastProductIdsToWarm($count, $page_count, $category_id));
    }

    $urls = [];
    foreach (\array_unique($product_ids) as $product_id) {
      $urls[] = $this->buildProductUrl((int) $product_id);
    }

    return $urls;
  }

  /**
   * Looking for product IDs from the first pages.
   *
   * @param int $page_count
   *   The total pages in category.
   * @param int $category_id
   *   The category ID.
   *
   * @return array
   *   An array with product IDs.
   */
  protected function prepareFirstProductIdsToWarm(int $page_count, int $category_id): array {
    $first_pages_to_warm = $this->getPagesFromBeginning();
    if ($page_count < $first_pages_to_warm) {
      $first_pages_to_warm = $page_count;
    }
    $query = $this->getProductsQuery($category_id);
    $query->range(0, $first_pages_to_warm * $this->getItemsPerPage());
    return $query->execute();
  }

  /**
   * Looking for product IDs from the last pages.
   *
   * @param int $item_count
   *   The total items in category.
   * @param int $page_count
   *   The total pages in category.
   * @param int $category_id
   *   The category ID.
   *
   * @return array
   *   An array with product IDs.
   */
  protected function prepareLastProductIdsToWarm(int $item_count, int $page_count, int $category_id): array {
    $last_pages_to_warm = $this->getPagesFromEnd();
    if ($last_pages_to_warm == 0) {
      return [];
    }

    $first_pages_to_warm = $this->getPagesFromBeginning();
    $total_pages_to_warm = $first_pages_to_warm + $last_pages_to_warm;
    if ($total_pages_to_warm > $page_count) {
      $last_pages_to_warm = $page_count - $first_pages_to_warm;
    }

    // At this point value can also be negative. E.g. 3 first pages, 3 last,
    // and only 2 total pages.
    if ($last_pages_to_warm <= 0) {
      return [];
    }

    // First, count as if the pages are full.
    $items_to_warm = $last_pages_to_warm * $this->getItemsPerPage();
    // Then check, if last page isn't full.
    if (($leftover_items = $item_count % $this->getItemsPerPage()) > 0) {
      // If it isn't full, subtract missing amount from total items.
      $items_to_warm -= $this->getItemsPerPage() - $leftover_items;
    }

    $query = $this->getProductsQuery($category_id, TRUE);
    $query->range(0, $items_to_warm);
    return $query->execute();
  }

  /**
   * Builds absolute URL for product page.
   *
   * @param int $product_id
   *   The product ID.
   *
   * @return string
   *   The product page URL.
   */
  protected function buildProductUrl(int $product_id): string {
    $route_name = 'entity.' . $this->getProductEntityTypeId() . '.canonical';
    return Url::fromRoute($route_name, [$this->getProductEntityTypeId() => $product_id])
      ->setAbsolute()
      ->toString();
  }

}
