<?php

namespace Drupal\localgov_services_status;

use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\PathProcessor\InboundPathProcessorInterface;
use Drupal\Core\PathProcessor\OutboundPathProcessorInterface;
use Drupal\Core\Render\BubbleableMetadata;
use Symfony\Component\HttpFoundation\Request;

/**
 * Processes the inbound path using path alias lookups.
 */
class PathProcessor implements InboundPathProcessorInterface, OutboundPathProcessorInterface {

  /**
   * The path processor.
   *
   * @var \Drupal\Core\PathProcessor\InboundPathProcessorInterface
   */
  protected $pathProcessor;

  /**
   * The language manager.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * The path validator.
   *
   * @var \Drupal\Core\Path\PathValidatorInterface
   */
  protected $pathValidator;

  /**
   * Builds PathProcessor object.
   *
   * @param \Drupal\Core\PathProcessor\InboundPathProcessorInterface $path_processor
   *   The path processor.
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   The language manager.
   */
  public function __construct(InboundPathProcessorInterface $path_processor, LanguageManagerInterface $language_manager) {
    $this->pathProcessor = $path_processor;
    $this->languageManager = $language_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function processInbound($path, Request $request) {
    $request_path = $this->getPath($request->getPathInfo());

    if (substr($request_path, -7) == '/status') {
      $base_path = substr($request_path, 0, -7);
      $processed_path = $this->pathProcessor->processInbound($base_path, $request);
      if ($processed_path !== $base_path) {
        $path = $processed_path . '/status';
      }
    }

    return $path;
  }

  /**
   * {@inheritdoc}
   */
  public function processOutbound($path, &$options = [], Request $request = NULL, BubbleableMetadata $bubbleableMetadata = NULL) {
    assert($this->pathProcessor instanceof OutboundPathProcessorInterface);

    // This is the inverse of inbound. Maybe less all-encompassing to swap this
    // for the preg_match.
    if (substr($path, -7) == '/status') {
      $base_path = substr($path, 0, -7);
      $processed_path = $this->pathProcessor->processOutbound($base_path, $options, $request);
      $path = $processed_path . '/status';
    }

    return $path;
  }

  /**
   * Helper function to handle multilingual paths.
   *
   * @param string $path_info
   *   Path that might contain language prefix.
   *
   * @return string
   *   Path without language prefix.
   */
  protected function getPath($path_info) {
    $language_prefix = '/' . $this->languageManager->getCurrentLanguage(LanguageInterface::TYPE_URL)->getId() . '/';

    if (substr($path_info, 0, strlen($language_prefix)) == $language_prefix) {
      $path_info = '/' . substr($path_info, strlen($language_prefix));
    }

    return $path_info;
  }

}
