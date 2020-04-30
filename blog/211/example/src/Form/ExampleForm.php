<?php

namespace Drupal\example\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a example with form that locks actions.
 *
 * The form uses persistent lock, which will be cleared programmatically or
 * when ends timeout.
 *
 * Form generates file and lock it for 5 minutes. This means, the generation
 * can be requested only once per 5 minutes. But we also provide unlock button
 * to forcefully run generate process.
 */
class ExampleForm extends FormBase {

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
  public static function create(ContainerInterface $container) {
    $instance = parent::create($container);
    $instance->lock = $container->get('lock.persistent');
    $instance->bigFileGenerator = $container->get('example.big_file_generator');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $form['actions'] = [
      '#type' => 'actions',
    ];
    $form['actions']['generate'] = [
      '#type' => 'submit',
      '#value' => new TranslatableMarkup('Generate'),
      '#disabled' => !$this->lock->lockMayBeAvailable($this->getFormId()),
      '#op' => 'generate',
    ];
    $form['actions']['unlock'] = [
      '#type' => 'submit',
      '#value' => new TranslatableMarkup('Force unlock'),
      '#op' => 'unlock',
      '#attributes' => [
        'class' => ['button', 'button--danger'],
      ],
      '#access' => !$this->lock->lockMayBeAvailable($this->getFormId()),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'example_locking_form';
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $triggered_element = $form_state->getTriggeringElement();
    $action = !isset($triggered_element['#op']) ? 'generate' : $triggered_element['#op'];
    switch ($action) {
      case 'generate':
        if (!$this->lock->acquire($this->getFormId(), 300)) {
          return;
        }
        $this->bigFileGenerator->generate('form', TRUE);
        break;

      case 'unlock':
        $this->lock->release($this->getFormId());
        break;
    }
  }

}
