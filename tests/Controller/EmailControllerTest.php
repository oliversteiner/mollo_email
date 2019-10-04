<?php

namespace Drupal\mollo_email\Tests;

use Drupal\simpletest\WebTestBase;

/**
 * Provides automated tests for the mollo_email module.
 */
class EmailControllerTest extends WebTestBase {


  /**
   * {@inheritdoc}
   */
  public static function getInfo() {
    return [
      'name' => "mollo_email EmailController's controller functionality",
      'description' => 'Test Unit for module mollo_email and controller EmailController.',
      'group' => 'Other',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();
  }

  /**
   * Tests mollo_email functionality.
   */
  public function testEmailController() {
    // Check that the basic functions of module mollo_email.
    $this->assertEquals(TRUE, TRUE, 'Test Unit Generated via Drupal Console.');
  }

}
