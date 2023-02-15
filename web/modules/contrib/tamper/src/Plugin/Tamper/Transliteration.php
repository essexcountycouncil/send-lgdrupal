<?php

namespace Drupal\tamper\Plugin\Tamper;

use Drupal\Component\Transliteration\TransliterationInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\tamper\Exception\TamperException;
use Drupal\tamper\TamperableItemInterface;
use Drupal\tamper\TamperBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin implementation for transliteration.
 *
 * @Tamper(
 *   id = "transliteration",
 *   label = @Translation("Transliterates text from Unicode to US-ASCII."),
 *   description = @Translation("Runs the value through the transliteration service. Letters will have language decorations and accents removed."),
 *   category = "Text"
 * )
 */
class Transliteration extends TamperBase implements ContainerFactoryPluginInterface {

  /**
   * The transliteration service.
   *
   * @var \Drupal\Component\Transliteration\TransliterationInterface
   */
  protected $transliteration;

  /**
   * Constructs a Transliteration plugin.
   *
   * @param array $configuration
   *   The plugin configuration.
   * @param string $plugin_id
   *   The plugin ID.
   * @param mixed $plugin_definition
   *   The plugin definition.
   * @param mixed $source_definition
   *   A definition of which sources there are that Tamper plugins can use.
   * @param \Drupal\Component\Transliteration\TransliterationInterface $transliteration
   *   The transliteration service.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, $source_definition, TransliterationInterface $transliteration) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $source_definition);
    $this->transliteration = $transliteration;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $configuration['source_definition'],
      $container->get('transliteration')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function tamper($data, TamperableItemInterface $item = NULL) {
    if (!is_string($data)) {
      throw new TamperException('Input should be a string.');
    }

    return $this->transliteration->transliterate($data, LanguageInterface::LANGCODE_DEFAULT, '_');
  }

}
