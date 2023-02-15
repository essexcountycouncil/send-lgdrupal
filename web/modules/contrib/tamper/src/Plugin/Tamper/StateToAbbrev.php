<?php

namespace Drupal\tamper\Plugin\Tamper;

use Drupal\tamper\TamperableItemInterface;
use Drupal\tamper\TamperBase;

/**
 * Plugin implementation for converting state to abbreviation.
 *
 * @Tamper(
 *   id = "state_to_abbrev",
 *   label = @Translation("State to abbrev"),
 *   description = @Translation("Converts this field from a full state name string to the two character abbreviation."),
 *   category = "Text"
 * )
 */
class StateToAbbrev extends TamperBase {

  /**
   * Get the state and abbreviation list.
   *
   * @return array
   *   List of state abbreviations, keyed by lower case state name.
   */
  protected static function getStateList() {
    $states = [
      'alabama' => 'AL',
      'alaska' => 'AK',
      'arizona' => 'AZ',
      'american samoa' => 'AS',
      'arkansas' => 'AR',
      'california' => 'CA',
      'colorado' => 'CO',
      'connecticut' => 'CT',
      'delaware' => 'DE',
      'district of columbia' => 'DC',
      'federated states of micronesia' => 'FM',
      'florida' => 'FL',
      'georgia' => 'GA',
      'guam' => 'GU',
      'hawaii' => 'HI',
      'idaho' => 'ID',
      'illinois' => 'IL',
      'indiana' => 'IN',
      'iowa' => 'IA',
      'kansas' => 'KS',
      'kentucky' => 'KY',
      'louisiana' => 'LA',
      'maine' => 'ME',
      'maryland' => 'MD',
      'massachusetts' => 'MA',
      'marshall islands' => 'MH',
      'michigan' => 'MI',
      'minnesota' => 'MN',
      'mississippi' => 'MS',
      'missouri' => 'MO',
      'montana' => 'MT',
      'nebraska' => 'NE',
      'nevada' => 'NV',
      'new hampshire' => 'NH',
      'new jersey' => 'NJ',
      'new mexico' => 'NM',
      'new york' => 'NY',
      'north carolina' => 'NC',
      'north dakota' => 'ND',
      'northern mariana islands' => 'MP',
      'ohio' => 'OH',
      'oklahoma' => 'OK',
      'oregon' => 'OR',
      'pennsylvania' => 'PA',
      'palau' => 'PW',
      'puerto rico' => 'PR',
      'rhode island' => 'RI',
      'south carolina' => 'SC',
      'south dakota' => 'SD',
      'tennessee' => 'TN',
      'texas' => 'TX',
      'utah' => 'UT',
      'vermont' => 'VT',
      'virginia' => 'VA',
      'virgin islands' => 'VI',
      'washington' => 'WA',
      'west virginia' => 'WV',
      'wisconsin' => 'WI',
      'wyoming' => 'WY',
    ];

    return $states;
  }

  /**
   * {@inheritdoc}
   */
  public function tamper($data, TamperableItemInterface $item = NULL) {

    $states = self::getStateList();

    // If it's already a valid state abbreviation, leave it alone.
    if (in_array($data, $states)) {
      return;
    }

    // Trim whitespace, set to lowercase.
    $state = mb_strtolower(trim($data));

    return isset($states[$state]) ? $states[$state] : '';
  }

}
