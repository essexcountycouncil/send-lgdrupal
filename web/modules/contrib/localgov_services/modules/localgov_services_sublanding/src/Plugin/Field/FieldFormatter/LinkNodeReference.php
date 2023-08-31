<?php

namespace Drupal\localgov_services_sublanding\Plugin\Field\FieldFormatter;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Cache\CacheableDependencyInterface;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Entity\EntityDisplayRepositoryInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityPublishedInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Url;
use Drupal\link\Plugin\Field\FieldType\LinkItem;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Link to node reference field formatter.
 *
 * @package Drupal\localgov_services_sublanding\Plugin\Field\FieldFormatter
 *
 * @FieldFormatter(
 *   id = "link_node_reference",
 *   module = "localgov_services_sublanding",
 *   label = @Translation("Node reference"),
 *   field_types = {
 *     "link"
 *   }
 * )
 */
class LinkNodeReference extends FormatterBase implements ContainerFactoryPluginInterface {

  /**
   * Entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  private $entityTypeManager;


  /**
   * The entity display repository.
   *
   * @var \Drupal\Core\Entity\EntityDisplayRepositoryInterface
   */
  protected $entityDisplayRepository;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $plugin_id,
      $plugin_definition,
      $configuration['field_definition'],
      $configuration['settings'],
      $configuration['label'],
      $configuration['view_mode'],
      $configuration['third_party_settings'],
      $container->get('entity_type.manager'),
      $container->get('entity_display.repository')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, $label, $view_mode, array $third_party_settings, EntityTypeManagerInterface $entityTypeManager, EntityDisplayRepositoryInterface $entityDisplayRepository) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $label, $view_mode, $third_party_settings);
    $this->entityTypeManager = $entityTypeManager;
    $this->entityDisplayRepository = $entityDisplayRepository;
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'view_mode' => 'teaser',
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $build = [];

    foreach ($items as $item) {
      if ($item->isExternal()) {
        $build[] = $this->buildExternal($item);
      }
      else {
        [$is_published, $render_array] = $this->buildInternal($item, $langcode);

        if ($is_published) {
          $build[] = $render_array;
        }
        else {
          // Published or not, cache tags remain relevant for all linked pages.
          CacheableMetadata::createFromRenderArray($build)
            ->merge(CacheableMetadata::createFromRenderArray($render_array))
            ->applyTo($build);
        }
      }
    }

    return $build;
  }

  /**
   * Build the render array for external links.
   *
   * @param \Drupal\link\Plugin\Field\FieldType\LinkItem $item
   *   Link item to render.
   *
   * @return array
   *   Render array.
   */
  private function buildExternal(LinkItem $item) {
    return [
      '#theme' => 'dummy_teaser',
      '#title' => $item->getValue()['title'],
      '#url' => Url::fromUri($item->getValue()['uri']),
    ];
  }

  /**
   * Build the render array for internal links.
   *
   * @param \Drupal\link\Plugin\Field\FieldType\LinkItem $item
   *   Link item to render.
   * @param string $langcode
   *   Language code.
   *
   * @return array
   *   First item: (bool) Is this a published link?; Last item: Render array.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   */
  private function buildInternal(LinkItem $item, $langcode) {
    try {
      $url = $item->getUrl();
      // Test if this is an entity route we can understand.
      // It's all convention, but if it doesn't meet the test we safely render
      // normally.
      $entity = NULL;
      if ($url->isRouted()) {
        $matches = [];
        if (preg_match('/entity\.([a-z0-9_]+)\.[a-z0-9_]+/', $url->getRouteName(), $matches)) {
          $entity_type = $matches[1];
          $params = $item->getUrl()->getRouteParameters();
          if (isset($params[$entity_type]) && !empty($params[$entity_type])) {
            $entity = $this->entityTypeManager->getStorage($entity_type)->load($params[$entity_type]);
          }
        }
      }
      if ($entity) {
        return $this->buildEntityLink($entity);
      }
      else {
        $render_array = [
          '#theme' => 'dummy_teaser',
          '#title' => $item->getValue()['title'],
          '#url' => $url,
        ];
        return [$url->access(), $render_array];
      }
    }
    // Fallback to buildExternal() if the internal route is not valid.
    catch (\UnexpectedValueException $exception) {
      return [TRUE, $this->buildExternal($item)];
    }
  }

  /**
   * Build an entity link.
   */
  private function buildEntityLink(EntityInterface $entity) {
    if ($entity and $entity->access('view')) {
      $view_builder = $this->entityTypeManager->getViewBuilder($entity->getEntityTypeId());
      $render_array = $view_builder->view($entity, $this->getSetting('view_mode'), $entity->language()->getId());

      if ($entity instanceof EntityPublishedInterface and !$entity->isPublished()) {
        $render_array['#attributes']['class'][] = 'localgov-services-sublanding-child-entity--unpublished';
        $render_array['#attached']['library'][] = 'localgov_services_sublanding/child_pages';
        $render_array['#cache']['contexts'][] = 'url';
      }

      if ($entity instanceof CacheableDependencyInterface) {
        $render_array['#cache']['tags'] = $render_array['#cache']['tags'] ?? [];
        $render_array['#cache']['tags'] = Cache::mergeTags($render_array['#cache']['tags'], $entity->getCacheTags());
      }
      return [TRUE, $render_array];
    }
    elseif ($entity and !$entity->access('view') and ($entity instanceof CacheableDependencyInterface)) {
      // Keep track of the entity; it may become accessible later.
      $render_array['#cache']['tags'] = $entity->getCacheTags();
      return [FALSE, $render_array];
    }
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $form['view_mode'] = [
      '#title' => $this->t('View Mode'),
      '#type' => 'select',
      '#options' => $this->entityDisplayRepository->getViewModeOptions('node'),
      '#default_value' => $this->getSetting('view_mode'),
    ];
    return $form;
  }

}
