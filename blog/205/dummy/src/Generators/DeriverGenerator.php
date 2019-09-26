<?php

namespace Drupal\dummy\Generators;

use DrupalCodeGenerator\Command\BaseGenerator;
use DrupalCodeGenerator\Utils;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Question\Question;

class DeriverGenerator extends BaseGenerator {

  /**
   * {@inheritDoc}
   */
  protected $name = 'dummy:deriver';

  /**
   * {@inheritDoc}
   */
  protected $description = 'Generates Deriver object for derivatives.';

  /**
   * {@inheritDoc}
   */
  protected $templatePath = __DIR__ . '/templates';

  /**
   * {@inheritDoc}
   */
  public function interact(InputInterface $input, OutputInterface $output) {
    // Collects module info.
    $questions = Utils::moduleQuestions();
    $this->vars = &$this->collectVars($input, $output, $questions);

    // Ask for DependencyInjection.
    $dependency_injection_question = new ConfirmationQuestion(t('Do you want to add Dependency Injection support for deriver?'), TRUE);
    $this->vars['dependency_injection'] = $this->ask($input, $output, $dependency_injection_question);

    // Ask for deriver name.
    $default_deriver_name = Utils::camelize($this->vars['name'] . 'Deriver');
    $deriver_name_question = new Question(t('Deriver object name'), $default_deriver_name);
    $deriver_name_question->setValidator([Utils::class, 'validateRequired']);
    $this->vars['class_name'] = $this->ask($input, $output, $deriver_name_question);
  
    $this
      ->addFile('src/Plugin/Derivative/{class_name}.php')
      ->template('deriver.html.twig');
  }

}
