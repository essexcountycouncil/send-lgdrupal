<?php

namespace Drupal\responsive_preview;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\InvokeCommand;
use Drupal\Core\Ajax\SettingsCommand;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Path\CurrentPathStack;
use Drupal\Core\Routing\AdminContext;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Url;
use Drupal\node\NodeForm;
use Drupal\node\NodeInterface;
use Drupal\node\NodeTypeInterface;
use Drupal\responsive_preview\Entity\Device;

/**
 * Responsive preview service.
 */
class ResponsivePreview {

  use StringTranslationTrait;

  /**
   * Admin context service.
   *
   * @var \Drupal\Core\Routing\AdminContext
   */
  protected $routerAdminContext;

  /**
   * The current path.
   *
   * @var \Drupal\Core\Path\CurrentPathStack
   */
  protected $currentPathStack;

  /**
   * The route match service.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $routeMatch;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;

  /**
   * ResponsivePreview constructor.
   *
   * @param \Drupal\Core\Routing\AdminContext $adminContext
   *   Admin context service.
   * @param \Drupal\Core\Path\CurrentPathStack $currentPathStack
   *   CurrentPathStack service to get the path.
   * @param \Drupal\Core\Routing\RouteMatchInterface $routeMatch
   *   The route match service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   * @param \Drupal\Core\Session\AccountProxyInterface $currentUser
   *   The current user.
   */
  public function __construct(AdminContext $adminContext, CurrentPathStack $currentPathStack, RouteMatchInterface $routeMatch, EntityTypeManagerInterface $entityTypeManager, AccountProxyInterface $currentUser) {
    $this->routerAdminContext = $adminContext;
    $this->currentPathStack = $currentPathStack;
    $this->routeMatch = $routeMatch;
    $this->entityTypeManager = $entityTypeManager;
    $this->currentUser = $currentUser;
  }

  /**
   * Determines wether the responsive preview should be shown and returns the
   * url for the preview.
   */
  public function getPreviewUrl() {
    // Determine if responsive preview should be available for this node type.
    if ($this->routeMatch->getRouteName() === 'node.add') {
      $node_type = $this->routeMatch->getParameter('node_type');
      if ($node_type && !$this->previewEnabled($node_type)) {
        return NULL;
      }
      return '/';
    }
    elseif ($form = $this->routeMatch->getRouteObject()->getDefault("_entity_form")) {
      $entity_type_id = current(explode('.', $form));
      /** @var \Drupal\Core\Entity\EntityInterface $entity */
      if (!($entity = $this->routeMatch->getParameter($entity_type_id))) {
        return NULL;
      }
      if ($entity instanceof NodeInterface && !$this->previewEnabled($entity->type->entity)) {
        return NULL;
      }
      if ($entity->hasLinkTemplate('canonical')) {
        return $entity->toUrl()->toString();
      }
    }
    if (!$this->routerAdminContext->isAdminRoute()) {
      return $this->currentPathStack->getPath();
    }
    return NULL;
  }

  /**
   * Preview is enabled.
   *
   * @param \Drupal\node\NodeTypeInterface $node_type
   *   The node type.
   *
   * @return bool
   *   TRUE if preview mode is not disabled.
   */
  public function previewEnabled(NodeTypeInterface $node_type) {
    return !($node_type->getPreviewMode() === DRUPAL_DISABLED);
  }

  /**
   * Returns an array of enabled devices, suitable for rendering.
   *
   * @return array
   *   A render array of enabled devices.
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function getRenderableDevicesList() {
    $links = [];

    /** @var \Drupal\responsive_preview\Entity\Device[] $devices */
    $devices = $this->entityTypeManager
      ->getStorage('responsive_preview_device')
      ->loadByProperties(['status' => 1]);

    uasort($devices, [Device::class, 'sort']);

    foreach ($devices as $name => $entity) {
      $dimensions = $entity->getDimensions();
      $links[$name] = [
        '#type' => 'html_tag',
        '#tag' => 'button',
        '#value' => $entity->label(),
        '#attributes' => [
          'data-responsive-preview-name' => $name,
          'data-responsive-preview-width' => $dimensions['width'],
          'data-responsive-preview-height' => $dimensions['height'],
          'data-responsive-preview-dppx' => $dimensions['dppx'],
          'class' => [
            'responsive-preview-device',
            'responsive-preview-icon',
            'responsive-preview-icon-active',
          ],
        ],
      ];
    }

    // Add a configuration link.
    $links['configure_link'] = [
      '#type' => 'link',
      '#title' => $this->t('Configure devices'),
      '#url' => Url::fromRoute('entity.responsive_preview_device.collection'),
      '#access' => $this->currentUser->hasPermission('administer responsive preview'),
      '#attributes' => [
        'class' => ['responsive-preview-configure'],
      ],
    ];

