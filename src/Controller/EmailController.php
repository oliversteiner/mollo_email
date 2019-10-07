<?php

namespace Drupal\mollo_email\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\mollo_email\Utility\ImapTrait;

/**
 * Class EmailController.
 */
class EmailController extends ControllerBase
{
  use ImapTrait;

  public static function getInvalidAddresses(): array
  {
    $base = '/api/email/';
    $dir = DRUPAL_ROOT.'/mails/';

    //  $addresses = ['info@test.com', 'example@example.com'];

    $addresses_IMAP = self::getAllInvalidAddresses();
    [$addresses_FIELES, $exclude_list] = self::getEmailsFromDirectory($dir);
    $addresses =array_merge($addresses_FIELES, $addresses_IMAP);

    return $addresses;
  }
}
