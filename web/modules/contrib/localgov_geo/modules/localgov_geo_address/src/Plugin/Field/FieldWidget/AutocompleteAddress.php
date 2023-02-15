<?php

namespace Drupal\localgov_geo_address\Plugin\Field\FieldWidget;

use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\address\Plugin\Field\FieldWidget\AddressDefaultWidget;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\geocoder\Entity\GeocoderProvider;
use Drupal\geocoder\ProviderPluginManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Extends the address widget to use our custom element.
 *
 * @FieldWidget(
 *   id = "localgov_geo_address",
 *   label = @Translation("Address autocomplete"),
 *   field_types = {
 *     "address"
 *   },
 * )
 */
class AutocompleteAddress extends AddressDefaultWidget {

  /**
   * The Geocoder Provider Plugin Manager.
   *
   * @var \Drupal\geocoder\ProviderPluginManager|null
   */
  protected $geocoderProviderManager;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface|null
   */
  protected $entityTypeManager;

  /**
   * The entity field manager.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface|null
   */
  protected $entityFieldManager;

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return parent::defaultSettings() + [
      'providers' => [],
      'geocode_geofield' => '',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    /** @var static $widget */
    $widget = parent::create($container, $configuration, $plugin_id, $plugin_definition);
    $widget->setGecoderProviderManager($container->get('plugin.manager.geocoder.provider'));
    $widget->setEntityTypeManager($container->get('entity_type.manager'));
    $widget->setEntityFieldManager($container->get('entity_field.manager'));
    return $widget;
  }

  /**
   * Retrieves the entity type manager service.
   *
   * @return \Drupal\Core\Entity\EntityTypeManagerInterface
   *   The entity type manager service.
   */
  public function getEntityTypeManager() {
    return $this->entityTypeManager ?: \Drupal::entityTypeManager();
  }

  /**
   * Retrieves the entity type manager service.
   *
   * @return \Drupal\Core\Entity\EntityFieldManagerInterface
   *   The entity field manager service.
   */
  public function getEntityFieldManager() {
    return $this->entityFieldManager ?: \Drupal::service('entity_field.manager');
  }

  /**
   * Get the Geocoder Provider Plugin Manager.
   *
   * @return \Drupal\geocoder\ProviderPluginManager
   *   The Gecoder provider plugin manager.
   */
  public function getGeocoderProviderManager() {
    return $this->geocoderProviderManager ?: \Drupal::service('plugin.manager.geocoder.provider');
  }

  /**
   * Sets the entity type manager service.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager service.
   *
   * @return $this
   */
  public function setEntityTypeManager(EntityTypeManagerInterface $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
    return $this;
  }

  /**
   * Sets the entity field manager service.
   *
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $entity_field_manager
   *   The entity field manager service.
   *
   * @return $this
   */
  public function setEntityFieldManager(EntityFieldManagerInterface $entity_field_manager) {
    $this->entityFieldManager = $entity_field_manager;
    return $this;
  }

  /**
   * Set the Geocoder Provider Plugin Manager.
   *
   * @param \Drupal\geocoder\ProviderPluginManager $provider_plugin_manager
   *   The Gecoder provider plugin manager.
   *
   * @return $this
   */
  public function setGecoderProviderManager(ProviderPluginManager $provider_plugin_manager) {
    $this->geocoderProviderManager = $provider_plugin_manager;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $element = parent::settingsForm($form, $form_state);

    $providers = !empty($this->getSetting('providers')) ? $this->getSetting('providers') : [];
    // Get the enabled/selected providers.
    $enabled_providers = [];
    foreach ($providers as $provider_id => $provider_settings) {
      if ($provider_settings['checked']) {
        $enabled_providers[] = $provider_id;
      }
    }
    // Generates the Draggable Table of Selectable Geocoder providers.
    $element['providers'] = $this->getGeocoderProviderManager()->providersPluginsTableList($enabled_providers);

    // Set a validation for the providers selection.
    $element['providers']['#element_validate'][] = [
      static::class,
      'validateProvidersSettingsForm',
    ];

    $options = [];
    /** @var \Drupal\Core\Field\FieldConfigInterface $field */
    $field = $form_state->getFormObject()->getEntity();
    foreach ($this->getEntityFieldManager()->getFieldDefinitions($field->getTargetEntityTypeId(), $field->getTargetBundle()) as $id => $definition) {
      if ($definition->getType() == 'geofield') {
        $options[$id] = $this->t(
          '@label (@name) [@type]', [
            '@label' => $definition->getLabel(),
            '@name' => $definition->getName(),
            '@type' => $definition->getType(),
          ]
        );
      }
    }
    $element['geocode_geofield'] = [
      '#type' => 'select',
      '#title' => $this->t('Co-ordinates'),
      '#description' => $this->t('Populate geofield, with longitude and latitude fields available on the form.'),
      '#options' => $options,
      '#required' => FALSE,
      '#multiple' => FALSE,
      '#empty_value' => '',
      '#default_value' => $this->getSetting('geocode_geofield') ?: '',
    ];

    return $element;
  }

  /**
   * Validates the providers selection.
   *
   * @param array $element
   *   The form API form element.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state object.
   */
  public static function validateProvidersSettingsForm(array $element, FormStateInterface &$form_state) {
    $providers = !empty($element['#value']) ? array_filter($element['#value'], function ($value) {
      return isset($value['checked']) && (bool) $value['checked'];
    }) : [];

    if (empty($providers)) {
      $form_state->setError($element, \t('The selected Geocode operation needs at least one provider.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = parent::settingsSummary();

    $provider_labels = array_map(function (GeocoderProvider $provider): ?string {
      return $provider->label();
    }, $this->getEnabledGeocoderProviders());

    $summary['providers'] = $this->t('Geocoder providers(s): @provider_ids', [
      '@provider_ids' => !empty($provider_labels) ? implode(', ', $provider_labels) : $this->t('Not set'),
    ]);

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state): array {
    $element = parent::formElement($items, $delta, $element, $form, $form_state);

    $element['address']['#type'] = 'localgov_geo_address';
    $element['address']['#geocoders'] = array_keys($this->getEnabledGeocoderProviders());
    $element['address']['#attributes']['data-geocode-geofield'] = $this->getSetting('geocode_geofield');
    return $element;
  }

  /**
   * Returns the Geocoder providers that are enabled in this formatter.
   *
   * @return \Drupal\geocoder\Entity\GeocoderProvider[]
   *   The enabled Geocoder providers, sorted by weight.
   */
  public function getEnabledGeocoderProviders(): array {
    $formatter_settings = $this->getSetting('providers');

    $enabled_providers = array_filter($formatter_settings, function ($configuration) {
      return (bool) $configuration['checked'];
    });

    $providers = $this->getEntityTypeManager()
      ->getStorage('geocoder_provider')
      ->loadMultiple(array_keys($enabled_providers));

    // Sort providers according to weight.
    uasort($providers, function (GeocoderProvider $a, GeocoderProvider $b) use ($formatter_settings): int {
      if ((int) $formatter_settings[$a->id()]['weight'] === (int) $formatter_settings[$b->id()]['weight']) {
        return 0;
      }
      return (int) $formatter_settings[$a->id()]['weight'] < (int) $formatter_settings[$b->id()]['weight'] ? -1 : 1;
    });

    return $providers;
  }

}
