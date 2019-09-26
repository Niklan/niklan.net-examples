<?php

namespace Drupal\dummy\Service;

/**
 * Interface MessageServiceInterface.
 *
 * @package Drupal\dummy\Service
 */
interface MessageServiceInterface {

  /**
   * Gets message content.
   *
   * @return string
   *   The message.
   */
  public function getMessage();

  /**
   * Gets message type.
   *
   * @return string
   *   The message type.
   */
  public function getType();

}
