<?php

namespace Drupal\example\EventSubscriber;

use Drupal\Core\Render\HtmlResponse;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\node\NodeInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Theme test subscriber for controller requests.
 */
class FullMarkupPageSubscriber implements EventSubscriberInterface {

  /**
   * The current route match.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $currentRouteMatch;

  /**
   * Constructs a new ThemeTestSubscriber.
   *
   * @param \Drupal\Core\Routing\RouteMatchInterface $current_route_match
   */
  public function __construct(RouteMatchInterface $current_route_match) {
    $this->currentRouteMatch = $current_route_match;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[KernelEvents::VIEW][] = ['onView', 100];

    return $events;
  }

  /**
   * Enable raw response.
   */
  public function onView(GetResponseEvent $event) {
    // Only applicable for main content pages.
    if ($this->currentRouteMatch->getRouteName() != 'entity.node.canonical') {
      return;
    }

    /** @var NodeInterface $node */
    $node = $this->currentRouteMatch->getParameter('node');

    // Set this plugin only for specific type of nodes.
    if ($node->bundle() != 'page_with_full_markup') {
      return;
    }

    // We only apply plugin if body field is exist and is not empty.
    if (!$node->hasField('body') || $node->get('body')->isEmpty()) {
      return;
    }

    $response = new HtmlResponse($node->get('body')->value);
    $event->setResponse($response);
  }

}
