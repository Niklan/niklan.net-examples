<?php

namespace Drupal\example\Controller;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\ServiceUnavailableHttpException;

/**
 * Generates big file with locking.
 *
 * The generate process will be processed using lock. That will protect multiple
 * simultaneous calls of this controller and generation init. Only one generate
 * is possible.
 *
 * This example uses lock which will be destroyed after response will be sent to
 * the user. So we don't need to care about unlocking it.
 */
final class ExampleController implements ContainerInjectionInterface {

  /**
   * The lock backend.
   *
   * @var \Drupal\Core\Lock\LockBackendInterface
   */
  protected $lock;

  /**
   * The big file generator.
   *
   * @var \Drupal\example\BigFileGenerator
   */
  protected $bigFileGenerator;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): ExampleController {
    $instance = new static();
    $instance->lock = $container->get('lock');
    $instance->bigFileGenerator = $container->get('example.big_file_generator');
    return $instance;
  }

  /**
   * Builds the response.
   */
  public function build(): array {
    if (!$this->bigFileGenerator->fileExists('controller')) {
      $lock_acquired = $this->lock->acquire('example_controller', 60);
      if (!$lock_acquired) {
        throw new ServiceUnavailableHttpException(3, new TranslatableMarkup('Generation in progress. Try again shortly.'));
      }

      $this->bigFileGenerator->generate('controller');
    }

    $build['content'] = [
      '#type' => 'inline_template',
      '#template' => '<a href="{{ href }}">{{ label }}</a>',
      '#context' => [
        'href' => file_create_url($this->bigFileGenerator->buildUri('controller')),
        'label' => new TranslatableMarkup('Open generated file'),
      ],
    ];

    return $build;
  }

}
