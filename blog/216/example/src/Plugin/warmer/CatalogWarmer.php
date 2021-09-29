<?php

declare(strict_types=1);

namespace Drupal\example\Plugin\warmer;

use Drupal\Core\Url;

/**
 * Provides warmer for catalog.
 *
 * @Warmer(
 *   id = "example_catalog",
 *   label = @Translation("Catalog"),
 *   description = @Translation("Warms all categories and some of their pages."),
 * )
 */
final class CatalogWarmer extends CatalogBasedWarmerBase {

  /**
   * {@inheritdoc}
   */
  protected function prepareUrls(): array {
    $category_ids = $this->loadCategoryIds();
    $urls = [];
    foreach ($category_ids as $category_id) {
      $urls[] = $this->buildCategoryUrl($category_id);
      $urls[] = $this->buildCategoryUrl($category_id, 0);

      $count = $this->countProductsInCategory($category_id);
      if ($count == 0) {
        continue;
      }

      $build_url_callback = function (int $page) use ($category_id, &$urls): void {
        $urls[] = $this->buildCategoryUrl($category_id, $page);
      };

      $this->prepareFirstPagesToWarm($count, $build_url_callback);
      $this->prepareLastPagesToWarm($count, $build_url_callback);
    }

    return $urls;
  }

  /**
   * Builds category URL.
   *
   * @param int $category_id
   *   The category ID.
   * @param int|null $page
   *   The query page value.
   *
   * @return string
   *   The category URL.
   */
  protected function buildCategoryUrl(int $category_id, ?int $page = NULL): string {
    $url = Url::fromRoute('entity.taxonomy_term.canonical', ['taxonomy_term' => $category_id]);
    if (isset($page)) {
      $url->setOption('query', [
        'page' => $page,
      ]);
    }
    return $url->setAbsolute()->toString();
  }

  /**
   * Executes callback to prepare first pages to warm.
   *
   * @param int $items_count
   *   The total item count.
   * @param callable $callback
   *   The callback.
   */
  protected function prepareFirstPagesToWarm(int $items_count, callable $callback): void {
    $pages_to_warm = $this->getPagesFromBeginning();
    if ($pages_to_warm <= 1) {
      return;
    }

    $pages_count = (int) \ceil($items_count / $this->getItemsPerPage());
    if ($pages_count == 1) {
      return;
    }
    if ($pages_count < $pages_to_warm) {
      $pages_to_warm = $pages_count;
    }

    // Subtract 1 because first page warmed separately.
    $pages_to_warm--;
    for ($page = 1; $page <= $pages_to_warm; $page++) {
      \call_user_func($callback, $page);
    }
  }

  /**
   * Executes callback to prepare last pages to warm.
   *
   * @param int $items_count
   *   The total item count.
   * @param callable $callback
   *   The callback.
   */
  protected function prepareLastPagesToWarm(int $items_count, callable $callback): void {
    $pages_to_warm = $this->getPagesFromEnd();
    if ($pages_to_warm == 0) {
      return;
    }

    $pages_count = (int) \ceil($items_count / $this->getItemsPerPage());
    // If total page count lesser or equal pages warmed from the beginning,
    // there is no reason to warm.
    if ($pages_count <= $this->getPagesFromBeginning()) {
      return;
    }

    $total_pages_to_warm = $this->getPagesFromBeginning() + $pages_to_warm;
    if ($total_pages_to_warm > $pages_count) {
      $pages_to_warm = $pages_count - $this->getPagesFromBeginning();
    }

    // Adjust page index for Drupal which counts pages from 0.
    $last_page_index = $pages_count - 1;
    for ($page = $last_page_index; $page > $last_page_index - $pages_to_warm; $page--) {
      \call_user_func($callback, $page);
    }
  }

}
