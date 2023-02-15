<?php

declare(strict_types = 1);

namespace Drupal\Tests\scheduled_transitions\Unit;

use Drupal\content_moderation\ModerationInformationInterface;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\ImmutableConfig;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\RevisionableStorageInterface;
use Drupal\Core\Entity\RevisionLogInterface;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\Core\Utility\Token;
use Drupal\scheduled_transitions\Entity\ScheduledTransitionInterface;
use Drupal\scheduled_transitions\ScheduledTransitionsUtility;
use Drupal\Tests\UnitTestCase;

/**
 * Tests scheduled transactions utility.
 *
 * @coversDefaultClass \Drupal\scheduled_transitions\ScheduledTransitionsUtility
 * @group scheduled_transitions
 */
class ScheduledTransitionsUtilityUnitTest extends UnitTestCase {

  /**
   * Tests revision log generator.
   *
   * @param string $transitioningRevisionId
   *   Revision ID of the transitioning revision.
   * @param string $latestRevisionId
   *   Revision ID of the latest revision of an entity..
   * @param array $options
   *   Scheduled transitions entity options.
   * @param string $expectedRevisionLog
   *   The expected revision log.
   *
   * @covers ::generateRevisionLog
   * @dataProvider providerGenerateRevisionLog
   */
  public function testGenerateRevisionLog(string $transitioningRevisionId, string $latestRevisionId, array $options, string $expectedRevisionLog): void {
    $configFactory = $this->createMock(ConfigFactoryInterface::class);

    $settings = $this->createMock(ImmutableConfig::class);
    $settings->expects($this->any())
      ->method('get')
      ->willReturnMap([
        ['message_transition_latest', 'template for latest revision'],
        ['message_transition_historical', 'template for historical revision'],
      ]);
    $configFactory->expects($this->any())
      ->method('get')
      ->with('scheduled_transitions.settings')
      ->willReturn($settings);

    $cache = $this->createMock(CacheBackendInterface::class);
    $entityTypeManager = $this->createMock(EntityTypeManagerInterface::class);
    $entityStorage = $this->createMock(RevisionableStorageInterface::class);
    $latest = $this->createMock(RevisionLogInterface::class);
    $latest->expects($this->any())
      ->method('getRevisionId')
      ->willReturn($latestRevisionId);
    $entityStorage->expects($this->once())
      ->method('getLatestRevisionId')
      ->with('1337')
      ->willReturn('2000');
    $entityStorage->expects($this->once())
      ->method('loadRevision')
      ->with('2000')
      ->willReturn($latest);
    $entityTypeManager->expects($this->once())
      ->method('getStorage')
      ->with('test_entity_type')
      ->willReturn($entityStorage);
    $bundleInfo = $this->createMock(EntityTypeBundleInfoInterface::class);
    $moderationInformation = $this->createMock(ModerationInformationInterface::class);
    $token = $this->createMock(Token::class);
    $token->expects($this->once())
      ->method('replace')
      ->willReturnArgument(0);
    $translation = $this->createMock(TranslationInterface::class);
    $utility = new ScheduledTransitionsUtility(
      $configFactory,
      $cache,
      $entityTypeManager,
      $bundleInfo,
      $moderationInformation,
      $token,
      $translation
    );

    $scheduledTransition = $this->createMock(ScheduledTransitionInterface::class);
    $scheduledTransition->expects($this->once())
      ->method('getOptions')
      ->willReturn($options);
    $newRevision = $this->createMock(RevisionLogInterface::class);
    $newRevision->expects($this->once())
      ->method('id')
      ->willReturn(1337);
    $newRevision->expects($this->once())
      ->method('getEntityTypeId')
      ->willReturn('test_entity_type');
    $newRevision->expects($this->any())
      ->method('getRevisionId')
      ->willReturn($transitioningRevisionId);

    $this->assertEquals(
      $expectedRevisionLog,
      $utility->generateRevisionLog($scheduledTransition, $newRevision)
    );
  }

  /**
   * Data provider for testGenerateRevisionLog.
   */
  public function providerGenerateRevisionLog(): array {
    $scenarios = [];

    $scenarios['historical'] = [
      // Transitioning different revisions.
      '333',
      '444',
      [],
      'template for historical revision',
    ];

    $scenarios['latest'] = [
      // Revision IDs are the same:
      '444',
      '444',
      [],
      'template for latest revision',
    ];

    $scenarios['custom'] = [
      // Revision IDs are irrelevant.
      '444',
      '444',
      [
        'revision_log_override' => TRUE,
        'revision_log' => 'custom revision log',
      ],
      'custom revision log',
    ];

    return $scenarios;
  }

}
