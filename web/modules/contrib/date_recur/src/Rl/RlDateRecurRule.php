<?php

declare(strict_types = 1);

namespace Drupal\date_recur\Rl;

use Drupal\date_recur\DateRecurRuleInterface;

/**
 * RRule object.
 *
 * @ingroup RLanvinPhpRrule
 */
final class RlDateRecurRule implements DateRecurRuleInterface {

  /**
   * Creates a new RlDateRecurRule.
   *
   * @param array $parts
   *   The parts for this rule.
   *
   * @internal constructor subject to change at any time. Creating
   *   RlDateRecurRule objects is reserved by date_recur module.
   */
  public function __construct(protected array $parts) {
    $this->parts['FREQ'] ?? throw new \Exception('Frequency must be defined.');
  }

  /**
   * {@inheritdoc}
   */
  public function getFrequency(): string {
    return $this->parts['FREQ'];
  }

  /**
   * {@inheritdoc}
   */
  public function getParts(): array {
    return $this->parts;
  }

}
