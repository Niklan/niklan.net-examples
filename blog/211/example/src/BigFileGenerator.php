<?php

namespace Drupal\example;

use Drupal\Component\Utility\Random;
use Drupal\Core\File\FileSystemInterface;

/**
 * Provides generator of fake file which takes a bit of time.
 */
final class BigFileGenerator {

  /**
   * The file path directory where files will be stored.
   *
   * @var string
   */
  private $directory = 'public://niklan-example';

  /**
   * The amount if items to generate.
   *
   * @var int
   */
  private $itemsCount = 1000000;

  /**
   * The file system.
   *
   * @var \Drupal\Core\File\FileSystemInterface
   */
  private $fileSystem;

  /**
   * Constructs a new BigFileGenerator object.
   *
   * @param \Drupal\Core\File\FileSystemInterface $file_system
   *   The file system.
   */
  public function __construct(FileSystemInterface $file_system) {
    $this->fileSystem = $file_system;
  }

  /**
   * Generates big file with fake data.
   *
   * @param string $filename
   *   The filename without extension.
   * @param bool $force
   *   TRUE if file must be generated even if it exists. The old file will be
   *   overridden.
   */
  public function generate(string $filename, bool $force = FALSE): void {
    if (!$force && $this->fileExists($filename)) {
      return;
    }

    // Make sure directory exists and writable.
    if (!$this->fileSystem->prepareDirectory($this->directory, FileSystemInterface::CREATE_DIRECTORY)) {
      throw new \Exception(sprintf('The %s directory is not writable.', $this->directory));
    }

    // If file exists and we're still here, then $force is set to TRUE and we
    // delete the old file.
    if ($this->fileExists($filename)) {
      $this->fileSystem->unlink($this->buildUri($filename));
    }

    $random = new Random();
    $temp = $this->fileSystem->tempnam('temporary://', 'example');
    // Write new data to file.
    $handle = fopen($temp, 'w');
    for ($i = 0; $i < $this->itemsCount; $i++) {
      $fields = [
        $random->string('255'),
        $random->word('17'),
      ];
      fputcsv($handle, $fields);
    }
    fclose($handle);
    // Move file only when write is finished.
    $this->fileSystem->move($temp, $this->buildUri($filename), FileSystemInterface::EXISTS_REPLACE);
  }

  /**
   * Check's whether file with provided filename is exists or not.
   *
   * @param string $filename
   *   The filename without extension.
   *
   * @return bool
   *   TRUE if file presented, FALSE otherwise.
   */
  public function fileExists(string $filename): bool {
    return file_exists($this->buildUri($filename));
  }

  /**
   * Builds URI to the file.
   *
   * @param string $filename
   *   The filename without extension.
   *
   * @return string
   *   The fully qualified URI to the file.
   */
  public function buildUri(string $filename): string {
    return $this->directory . '/' . $filename . '.csv';
  }

}