    return [
      '#theme' => 'item_list__responsive_preview',
      '#items' => $links,
      '#attributes' => [
        'class' => ['responsive-preview-options'],
      ],
      '#wrapper_attributes' => [
        'class' => ['responsive-preview-item-list'],
      ],
    ];
  }

  /**
   * Handling of form alter, for responsive preview.
   *
   * Request to this method is piped from module related hook_form_alter().
   *
   * @param array $form
   *   Form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Form state object.
   * @param string $form_id
   *   Form ID.
   */
  public function formAlter(array &$form, FormStateInterface $form_state, $form_id) {
    if (!$form_state->getFormObject() instanceof NodeForm) {
      return;
    }

    /** @var \Drupal\Core\Entity\Entity $entity */
    $node = $form_state->getFormObject()->getEntity();

    if ($node instanceof NodeInterface) {
      $preview_mode = $node->type->entity->getPreviewMode();

      $form['ajax_responsive_preview'] = [
        '#type' => 'hidden',
        '#name' => 'ajax_responsive_preview',
        '#id' => 'ajax_responsive_preview',
        '#attributes' => ['id' => 'ajax_responsive_preview'],
        '#access' => $preview_mode != DRUPAL_DISABLED && ($node->access('create') || $node->access('update')),
        '#submit' => $form['actions']['preview']['#submit'],
        '#executes_submit_callback' => TRUE,
        '#ajax' => [
          'callback' => [
            __CLASS__,
            'handleAjaxDevicePreview',
          ],
          'event' => 'show-responsive-preview',
          'progress' => [
            'type' => 'fullscreen',
          ],
        ],
        '#attached' => [
          'drupalSettings' => [
            'responsive_preview' => [
              'ajax_responsive_preview' => '#ajax_responsive_preview',
            ],
          ],
        ],
      ];
    }
  }

  /**
   * Handles response for AJAX request.
   *
   * @param array $form
   *   From array object.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Form state.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse|array
   *   Returns AJAX response object.
   */
  public static function handleAjaxDevicePreview(array $form, FormStateInterface $form_state) {
    // If there are no errors and everything is fine, then result for opening
    // responsive preview will be generated.
    $ajax = new AjaxResponse();

    // Handling of errors is a bit tricky and here is workaround with triggering
    // of normal preview functionality.
    if (count($form_state->getErrors()) > 0) {
      // Clearing error messages, because they will be generated by clicking on
      // "Preview" button.
      \Drupal::messenger()->deleteAll();

      // Triggering click on "Preview" button, in order to get error messages
      // properly displayed in UI, since it's not possible to propagate them
      // nicely over AJAX response.
      $ajax->addCommand(
        new InvokeCommand('#edit-preview', 'click')
      );
    }
    elseif (($triggering_element = $form_state->getTriggeringElement()) && $triggering_element['#name'] === 'ajax_responsive_preview') {
      $form_state->disableRedirect(FALSE);
      $redirectUrl = $form_state->getRedirect();
      $form_state->disableRedirect();

      $ajax->addCommand(
        new SettingsCommand(
          [
            'responsive_preview' => [
              'url' => ltrim($redirectUrl->toString(), '/'),
            ],
          ],
          TRUE
        ),
        TRUE
      );

      $deviceId = $form_state->getValue($triggering_element['#name']);
      $ajax->addCommand(
        new InvokeCommand("[data-responsive-preview-name='{$deviceId}']", 'trigger', ['open-preview'])
      );
    }

    return $ajax;
  }

  /**
   * Implements hook_toolbar().
   */
  public function previewToolbar() {
    $items = [];
    $items['responsive_preview'] = [
      '#cache' => [
        'contexts' => [
          'user.permissions',
        ],
      ],
    ];

    if (!$this->currentUser->hasPermission('access responsive preview')) {
      return $items;
    }
    $device_definition = $this->entityTypeManager->getDefinition('responsive_preview_device');

    $items['responsive_preview']['#cache']['tags'] = Cache::mergeTags(
      $device_definition->getListCacheTags(),
      ['config:node_type_list'],
    );
    $items['responsive_preview']['#cache']['contexts'] = Cache::mergeContexts(
      $items['responsive_preview']['#cache']['contexts'],
      ['route.is_admin', 'url'],
    );

    $url = $this->getPreviewUrl();
    if ($url) {
      $items['responsive_preview'] += [
        '#type' => 'toolbar_item',
        '#weight' => 50,
        'tab' => [
          'trigger' => [
            '#type' => 'html_tag',
            '#tag' => 'button',
            '#value' => $this->t('<span class="visually-hidden">Layout preview</span>'),
            '#attributes' => [
              'title' => $this->t('Preview page layout'),
              'class' => [
                'responsive-preview-icon',
                'responsive-preview-icon-responsive-preview',
                'responsive-preview-trigger',
              ],
            ],
          ],
          'device_options' => $this->getRenderableDevicesList(),
        ],
        '#wrapper_attributes' => [
          'id' => 'responsive-preview-toolbar-tab',
          'class' => ['toolbar-tab-responsive-preview'],
        ],
        '#attached' => [
          'library' => ['responsive_preview/drupal.responsive-preview'],
          'drupalSettings' => [
            'responsive_preview' => [
              'url' => ltrim($url, '/'),
            ],
          ],
        ],
      ];
    }

    return $items;
  }

}
