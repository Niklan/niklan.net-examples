<?php

namespace Drupal\example\Controller;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\example\Event\ExampleEvents;
use Drupal\example\Event\HelloWorldControllerEvent;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Provides responses for Example routes.
 */
class ExampleController implements ContainerInjectionInterface {

  /**
   * The event dispatcher.
   *
   * @var \Symfony\Component\EventDispatcher\EventDispatcherInterface
   */
  protected $eventDispatcher;

  /**
   * Constructs a new ExampleController object.
   *
   * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $event_dispatcher
   *   The event dispatcher.
   */
  public function __construct(EventDispatcherInterface $event_dispatcher) {
    $this->eventDispatcher = $event_dispatcher;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('event_dispatcher')
    );
  }

  /**
   * Builds the response.
   */
  public function build() {
    $page_content = ['#markup' => 'Hello World'];

    $event = new HelloWorldControllerEvent($page_content);
    $this->eventDispatcher->dispatch(ExampleEvents::HELLO_WORLD_BUILD, $event);

    $build['#title'] = $event->getPageTitle();
    $build['content'] = $event->getPageContent();

    return $build;
  }

}
