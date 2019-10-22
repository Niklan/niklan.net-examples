<?php

namespace Drupal\example\Event;

use Symfony\Component\EventDispatcher\Event;

/**
 * Define hello world controller event.
 */
class HelloWorldControllerEvent extends Event {

  /**
   * The page content.
   *
   * @var array
   */
  protected $pageContent;

  /**
   * The page title.
   *
   * @var string
   */
  protected $pageTitle;

  /**
   * Constructs a new HelloWorldControllerEvent object.
   *
   * @param array $page_content
   *   The page content.
   * @param string|NULL $page_title
   *   The page title.
   */
  public function __construct(array $page_content, string $page_title = NULL) {
    $this->setPageContent($page_content);
    $this->setPageTitle($page_title);
  }

  /**
   * Sets page content.
   *
   * @param array $page_content
   *   The page content.
   *
   * @return $this
   */
  public function setPageContent(array $page_content) {
    $this->pageContent = $page_content;

    return $this;
  }

  /**
   * Gets page content.
   *
   * @return array
   *   The page content.
   */
  public function getPageContent() {
    return $this->pageContent;
  }

  /**
   * Sets page title.
   *
   * @param string|NULL $page_title
   *   The page title.
   *
   * @return $this
   */
  public function setPageTitle(string $page_title = NULL) {
    $this->pageTitle = $page_title;

    return $this;
  }

  /**
   * Gets page title.
   *
   * @return NULL|string
   *   The page title.
   */
  public function getPageTitle() {
    return $this->pageTitle;
  }

}
