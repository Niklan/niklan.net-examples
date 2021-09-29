<?php

declare(strict_types=1);

namespace Drupal\example\Plugin\warmer;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Entity\ContentEntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\Query\QueryInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\SubformStateInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\taxonomy\TermStorageInterface;
use Drupal\warmer\Plugin\WarmerPluginBase;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Promise\Utils;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides abstract implementation for warmer plugins based on catalog warming.
 */
abstract class CatalogBasedWarmerBase extends WarmerPluginBase {

  /**
   * The vocabulary ID with categories.
   */
  protected const CATEGORY_VOCABULARY_ID = 'category';

  /**
   * The entity type used for products.
   */
  protected const PRODUCT_ENTITY_TYPE_ID = 'node';

  /**
   * An array with product bundles.
   */
  protected const PRODUCT_BUNDLES = ['product'];

  /**
   * The field name with category entity reference.
   */
  protected const CATEGORY_FIELD = 'field_category';

  /**
   * The field name to sort by.
   */
  protected const SORT_FIELD = 'created';

  /**
   * The default sort direction.
   */
  protected const SORT_DIRECTION = 'DESC';

  /**
   * The maximum amount of products listed on a single category page.
   */
  protected const ITEMS_PER_PAGE = 10;

  /**
   * The amount of concurrent requests during warming.
   */
  protected const CONCURRENT_REQUESTS = 10;

  /**
   * The entity type manager.
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * The product storage.
   */
  protected ?ContentEntityStorageInterface $productStorage;

  /**
   * The term storage.
   */
  protected ?TermStorageInterface $termStorage;

  /**
   * The HTTP client.
   */
  protected ClientInterface $httpClient;

