<?php

namespace Drupal\redirect\Plugin\Field\FieldWidget;

use Drupal\Component\Utility\NestedArray;
use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Url;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;

/**
 * Plugin implementation of the 'link' widget for the redirect module.
 *
 * Note that this field is meant only for the source field of the redirect
 * entity as it drops validation for non existing paths.
 *
 * @FieldWidget(
 *   id = "redirect_source",
 *   label = @Translation("Redirect source"),
 *   field_types = {
 *     "link"
 *   },
 *   settings = {
 *     "placeholder_url" = "",
 *     "placeholder_title" = ""
 *   }
 * )
 */
class RedirectSourceWidget extends WidgetBase {

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $default_url_value = $items[$delta]->path;
    if ($items[$delta]->query) {
      $default_url_value .= '?' . http_build_query($items[$delta]->query);
    }
    $element['path'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Path'),
      '#placeholder' => $this->getSetting('placeholder_url'),
      '#default_value' => $default_url_value,
      '#maxlength' => 2048,
      '#required' => $element['#required'],
      '#field_prefix' => Url::fromRoute('<front>', [], ['absolute' => TRUE])->toString(),
      '#attributes' => ['data-disable-refocus' => 'true'],
    ];

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function massageFormValues(array $values, array $form, FormStateInterface $form_state) {
    $values = parent::massageFormValues($values, $form, $form_state);
    // It is likely that the url provided for this field is not existing and
    // so the logic in the parent method did not set any defaults. Just run
    // through all url values and add defaults.
    foreach ($values as &$value) {
      if (!empty($value['path'])) {
        // In case we have query process the url.
        if (strpos($value['path'], '?') !== FALSE) {
          $url = UrlHelper::parse($value['path']);
          $value['path'] = $url['path'];
          $value['query'] = $url['query'];
        }
      }
    }
    return $values;
  }
}
