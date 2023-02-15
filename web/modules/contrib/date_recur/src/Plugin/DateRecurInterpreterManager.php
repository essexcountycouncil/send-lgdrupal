<?php

declare(strict_types = 1);

namespace Drupal\date_recur\Plugin;

use Drupal\Core\Plugin\DefaultPluginManager;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;

//@codingStandardsIgnoreStart
/**
 * Date recur interpreter plugin manager.
 */
// @phpstan-ignore-next-line
class DateRecurInterpreterManager extends DefaultPluginManager implements DateRecurInterpreterManagerInterface {

  /**
   * {@inheritdoc}
   *
   * @param \Traversable<string,string[]> $namespaces
   */
  // @phpstan-ignore-next-line
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler) {
    //@codingStandardsIgnoreEnd
    parent::__construct(
      'Plugin/DateRecurInterpreter',
      $namespaces,
      $module_handler,
      'Drupal\date_recur\Plugin\DateRecurInterpreterPluginInterface',
      'Drupal\date_recur\Annotation\DateRecurInterpreter'
    );
    $this->setCacheBackend($cache_backend, 'date_recur_interpreter_info', ['config:date_recur_interpreter_list']);
  }

}
