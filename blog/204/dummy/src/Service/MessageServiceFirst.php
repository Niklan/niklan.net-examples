<?php

namespace Drupal\dummy\Service;

/**
 * Class MessageServiceFirst.
 *
 * @package Drupal\dummy\Service
 */
class MessageServiceFirst extends MessageServiceBase {

  /**
   * {@inheritDoc}
   */
  public function getMessage() {
    return 'Hello World!';
  }

  /**
   * {@inheritDoc}
   */
  public function getType() {
    return 'warning';
  }

}
