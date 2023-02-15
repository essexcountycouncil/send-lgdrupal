<?php

namespace Drupal\localgov_geo_address\Element;

use Drupal\address\Element\Address;
use Drupal\Component\Utility\Crypt;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\BubbleableMetadata;
use Drupal\Core\Site\Settings;
use Drupal\Core\Url;

/**
 * Extends the address element to add autocomplete.
 *
 * @FormElement("localgov_geo_address")
 */
class AutocompleteAddress extends Address {

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    $info = parent::getInfo();
    $class = get_class($this);
    $info['#process'][] = [
      $class, 'processAutocomplete',
    ];
    return $info;
  }

  /**
   * Adds autocomplete to the processed address form element.
   *
   * @param array $element
   *   The form element to process.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   * @param array $complete_form
   *   The complete form structure.
   *
   * @return array
   *   The processed element.
   */
  // @codingStandardsIgnoreLine compatiblity with Drupal\Core\Render\Element\FormElement::processAutocomplete.
  public static function processAutocomplete(&$element, FormStateInterface $form_state, &$complete_form) {

    $access = FALSE;
    if (isset($element['address_line1'])) {
      $data = [
        'geocoder_providers' => $element['#geocoders'],
      ];
      $key = Crypt::hmacBase64(serialize($data), Settings::getHashSalt());

      $key_value_storage = \Drupal::keyValue('localgov_geo_address_autocomplete');
      if (!$key_value_storage->has($key)) {
        $key_value_storage->set($key, $data);
      }

      $parameters = [
        'settings_key' => $key,
      ];
      $url = Url::fromRoute('localgov_geo.autocomplete', $parameters)->toString(TRUE);

      /** @var \Drupal\Core\Access\AccessManagerInterface $access_manager */
      $access_manager = \Drupal::service('access_manager');
      $access = $access_manager->checkNamedRoute('localgov_geo.autocomplete', $parameters, \Drupal::currentUser(), TRUE);
    }

    if ($access) {
      $metadata = BubbleableMetadata::createFromRenderArray($element);
      if ($access->isAllowed()) {
        $element['#attributes']['class'][] = 'localgov-geo-autocomplete';
        $metadata->addAttachments(['library' => ['localgov_geo_address/autocomplete']]);
        // Provide a data attribute for the JavaScript behavior to bind to.
        $element['#attributes']['data-autocomplete-path'] = $url->getGeneratedUrl();
        $metadata = $metadata->merge($url);
      }
      $metadata
        ->merge(BubbleableMetadata::createFromObject($access))
        ->applyTo($element);
    }

    return $element;
  }

}
