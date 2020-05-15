<?php

namespace Drupal\example\Render\Placeholder;

use Drupal\Component\Serialization\Json;
use Drupal\Component\Utility\Crypt;
use Drupal\Component\Utility\Html;
use Drupal\Core\Render\Placeholder\PlaceholderStrategyInterface;
use Drupal\Core\Site\Settings;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Provides AJAX placeholder strategy.
 *
 * The placeholders on the page will be replaced with AJAX calls.
 */
final class AjaxPlaceholderStrategy implements PlaceholderStrategyInterface {

  /**
   * The module cookie name for no-JS mark.
   */
  public const NOJS_COOKIE = 'example_ajax_strategy_nojs';

  /**
   * The request stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * Constructs a new AjaxPlaceholderStrategy object.
   *
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The request stack.
   */
  public function __construct(RequestStack $request_stack) {
    $this->requestStack = $request_stack;
  }

  /**
   * {@inheritdoc}
   */
  public function processPlaceholders(array $placeholders): array {
    // If client doesn't have JavaScript enabled, fallback to default response
    // with blocking rendering, but client will receive all content. F.e. search
    // engines crawlers without JS still be possible to parse content.
    if ($this->requestStack->getCurrentRequest()->cookies->has(static::NOJS_COOKIE)) {
      return $placeholders;
    }

    foreach ($placeholders as $placeholder => $placeholder_render_array) {
      // Skip processing attribute placeholders.
      // @see \Drupal\Core\Access\RouteProcessorCsrf::renderPlaceholderCsrfToken()
      // @see \Drupal\Core\Form\FormBuilder::renderFormTokenPlaceholder()
      // @see \Drupal\Core\Form\FormBuilder::renderPlaceholderFormAction()
      if (!$this->placeholderIsAttributeSafe($placeholder)) {
        $placeholders[$placeholder] = $this->createAjaxPlaceholder($placeholder_render_array);
      }
    }
    return $placeholders;
  }

  /**
   * Determines whether the given placeholder is attribute-safe or not.
   *
   * @param string $placeholder
   *   A placeholder.
   *
   * @return bool
   *   Whether the placeholder is safe for use in a HTML attribute (in case it's
   *   a placeholder for a HTML attribute value or a subset of it).
   */
  private function placeholderIsAttributeSafe($placeholder): bool {
    return $placeholder[0] !== '<' || $placeholder !== Html::normalize($placeholder);
  }

  /**
   * Creates an AJAX placeholder.
   *
   * @param array $placeholder_render_array
   *   The placeholder render array.
   *
   * @return array
   *   The renderable array with custom placeholder markup.
   */
  private function createAjaxPlaceholder(array $placeholder_render_array): array {
    $callback = $placeholder_render_array['#lazy_builder'][0];
    $args = $placeholder_render_array['#lazy_builder'][1];

    return [
      '#type' => 'html_tag',
      '#tag' => 'span',
      '#cache' => [
        'max-age' => 0,
      ],
      '#attributes' => [
        'data-ajax-placeholder' => Json::encode([
          'callback' => $placeholder_render_array['#lazy_builder'][0],
          'args' => $placeholder_render_array['#lazy_builder'][1],
          'token' => self::generateToken($callback, $args),
        ]),
      ],
      '#attached' => [
        'library' => ['example/ajax-placeholder'],
      ],
    ];
  }

  /**
   * Generates token for protection from random code executions.
   *
   * @param string $callback
   *   The callback function.
   * @param array $args
   *   The callback arguments.
   *
   * @return string
   *   The token that sustain across requests.
   */
  public static function generateToken(string $callback, array $args): string {
    // Use hash salt to protect token against attacks.
    $token_parts = [$callback, $args, Settings::get('hash_salt')];
    return Crypt::hashBase64(serialize($token_parts));
  }

}
