<?php

namespace Drupal\localgov_openreferral\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\localgov_openreferral\Event\GenerateEntityMapping;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Property Mapping form.
 *
 * @property \Drupal\localgov_openreferral\Entity\PropertyMappingInterface $entity
 */
class PropertyMappingForm extends EntityForm {

  /**
   * Entity Bundle Information service.
   *
   * @var \Drupal\Core\Entity\EntityTypeBundleInfoInterface
   */
  protected $entityBundleInfo;

  /**
   * Event dispatcher service.
   *
   * @var \Symfony\Component\EventDispatcher\EventDispatcherInterface
   */
  protected $eventDispatcher;

  /**
   * Class constructor.
   */
  public function __construct(EntityTypeBundleInfoInterface $entity_bundle_info, EventDispatcherInterface $event_dispatcher) {
    $this->entityBundleInfo = $entity_bundle_info;
    $this->eventDispatcher = $event_dispatcher;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.bundle.info'),
      $container->get('event_dispatcher')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {

    $form = parent::form($form, $form_state);

    if ($this->entity->isNew()) {
      $bundle_info = $this->entityBundleInfo->getAllBundleInfo();
      $form['entity_type'] = [
        '#type' => 'select',
        '#title' => $this->t('Entity type'),
        '#description' => $this->t('Entity type mapped.'),
        '#options' => array_combine(array_keys($bundle_info), array_keys($bundle_info)),
        '#required' => TRUE,
        '#weight' => -10,
        '#ajax' => [
          'callback' => '::getBundles',
          'event' => 'change',
          'disable-refocus' => FALSE,
          'wrapper' => 'mapped-bundle',
          'progress' => [
            'type' => 'throbber',
            'message' => NULL,
          ],
        ],
      ];
    }
    else {
      $form['id'] = [
        '#type' => 'markup',
        '#markup' => $this->entity->id(),
      ];

      $entity_type = $this->entity->mappedEntityType();
      $form['entity_type'] = [
        '#type' => 'select',
        '#title' => $this->t('Entity type'),
        '#default_value' => $entity_type,
        '#options' => [$entity_type => $entity_type],
        '#required' => TRUE,
        '#disabled' => TRUE,
      ];
      $bundle = $this->entity->mappedBundle();
      $form['bundle'] = [
        '#type' => 'select',
        '#title' => $this->t('Bundle'),
        '#default_value' => $bundle,
        '#options' => [$bundle => $bundle],
        '#required' => TRUE,
        '#disabled' => TRUE,
      ];
    }

    $form['public_type'] = [
      '#type' => 'select',
      '#title' => $this->t('Open Referral type'),
      '#default_value' => $this->entity->getPublicType() ?: NULL,
      // @todo extend this list as we know they normalize fine.
      //   Move to a central location rather than tucked away here.
      '#options' => [
        'organization' => $this->t('organization'),
        'service' => $this->t('service'),
        'location' => $this->t('location'),
        'taxonomy' => $this->t('taxonomy'),
      ],
      '#required' => TRUE,
    ];

    $form['public_datatype'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Data type'),
      '#description' => $this->t('For taxonomy terms <a href="@curies">Open Referral has guidance</a>.', ['@curies' => 'https://developers.openreferraluk.org/UseOfTaxonomies/#curies-to-use']),
      '#states' => [
        'visible' => [
          ':input[name="public_type"]' => ['value' => 'taxonomy'],
        ],
      ],
      '#size' => 45,
      '#maxlength' => 60,
    ];
    $form_state->setValue('mapping', $this->entity->getMapping('default'));
    $form['update-mapping'] = [
      '#type' => 'submit',
      '#value' => $this->t('Populate mappings'),
      '#submit' => ['::populateMapping'],
      '#ajax' => [
        'callback' => '::refreshMappingAjax',
        'event' => 'click',
        'disable-refocus' => TRUE,
        'wrapper' => 'mapping-table',
        'progress' => [
          'type' => 'throbber',
          'message' => $this->t('Updated'),
        ],
      ],
    ];

    $form['mapping-wrapper'] = [
      '#type' => 'container',
      '#attributes' => ['id' => 'mapping-table'],
      '#title' => $this->t('Field mappings'),
    ];
    $form['add-row'] = [
      '#type' => 'submit',
      '#value' => $this->t('Add row'),
      '#limit_validation_errors' => [],
      '#submit' => ['::addRow'],
      '#ajax' => [
        'callback' => [$this, 'refreshMappingAjax'],
        'event' => 'click',
        'disable-refocus' => TRUE,
        'wrapper' => 'mapping-table',
        'progress' => [
          'type' => 'throbber',
          'message' => NULL,
        ],
      ],
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);

    $user_input = $form_state->getUserInput();
    if ($this->entity->isNew()) {
      $bundle_options = ['' => ''];
      if (!empty($user_input['entity_type'])) {
        $bundle_info = $this->entityBundleInfo->getAllBundleInfo();
        $bundle_options = array_combine(array_keys($bundle_info[$user_input['entity_type']]), array_keys($bundle_info[$user_input['entity_type']]));
      }
      $form['bundle'] = [
        '#type' => 'select',
        '#title' => $this->t('Bundle'),
        '#description' => $this->t('Bundle mapped'),
        '#options' => $bundle_options,
        '#required' => TRUE,
        '#prefix' => '<div id="mapped-bundle">',
        '#suffix' => '</div>',
        '#weight' => -9,
      ];
    }

    if (empty($user_input['mapping'])) {
      $user_input['mapping'] = $this->entity->getMapping('default');
      $user_input['mapping'][] = [
        'field_name' => '',
        'public_name' => '',
      ];
    }
    $form['mapping-wrapper']['mapping'] = [
      '#type' => 'table',
      '#caption' => $this->t('Field mapping'),
      '#header' => [
        $this->t('Drupal field'),
        $this->t('Open Referral property'),
      ],
    ];
    $delta = 0;
    foreach ($user_input['mapping'] as $delta => $mapping) {
      $form['mapping-wrapper']['mapping'][$delta]['field_name'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Drupal field'),
        '#title_display' => 'invisible',
        '#default_value' => $mapping['field_name'],
      ];
      $form['mapping-wrapper']['mapping'][$delta]['public_name'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Open Referral property'),
        '#title_display' => 'invisible',
        '#default_value' => $mapping['public_name'],
      ];
    }

    return $form;
  }

  /**
   * AJAX callback: ::buildForm output for 'bundle'.
   */
  public function getBundles(array &$form, FormStateInterface $form_state) {
    return $form['bundle'];
  }

  /**
   * AJAX callback: ::buildForm output for 'mapping-wrapper'.
   */
  public function refreshMappingAjax(array &$form, FormStateInterface $form_state) {
    return $form['mapping-wrapper'];
  }

  /**
   * Submit handler.
   *
   * Add suggested mappings for entity fields for Open Referral type.
   */
  public function populateMapping(array &$form, FormStateInterface $form_state) {
    $user_input = $form_state->getUserInput();
    $event = new GenerateEntityMapping($form_state->getValue('entity_type'), $form_state->getValue('bundle'), $form_state->getValue('public_type'));
    $event->mapping = array_filter($user_input['mapping'], function ($value) {
      return !(empty($value['field_name']) && empty($value['public_name']));
    });
    $this->eventDispatcher->dispatch($event::GENERATE, $event);
    $user_input['mapping'] = $event->mapping;
    $user_input['mapping'][] = [
      'field_name' => '',
      'public_name' => '',
    ];
    $form_state->setUserInput($user_input);
    $form_state->setRebuild();
  }

  /**
   * Submit handler.
   *
   * Add an empty row to the mapping.
   */
  public function addRow(array &$form, FormStateInterface $form_state) {
    $user_input = $form_state->getUserInput();
    $user_input['mapping'][] = [
      'field_name' => '',
      'public_name' => '',
    ];
    $form_state->setUserInput($user_input);
    $form_state->setRebuild();
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);

    foreach ($form_state->getUserInput()['mapping'] as $delta => $row) {
      if (empty($row['field_name']) != empty($row['public_name'])) {
        if (empty($row['field_name'])) {
          $form_state->setError($form['mapping-wrapper']['mapping'][$delta]['field_name'], $this->t('Drupal Field name required if mapped to a Open Referral property'));
        }
        else {
          $form_state->setError($form['mapping-wrapper']['mapping'][$delta]['public_name'], $this->t('Open Referral property required, or use "_flatten" if should be included in parent entity directly.'));
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    if ($this->entity->isNew() && !empty($form_state->getValue('entity_type')) && !empty($form_state->getValue('bundle'))) {
      $this->entity->setOriginalId($form_state->getValue('entity_type') . '.' . $form_state->getValue('bundle'));
    }
    $result = parent::save($form, $form_state);
    $message_args = ['%label' => $this->entity->label()];
    $message = $result == SAVED_NEW
      ? $this->t('Created new property mapping %label.', $message_args)
      : $this->t('Updated property mapping %label.', $message_args);
    $this->messenger()->addStatus($message);
    $form_state->setRedirectUrl($this->entity->toUrl('collection'));
    return $result;
  }

  /**
   * {@inheritdoc}
   */
  public function buildEntity(array $form, FormStateInterface $form_state) {
    $entity = parent::buildEntity($form, $form_state);

    $mapping = [];
    foreach ($form_state->getValue('mapping') as $row) {
      if (!empty($row['field_name']) && !empty($row['public_name'])) {
        $mapping[] = [
          'field_name' => $row['field_name'],
          'public_name' => $row['public_name'],
        ];
      }
    }
    $entity->setMapping($mapping);

    return $entity;
  }

}
