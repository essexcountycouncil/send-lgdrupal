<?php

declare(strict_types = 1);

namespace Drupal\scheduled_transitions;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Hooks for Scheduled Transitions module.
 */
class ScheduledTransitionsHooks implements ContainerInjectionInterface {

  /**
   * Constructs a new ScheduledTransitionsHooks.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   The config factory.
   * @param \Drupal\scheduled_transitions\ScheduledTransitionsJobsInterface $scheduledTransitionsJobs
   *   Job runner for Scheduled Transitions.
   */
  public function __construct(protected ConfigFactoryInterface $configFactory, protected ScheduledTransitionsJobsInterface $scheduledTransitionsJobs) {
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('scheduled_transitions.jobs'),
    );
  }

  /**
   * Implements hook_cron().
   *
   * @see \scheduled_transitions_cron()
   */
  public function cron(): void {
    $settings = $this->configFactory->get('scheduled_transitions.settings');
    if (!empty($settings->get('automation.cron_create_queue_items'))) {
      $this->scheduledTransitionsJobs->jobCreator();
    }
  }

}
