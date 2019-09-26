<?php

namespace Drupal\dummy\Service;

/**
 * Class MessageServiceSecond.
 *
 * @package Drupal\dummy\Service
 */
class MessageServiceSecond extends MessageServiceBase {

  /**
   * {@inheritDoc}
   */
  public function getMessage() {
    return 'Bip-boop-bip, it is working!';
  }

}
