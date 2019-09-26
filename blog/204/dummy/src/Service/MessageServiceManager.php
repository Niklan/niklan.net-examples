<?php

namespace Drupal\dummy\Service;

/**
 * Class MessageServiceManager.
 *
 * @package Drupal\dummy\Service
 */
class MessageServiceManager {

  /**
   * The messages services array.
   *
   * @var \Drupal\dummy\Service\MessageServiceInterface[]
   */
  protected $messages = [];

  /**
   * An array with sorted services by priority, NULL otherwise.
   *
   * @var NULL|array
   */
  protected $messagesSorted = NULL;

  /**
   * Adds message service to internal service storage.
   *
   * @param \Drupal\dummy\Service\MessageServiceInterface $message
   *   The message service.
   * @param int $priority
   *   The service priority.
   */
  public function addService(MessageServiceInterface $message, $priority = 0) {
    $this->messages[$priority][] = $message;
    // Reset sorted status to be resorted on next call.
    $this->messagesSorted = NULL;
  }

  /**
   * Sorts messages services.
   *
   * @return \Drupal\dummy\Service\MessageServiceInterface[]
   *   The sorted messages services.
   */
  protected function sortMessages() {
    $sorted = [];
    krsort($this->messages);

    foreach ($this->messages as $messages) {
      $sorted = array_merge($sorted, $messages);
    }

    return $sorted;
  }


  /**
   * Gets all messages from services.
   *
   * @return array
   *   The array contains message and it's type.
   */
  public function getMessages() {
    if (!$this->messagesSorted) {
      $this->messagesSorted = $this->sortMessages();
    }

    $messages = [];
    foreach ($this->messagesSorted as $message_service) {
      $messages[] = [
        'message' => $message_service->getMessage(),
        'type' => $message_service->getType(),
      ];
    }

    return $messages;
  }

}
