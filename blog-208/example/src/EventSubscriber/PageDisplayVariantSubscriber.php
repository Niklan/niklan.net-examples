<?php

namespace Drupal\example\EventSubscriber;

use Drupal\Core\Render\PageDisplayVariantSelectionEvent;
use Drupal\Core\Render\RenderEvents;
use Drupal\node\NodeInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Selects the custom page display variant.
 */
class PageDisplayVariantSubscriber implements EventSubscriberInterface {

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[RenderEvents::SELECT_PAGE_DISPLAY_VARIANT][] = ['onSelectPageDisplayVariant'];

    return $events;
  }

  /**
   * Selects the page display variant.
   *
   * @param \Drupal\Core\Render\PageDisplayVariantSelectionEvent $event
   *   The event to process.
   */
  public function onSelectPageDisplayVariant(PageDisplayVariantSelectionEvent $event) {
    $route_match = $event->getRouteMatch();

    // Only applicable for main content pages.
    if ($route_match->getRouteName() != 'entity.node.canonical') {
      return;
    }

    /** @var NodeInterface $node */
    $node = $route_match->getParameter('node');

    // Set this plugin only for specific type of nodes.
    if ($node->bundle() != 'page_with_body_markup') {
      return;
    }

    // We only apply plugin if body field is exist and is not empty.
    if (!$node->hasField('body') || $node->get('body')->isEmpty()) {
      return;
    }

    $event->setPluginId('body_markup');
  }

}