  /**
   * The static cache.
   */
  protected CacheBackendInterface $cache;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): self {
    $instance = parent::create($container, $configuration, $plugin_id, $plugin_definition);
    $instance->entityTypeManager = $container->get('entity_type.manager');
    $instance->httpClient = $container->get('http_client');
    $instance->cache = $container->get('cache.static');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function loadMultiple(array $ids = []): array {
    return $ids;
  }

  /**
   * {@inheritdoc}
   */
  public function warmMultiple(array $items = []): int {
    $promises = [];
    $success = 0;

    foreach ($items as $key => $url) {
      // Fire async request.
      $promises[] = $this->httpClient
        ->requestAsync('GET', $url)
        ->then(static function (ResponseInterface $response) use (&$success): void {
          if ($response->getStatusCode() < 399) {
            $success++;
          }
        });
      // Wait for all fired requests if max number is reached.
      $item_keys = \array_keys($items);
      if ($key % $this->getConcurrentRequests() == 0 || $key == \end($item_keys)) {
        Utils::all($promises)->wait();
        $promises = [];
      }
    }

    return $success;
  }

  /**
   * Gets the amount of simultaneous requests during warming.
   *
   * @return int
   *   The amount of requests.
   */
  protected function getConcurrentRequests(): int {
    return $this::CONCURRENT_REQUESTS;
  }

  /**
   * {@inheritdoc}
   */
  public function buildIdsBatch($cursor): array {
    $cid = __METHOD__ . ':' . $this->getPluginId();
    if ($cache = $this->cache->get($cid)) {
      $urls = $cache->data;
    }
    else {
      $urls = $this->prepareUrls();
      $this->cache->set($cid, $urls);
    }
    $cursor_position = \is_null($cursor) ? -1 : \array_search($cursor, $urls);
    if ($cursor_position === FALSE) {
      return [];
    }
    return \array_slice($urls, $cursor_position + 1, (int) $this->getBatchSize());
  }

  /**
   * Prepares a list of URLs for warming.
   *
   * @return array
   *   An array with URLs to warm.
   */
  abstract protected function prepareUrls(): array;

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration(): array {
    return [
      'pages_from_beginning' => 3,
      'pages_from_end' => 1,
      'frequency' => 60 * 60,
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function addMoreConfigurationFormElements(array $form, SubformStateInterface $form_state): array {
    $form['pages_from_beginning'] = [
      '#type' => 'number',
      '#min' => 1,
      '#step' => 1,
      '#required' => TRUE,
      '#title' => new TranslatableMarkup('First pages to warm'),
      '#description' => new TranslatableMarkup('The number of pages to warm starting from a beginning, including first one.'),
      '#default_value' => $this->getPagesFromBeginning(),
    ];

    $form['pages_from_end'] = [
      '#type' => 'number',
      '#min' => 0,
      '#step' => 1,
      '#required' => TRUE,
      '#title' => new TranslatableMarkup('Last pages to warm'),
      '#description' => new TranslatableMarkup('The number of pages to warm starting from an end.'),
      '#default_value' => $this->getPagesFromEnd(),
    ];

    return $form;
  }

  /**
   * Gets number of pages needs to be warmed from beginning.
   *
   * @return int
   *   The amount of pages to warm from beginning.
   */
  public function getPagesFromBeginning(): int {
    return (int) $this->getConfiguration()['pages_from_beginning'];
  }

  /**
   * Gets number of pages needs to be warmed from the end.
   *
   * @return int
   *   The number of pages.
   */
  public function getPagesFromEnd(): int {
    return (int) $this->getConfiguration()['pages_from_end'];
  }

  /**
   * Gets items per page.
   *
   * @return int
   *   The amount of items per page.
   */
  public function getItemsPerPage(): int {
    return $this::ITEMS_PER_PAGE;
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state): void {
    $pages_from_beginning = $form_state->getValue('pages_from_beginning');
    if (!\is_numeric($pages_from_beginning) || $pages_from_beginning < 1) {
      $form_state->setError($form['pages_from_beginning'], new TranslatableMarkup('First pages to warm should be greater than or equal 1.'));
    }

    $pages_from_end = $form_state->getValue('pages_from_end');
    if (!\is_numeric($pages_from_end) || $pages_from_end < 0) {
      $form_state->setError($form['pages_from_end'], new TranslatableMarkup('Last pages to warm should be a positive number.'));
    }

    parent::validateConfigurationForm($form, $form_state);
  }

  /**
   * Loads active categories IDs.
   *
   * @return array
   *   An array with category IDs.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  protected function loadCategoryIds(): array {
    $ids = [];
    foreach ($this->loadCatalogTree() as $term) {
      if ($term->status != 1) {
        continue;
      }
      $ids[] = (int) $term->tid;
    }
    return $ids;
  }

  /**
   * Loads catalog tree.
   *
   * @return \stdClass[]
   *   An array with tree information.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  protected function loadCatalogTree(): array {
    return $this->getTermStorage()->loadTree($this->getCategoryVocabularyId());
  }

  /**
   * Gets term storage.
   *
   * @return \Drupal\taxonomy\TermStorageInterface
   *   The term storage.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  protected function getTermStorage(): TermStorageInterface {
    if (!isset($this->termStorage)) {
      $this->termStorage = $this->entityTypeManager->getStorage('taxonomy_term');
    }
    return $this->termStorage;
  }

  /**
   * Gets category vocabulary ID.
   *
   * @return string
   *   The vocabulary ID.
   */
  protected function getCategoryVocabularyId(): string {
    return $this::CATEGORY_VOCABULARY_ID;
  }

  /**
   * Counts the amount of products in category.
   *
   * @param int $category_id
   *   The category ID.
   *
   * @return int
   *   The amount of products in category.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  protected function countProductsInCategory(int $category_id): int {
    $query = $this->getProductsQuery($category_id);
    return (int) $query->count()->execute();
  }

  /**
   * Prepares entity query for products in category.
   *
   * @param int $category_id
   *   The category ID.
   * @param bool $negate_sort
   *   TRUE if sort should be reversed, FALSE to use default.
   *
   * @return \Drupal\Core\Entity\Query\QueryInterface
   *   The entity query with basic conditions.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  protected function getProductsQuery(int $category_id, bool $negate_sort = FALSE): QueryInterface {
    $category_ids = $this->getCategoryChildren($category_id);
    $category_ids[] = $category_id;

    $product_entity_definition = $this->entityTypeManager->getDefinition($this->getProductEntityTypeId());

    $query = $this->getProductStorage()->getQuery()->accessCheck(FALSE);
    if (!empty($this->getProductBundles()) && $product_entity_definition->hasKey('bundle')) {
      $query->condition($product_entity_definition->getKey('bundle'), $this->getProductBundles(), 'IN');
    }
    if ($product_entity_definition->hasKey('status')) {
      $query->condition($product_entity_definition->getKey('status'), 1);
    }
    $query->condition($this->getCategoryField(), $category_ids, 'IN');
    $query->sort($this->getSortField(), $this->getSortDirection($negate_sort));
    return $query;
  }

  /**
   * Gets children categories.
   *
   * @param int $term_id
   *   The current category ID.
   *
   * @return array
   *   An array with parent category IDs.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  protected function getCategoryChildren(int $term_id): array {
    $children = [];
    foreach ($this->loadCatalogTree() as $term) {
      if (\in_array($term_id, $term->parents)) {
        $children[] = (int) $term->tid;
        $children = \array_merge($children, $this->getCategoryChildren((int) $term->tid));
      }
    }
    return $children;
  }

  /**
   * Gets the product entity type ID.
   *
   * @return string
   *   The entity type ID.
   */
  protected function getProductEntityTypeId(): string {
    return $this::PRODUCT_ENTITY_TYPE_ID;
  }

  /**
   * Gets product storage.
   *
   * @return \Drupal\Core\Entity\ContentEntityStorageInterface
   *   The product storage.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  protected function getProductStorage(): ContentEntityStorageInterface {
    if (!isset($this->productStorage)) {
      $this->productStorage = $this->entityTypeManager->getStorage($this->getProductEntityTypeId());
    }
    return $this->productStorage;
  }

  /**
   * Gets product entity type bundle used for products.
   *
   * @return array
   *   An array with bundle IDs. Returns an empty array if all bundles allowed.
   */
  protected function getProductBundles(): array {
    return $this::PRODUCT_BUNDLES;
  }

  /**
   * Gets entity reference field name used for categories in product.
   *
   * @return string
   *   The name of category field.
   */
  protected function getCategoryField(): string {
    return $this::CATEGORY_FIELD;
  }

  /**
   * Gets the field name used for sorting results in category by default.
   *
   * @return string
   *   The category field name.
   */
  protected function getSortField(): string {
    return $this::SORT_FIELD;
  }

  /**
   * Gets default sort direction used for sorting results in category.
   *
   * @param bool $negate
   *   Used to flip sort direction.
   *
   * @return string
   *   The sort direction.
   */
  protected function getSortDirection(bool $negate = FALSE): string {
    if ($negate) {
      return $this::SORT_DIRECTION == 'ASC' ? 'DESC' : 'ASC';
    }
    else {
      return $this::SORT_DIRECTION;
    }
  }

}
