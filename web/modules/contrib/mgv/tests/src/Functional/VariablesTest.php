<?php

namespace Drupal\Tests\mgv\Functional;

use Drupal\Core\Url;
use Drupal\Tests\BrowserTestBase;

/**
 * MGV functional test.
 *
 * @group mgv
 */
class VariablesTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['mgv', 'help'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Test variable.
   *
   * @dataProvider variablesDataProvider
   */
  public function testVariable($plugin_id) {
    $this->drupalLogin($this->createUser($this->getUserPermissions()));
    $this->drupalGet(Url::fromRoute('help.page', ['name' => 'mgv']));
    $this->assertSession()
      ->elementContains(
        'css',
        '[data-mgv-id="' . str_replace('\\', '--', $plugin_id) . '"]',
        $this->getValue($plugin_id)
      );
  }

  /**
   * Data provider for ::testVariable().
   *
   * @return string[][]
   *   List of the plugins to test.
   */
  public function variablesDataProvider() {
    $list = [
      'base_url',
      'current_langcode',
      'current_langname',
      'current_page_title',
      'current_path',
      'current_path_alias',
      'raw_current_page_title',
      'logo',
      'site_mail',
      'site_name',
      'site_slogan',
      'social_sharing\email',
      'social_sharing\facebook',
      'social_sharing\linkedin',
      'social_sharing\twitter',
      'social_sharing\whatsapp',
    ];
    $return = [];
    foreach ($list as $id) {
      $return[$id] = [$id];
    }
    return $return;
  }

  /**
   * Value provider.
   *
   * Some values can't be calculated at triggering data provider method.
   *
   * @param string $plugin_id
   *   ID of the variable.
   *
   * @return array|mixed|string|null
   *   Value of the variable.
   */
  public function getValue(string $plugin_id) {
    $value = NULL;
    switch ($plugin_id) {
      case 'base_url':
        /* @see \Drupal\mgv\Plugin\GlobalVariable\BaseUrl::getValue() */
        $value = $this->baseUrl;
        break;

      case 'current_langcode':
        /* @see \Drupal\mgv\Plugin\GlobalVariable\CurrentLangcode::getValue() */
        $value = \Drupal::languageManager()->getCurrentLanguage()->getId();
        break;

      case 'current_langname':
        /* @see \Drupal\mgv\Plugin\GlobalVariable\CurrentLangname::getValue() */
        $value = \Drupal::languageManager()->getCurrentLanguage()->getName();
        break;

      case 'current_page_title':
        /* @see \Drupal\mgv\Plugin\GlobalVariable\CurrentPageTitle::getValue() */
      case 'raw_current_page_title':
        /* @see \Drupal\mgv\Plugin\GlobalVariable\RawCurrentPageTitle::getValue() */
        $value = 'Help';
        break;

      case 'current_path':
        /* @see \Drupal\mgv\Plugin\GlobalVariable\CurrentPath::getValue() */
        $value = \Drupal::service('path.current')->getPath();
        break;

      case 'current_path_alias':
        /* @see \Drupal\mgv\Plugin\GlobalVariable\CurrentPathAlias::getValue() */
        $value = \Drupal::service('path_alias.manager')->getAliasByPath(
          \Drupal::service('path.current')->getPath()
        );
        break;

      case 'logo':
        /* @see \Drupal\mgv\Plugin\GlobalVariable\SiteLogo::getValue() */
        $theme_name = \Drupal::theme()->getActiveTheme()->getName();
        $value = theme_get_setting('logo.url', $theme_name);
        break;

      case 'site_mail':
        /* @see \Drupal\mgv\Plugin\GlobalVariable\SiteMail::getValue() */
        $value = \Drupal::config('system.site')->get('mail');
        break;

      case 'site_name':
        /* @see \Drupal\mgv\Plugin\GlobalVariable\SiteName::getValue() */
        $value = \Drupal::config('system.site')->get('name');
        break;

      case 'site_slogan':
        /* @see \Drupal\mgv\Plugin\GlobalVariable\SiteSlogan::getValue() */
        $value = \Drupal::config('system.site')->get('slogan');
        break;

      case 'social_sharing\email':
        /* @see \Drupal\mgv\Plugin\GlobalVariable\SocialSharingEmail::getValue() */
        $url = Url::fromUri($this->baseUrl . '/admin/help/mgv');
        $query = [
          'subject' => 'Help',
          'body' => 'Check this out from Drupal: ' . $url->toString(),
        ];
        $value = htmlentities(Url::fromUri('mailto:', ['query' => $query])->toString());
        break;

      case 'social_sharing\facebook':
        /* @see \Drupal\mgv\Plugin\GlobalVariable\SocialSharingFacebook::getValue() */
        $url = str_replace(':', '%3A', $this->baseUrl);
        $value = 'https://www.facebook.com/sharer.php?u=' . $url . '/admin/help/mgv&amp;text=Help';
        break;

      case 'social_sharing\linkedin':
        /* @see \Drupal\mgv\Plugin\GlobalVariable\SocialSharingLinkedin::getValue() */
        $url = str_replace(':', '%3A', $this->baseUrl);
        $value = 'https://www.linkedin.com/shareArticle?mini=true&amp;url=' . $url . '/admin/help/mgv&amp;title=Help&amp;source=Drupal';
        break;

      case 'social_sharing\twitter':
        /* @see \Drupal\mgv\Plugin\GlobalVariable\SocialSharingTwitter::getValue() */
        $url = str_replace(':', '%3A', $this->baseUrl);
        $value = 'https://twitter.com/intent/tweet?url=' . $url . '/admin/help/mgv&amp;text=Help';
        break;

      case 'social_sharing\whatsapp':
        /* @see \Drupal\mgv\Plugin\GlobalVariable\SocialSharingWhatsapp::getValue() */
        $url = str_replace(':', '%3A', $this->baseUrl);
        $value = 'whatsapp://send?text=Help%20-%20' . $url . '/admin/help/mgv';
        break;

      default;
    }
    return $value;
  }

  /**
   * Test is query params are present in link for sharing via email plugin.
   */
  public function testEmailQueryTrimIssue() {
    $this->drupalLogin($this->createUser($this->getUserPermissions()));
    $url = Url::fromRoute('help.page', ['name' => 'mgv'], [
      'query' => [
        'filter1' => 'test',
      ],
    ]);
    $this->drupalGet($url);
    $query = [
      'subject' => 'Help',
      'body' => 'Check this out from Drupal: ' . $url->toString(),
    ];
    $expect = Url::fromUri('mailto:', ['query' => $query])->toString();
    $this->assertSession()
      ->elementContains(
        'css',
        '[data-mgv-id="social_sharing--email"]',
        htmlentities($expect)
      );
  }

  /**
   * User permissions needed for testing.
   *
   * @return string[]
   *   Permissions list.
   */
  protected function getUserPermissions() {
    $permissions = ['access administration pages'];
    if (version_compare(\Drupal::VERSION, '10.2.0', '>=')) {
      $permissions += ['access help pages'];
    }
    return $permissions;
  }

}
