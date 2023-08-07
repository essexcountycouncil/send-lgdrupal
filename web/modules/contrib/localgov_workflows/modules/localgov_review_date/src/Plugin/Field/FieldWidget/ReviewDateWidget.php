<?php

namespace Drupal\localgov_review_date\Plugin\Field\FieldWidget;

use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\localgov_review_date\Entity\ReviewDate;
use Drupal\localgov_review_date\Form\ReviewDateSettingsForm;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin implementation of the 'review_date' widget.
 *
 * @FieldWidget(
 *   id = "review_date",
 *   label = @Translation("Review date"),
 *   description = @Translation("Review date widget"),
 *   field_types = {
 *     "review_date",
 *   },
 * )
 */
class ReviewDateWidget extends WidgetBase implements ContainerFactoryPluginInterface {

  /**
   * The config factory service.
   *
   * @var \Drupal\Core\Config\ConfigFactory
   */
  protected $configFactory;

  /**
   * {@inheritdoc}
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, array $third_party_settings, ConfigFactory $config_factory) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $third_party_settings);
    $this->configFactory = $config_factory;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $plugin_id,
      $plugin_definition,
      $configuration['field_definition'],
      $configuration['settings'],
      $configuration['third_party_settings'],
      $container->get('config.factory')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {

    // Get current review status object.
    $entity = $items->getEntity();
    $langcode = $form_state->get('langcode') ?? LanguageInterface::LANGCODE_NOT_SPECIFIED;
    $review_date = ReviewDate::getActiveReviewDate($entity, $langcode);

    // Calculate next review date.
    $config = $this->configFactory->get('localgov_review_date.settings');
    $default_next_review = $config->get('default_next_review') ?? 12;
    $next_review = date('Y-m-d', strtotime('+' . $default_next_review . ' months'));

    // Add form items.
    $element['reviewed'] = [
      '#type' => 'checkbox',
      '#title' => 'Content reviewed',
      '#description' => $this->t('I have reviewed this content.'),
      '#default' => FALSE,
      '#attributes' => [
        'class' => ['review-date-reviewed'],
      ],
    ];
    $element['review'] = [
      '#type' => 'container',
      '#attributes' => [
        'class' => ['review-date-container'],
      ],
      '#states' => [
        'visible' => [
          ':input[name="localgov_review_date[0][reviewed]"]' => [
            'checked' => TRUE,
          ],
        ],
      ],
    ];
    $element['review']['review_in'] = [
      '#type' => 'select',
      '#title' => $this->t('Next review in'),
      '#options' => ReviewDateSettingsForm::getNextReviewOptions(),
      '#default_value' => $default_next_review,
      '#attributes' => [
        'class' => ['review-date-review-in'],
      ],
    ];
    $element['review']['review_date'] = [
      '#type' => 'date',
      '#title' => $this->t('Review date'),
      '#description' => $this->t('When is this content next due to be reviewed.'),
      '#default_value' => $next_review,
      '#attributes' => [
        'class' => ['review-date-review-date'],
        'type' => 'date',
      ],
    ];
    $element['last_review'] = [
      '#type' => 'hidden',
      '#value' => is_null($review_date) ? '' : date('Y-m-d', $review_date->getCreatedTime()),
      '#attributes' => [
        'class' => ['review-date-last-review'],
      ],
    ];
    $element['next_review'] = [
      '#type' => 'hidden',
      '#value' => is_null($review_date) ? '' : date('Y-m-d', $review_date->getReviewTime()),
      '#attributes' => [
        'class' => ['review-date-next-review'],
      ],
    ];
    $element['langcode'] = [
      '#type' => 'hidden',
      '#value' => $langcode,
    ];

    // Add to advanced settings.
    if (isset($form['advanced'])) {
      $element += [
        '#type' => 'details',
        '#title' => $this->t('Review date'),
        '#group' => 'advanced',
        '#open' => TRUE,
        '#attributes' => [
          'class' => ['review-date-form'],
        ],
        '#attached' => [
          'library' => ['localgov_review_date/localgov_review_date.review_date'],
        ],
      ];
      $element['#weight'] = -5;
    }

    return $element;
  }

}
