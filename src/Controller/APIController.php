<?php

namespace Drupal\mollo_email\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\mollo_email\Utility\ImapTrait;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Class APIController.
 */
class APIController extends ControllerBase
{

  use ImapTrait;

  public function list():JsonResponse{
    $name = 'list';
    $base = '/api/email/';
    $version = '1.0.0';

    $emails = self::listFromImap();

    $response = [
      'name' => $base.$name,
      'version' => $version,
      'emails' => $emails
    ];

    return new JsonResponse($response);

  }


  /**
   * @return JsonResponse
   */
  public function invalid(): JsonResponse
  {
    $name = 'invalid';
    $base = '/api/email/';
    $version = '1.0.0';
  //  $addresses = ['info@test.com', 'example@example.com'];

    $addresses = self::getAllInvalidAddresses();

    $response = [
      'name' => $base.$name,
      'version' => $version,
      'addresses' => $addresses
    ];

    return new JsonResponse($response);
  }

}
