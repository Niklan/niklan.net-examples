<?php

namespace Drupal\example\Event;

/**
 * Defines events for example module.
 */
final class ExampleEvents {

  /**
   * Name of the event fired when prepare content for hello world controller.
   *
   * @Event
   *
   * @see \Drupal\example\Event\HelloWorldControllerEvent
   * @see \Drupal\example\Controller\ExampleController
   *
   * @var string
   */
  const HELLO_WORLD_BUILD = 'example.hello_world_build';

}
