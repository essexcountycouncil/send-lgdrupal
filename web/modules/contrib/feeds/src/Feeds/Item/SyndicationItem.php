<?php

namespace Drupal\feeds\Feeds\Item;

// The class variable names in this file need to have underscores because their
// names need to match with the mapping source names defined in
// \Drupal\feeds\Feeds\Parser\SyndicationParser::getMappingSources().
// phpcs:disable Drupal.NamingConventions.ValidVariableName.LowerCamelName

/**
 * Defines an item class for use with an RSS/Atom parser.
 *
 * @see \Drupal\feeds\Feeds\Parser\SyndicationParser
 */
class SyndicationItem extends BaseItem {

  /**
   * Title of the feed item.
   *
   * @var string
   */
  protected $title;

  /**
   * Parsed item content.
   *
   * @var string
   */
  protected $content;

  /**
   * Parsed item description.
   *
   * @var string
   */
  protected $description;

  /**
   * Name of the feed item's author.
   *
   * @var string
   */
  protected $author_name;

  /**
   * Mail address of the feed item's author.
   *
   * @var string
   */
  protected $author_email;

  /**
   * Published date as UNIX time GMT.
   *
   * @var int
   */
  protected $timestamp;

  /**
   * Updated date as UNIX time GMT.
   *
   * @var int
   */
  protected $updated;

  /**
   * URL of the feed item.
   *
   * @var string
   */
  protected $url;

  /**
   * Global Unique Identifier.
   *
   * @var string
   */
  protected $guid;

  /**
   * An array of categories that have been assigned to the feed item.
   *
   * @var array
   */
  protected $tags;

  /**
   * The feed item latitude.
   *
   * @var float
   */
  protected $georss_lat;

  /**
   * The feed item longitude.
   *
   * @var float
   */
  protected $georss_lon;

  /**
   * A list of enclosures attached to the feed item.
   *
   * @var array
   */
  protected $enclosures;

  /**
   * Title of the feed.
   *
   * @var string
   */
  protected $feed_title;

  /**
   * Description of the feed.
   *
   * @var string
   */
  protected $feed_description;

  /**
   * URL of the feed.
   *
   * @var string
   */
  protected $feed_url;

  /**
   * The URL of the feed image.
   *
   * @var string
   */
  protected $feed_image_uri;

  /**
   * Url to a media file.
   *
   * The file can be audio, video or other media.
   *
   * @var string
   */
  protected $mediarss_content;

  /**
   * Text that describes the media object.
   *
   * @var string
   */
  protected $mediarss_description;

  /**
   * Url to an image that is representative for the media object.
   *
   * @var string
   */
  protected $mediarss_thumbnail;

}
