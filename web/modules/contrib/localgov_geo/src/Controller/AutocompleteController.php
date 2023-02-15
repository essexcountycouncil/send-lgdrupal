<?php

namespace Drupal\localgov_geo\Controller;

use Drupal\Component\Utility\Crypt;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\KeyValueStore\KeyValueStoreInterface;
use Drupal\Core\Site\Settings;
use Drupal\geocoder\Entity\GeocoderProvider;
use Drupal\geocoder\GeocoderInterface;
use Geocoder\Model\AddressCollection;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * Defines a route controller for geo autocomplete form elements.
 */
class AutocompleteController extends ControllerBase {

  /**
   * The geocoder service.
   *
   * @var \Drupal\geocoder\GeocoderInterface
   */
  protected $geocoder;

  /**
   * The key value store.
   *
   * @var \Drupal\Core\KeyValueStore\KeyValueStoreInterface
   */
  protected $keyValue;

  /**
   * Constructs an EntityAutocompleteController object.
   *
   * @param \Drupal\geocoder\GeocoderInterface $geocoder
   *   The autocomplete matcher for entity references.
   * @param \Drupal\Core\KeyValueStore\KeyValueStoreInterface $key_value
   *   The key value factory.
   */
  public function __construct(GeocoderInterface $geocoder, KeyValueStoreInterface $key_value) {
    $this->geocoder = $geocoder;
    $this->keyValue = $key_value;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('geocoder'),
      $container->get('keyvalue')->get('localgov_geo_address_autocomplete')
    );
  }

  /**
   * Autocomplete the label of an entity.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request object that contains the typed tags.
   * @param string $settings_key
   *   The hashed key of the key/value entry that holds the settings.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   The matched entity labels as a JSON response.
   *
   * @throws \Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException
   *   Thrown if the selection settings key is not found in the key/value store
   *   or if it does not match the stored data.
   */
  public function autocomplete(Request $request, $settings_key) {
    $matches = [];

    // Get the typed string from the URL, if it exists.
    if ($input = $request->query->get('q')) {
      // Selection settings are passed in as a hashed key of a serialized array
      // stored in the key/value store.
      $settings = $this->keyValue->get($settings_key, FALSE);
      if ($settings !== FALSE) {
        $settings_hash = Crypt::hmacBase64(serialize($settings), Settings::getHashSalt());
        if (!hash_equals($settings_hash, $settings_key)) {
          // Disallow access when the selection settings hash does not match the
          // passed-in key.
          throw new AccessDeniedHttpException('Invalid selection settings key.');
        }
      }
      else {
        // Disallow access when the selection settings key is not found in the
        // key/value store.
        throw new AccessDeniedHttpException();
      }

      $address_fields = json_decode($input, TRUE);
      $country = $address_fields['country_code'];
      // Also available if can be passed to a geocoder:
      // $address_fields['langcode'].
      // Maybe worth formatting this. But for the rest of the fields they are in
      // the expected order.
      // Formating see AddressService::addressArrayToGeoString().
      //
      // @todo allow configuring to remove any other keys (eg. family_name).
      unset($address_fields['country_code'], $address_fields['langcode']);
      $address_string = implode(', ', array_filter($address_fields));
      $address_string .= ', ' . $country;

      $providers = GeocoderProvider::loadMultiple($settings['geocoder_providers']);
      if (
        ($address_suggestions = $this->geocoder->geocode($address_string, $providers)) &&
        $address_suggestions instanceof AddressCollection
      ) {
        $available_fields = array_keys($address_fields);
        $mapping = [
          'dependent_locality' => 'subLocality',
          'locality' => 'locality',
          'postal_code' => 'postalCode',
          'country_code' => 'country_code',
        ];
        foreach ($address_suggestions as $suggestion) {

          // Formatting address line 1.
          // Probably should be localized. Does this info exist in address
          // field data?
          $suggestion_array = $suggestion->toArray();
          $street_address = implode(' ', array_filter([
            $suggestion->getStreetNumber(),
            $suggestion->getStreetName(),
          ]));
          $suggestion_array['drupal_address']['address_line1'] = $street_address;
          foreach ($available_fields as $address_element) {
            if (isset($mapping[$address_element])) {
              $suggestion_array['drupal_address'][$address_element] = $suggestion_array[$mapping[$address_element]];
            }
          }
          $address_string = implode("<br />\n", array_filter($suggestion_array['drupal_address']));
          $matches[] = [
            'value' => $address_string,
            'key' => $suggestion_array,
          ];
        }
      }
    }

    return new JsonResponse($matches);
  }

}
