<?php

namespace Drupal\dummy\Service;

/**
 * Class MessageServiceBase.
 *
 * @package Drupal\dummy\Service
 */
abstract class MessageServiceBase implements MessageServiceInterface {

  /**
   * {@inheritDoc}
   */
  public function getType() {
    return 'status';
  }

}
