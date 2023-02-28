<?php

declare(strict_types=1);

namespace Drupal\entity_hierarchy_microsite;

use Drupal\Core\DestructableInterface;
use Drupal\Core\Menu\MenuLinkManagerInterface;

/**
 * Defines a class for rebuild microsite menus.
 */
final class MenuRebuildProcessor implements DestructableInterface {

  /**
   * TRUE if needs rebuild.
   *
   * @var bool
   */
  protected $needsRebuild = FALSE;

  /**
   * Menu link manager.
   *
   * @var \Drupal\Core\Menu\MenuLinkManagerInterface
   */
  protected $menuLinkManager;

  /**
   * Constructs a new MenuRebuildProcessor.
   *
   * @param \Drupal\Core\Menu\MenuLinkManagerInterface $menuLinkManager
   *   Menu link manager.
   */
  public function __construct(MenuLinkManagerInterface $menuLinkManager) {
    $this->menuLinkManager = $menuLinkManager;
  }

  /**
   * {@inheritdoc}
   */
  public function destruct(): void {
    if ($this->needsRebuild) {
      $this->menuLinkManager->rebuild();
      $this->needsRebuild = FALSE;
    }
  }

  /**
   * Marks rebuild as needed.
   *
   * @return $this
   */
  public function markRebuildNeeded(): self {
    $this->needsRebuild = TRUE;
    return $this;
  }

}
