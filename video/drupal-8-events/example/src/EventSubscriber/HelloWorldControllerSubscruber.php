<?php

namespace Drupal\example\EventSubscriber;

use Drupal\example\Event\ExampleEvents;
use Drupal\example\Event\HelloWorldControllerEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Provides subscriber for hello world controller.
 */
class HelloWorldControllerSubscruber implements EventSubscriberInterface {

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    return [
      ExampleEvents::HELLO_WORLD_BUILD => 'onHelloWorldBuild',
    ];
  }

  /**
   * Reacts to content preparation for hello world controller.
   *
   * @param \Drupal\example\Event\HelloWorldControllerEvent $event
   *   The hello world controller event.
   */
  public function onHelloWorldBuild(HelloWorldControllerEvent $event) {
    $event->setPageTitle('Hello from event!');
    $content = $event->getPageContent();
    $content['additional'] = [
      '#type' => 'fieldset',
      '#title' => 'Additional content',
    ];
    $content['additional']['description'] = [
      '#markup' => 'This content was additionally added from hello world event subscriber.',
    ];
    $event->setPageContent($content);
  }

}
