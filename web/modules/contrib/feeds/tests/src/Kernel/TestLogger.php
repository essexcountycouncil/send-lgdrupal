<?php

namespace Drupal\Tests\feeds\Kernel;

use Drupal\Core\Logger\RfcLoggerTrait;
use Psr\Log\LoggerInterface;

/**
 * Logger for testing log messages.
 */
class TestLogger implements LoggerInterface {
  use RfcLoggerTrait;

  /**
   * Array of logged messages.
   *
   * @var array
   */
  protected $messages = [];

  /**
   * {@inheritdoc}
   */
  public function log($level, $message, array $context = []): void {
    // Remove any context that does not start with a placeholder.
    foreach ($context as $key => $value) {
      $placeholder_key = mb_substr($key, 0, 1);
      switch ($placeholder_key) {
        case '@':
        case '%':
        case ':':
          break;

        default:
          unset($context[$key]);
      }
    }

    $this->messages[$level][] = strtr($message, $context);
  }

  /**
   * Returns the logged messages.
   *
   * @return array
   *   An array of all logged messages.
   */
  public function getMessages(): array {
    return $this->messages;
  }

  /**
   * Clears all logged messages.
   */
  public function clearMessages() {
    $this->messages = [];
  }

}
