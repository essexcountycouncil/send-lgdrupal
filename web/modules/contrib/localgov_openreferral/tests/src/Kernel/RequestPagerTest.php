<?php

namespace Drupal\Tests\localgov_openreferral\Kernel;

use Drupal\KernelTests\Core\Pager\RequestPagerTest as CoreRequestPagerTest;
use Symfony\Component\HttpFoundation\Request;

/**
 * Tests decorated Pager Parameters.
 *
 * @group localgov_openreferral
 */
class RequestPagerTest extends CoreRequestPagerTest {

  /**
   * Modules to install.
   *
   * @var array
   */
  protected static $modules = ['localgov_openreferral', 'serialization'];

  /**
   * Test decoration of ::findPage.
   */
  public function testOpenreferralFindPage() {
    $request = Request::create('http://example.com/openreferral/v1/services', 'GET', ['page' => '1,10']);

    /** @var \Symfony\Component\HttpFoundation\RequestStack $request_stack */
    $request_stack = $this->container->get('request_stack');
    $request_stack->push($request);

    $pager_params = $this->container->get('pager.parameters');

    $this->assertEquals(0, $pager_params->findPage(0));
  }

}
