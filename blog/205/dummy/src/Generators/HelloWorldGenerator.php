<?php

namespace Drupal\dummy\Generators;

use DrupalCodeGenerator\Command\BaseGenerator;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class HelloWorldGenerator.
 *
 * @package Drupal\dummy\Generators
 */
class HelloWorldGenerator extends BaseGenerator {

  /**
   * {@inheritDoc}
   */
  protected $name = 'dummy:hello-world';

  /**
   * {@inheritDoc}
   */
  protected $description = 'Generates php file which echoing "Hello World!".';

  /**
   * {@inheritDoc}
   */
  public function interact(InputInterface $input, OutputInterface $output) {
    $file_content = <<<PHP
<?php

/**
 * @file
 * Shows how simple generator works.
 */
 
echo 'Hello World';

PHP;

    // Write content to file.
    $this
      ->addFile('hello-world.php')
      ->content($file_content);
  }

}
