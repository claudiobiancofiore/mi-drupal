<?php

declare(strict_types=1);

namespace Drupal\slider_admin;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Access\AccessResultInterface;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Access control for slides.
 *
 * Visible slides can be seen by everyone (they are shown in the public
 * slider). Everything else requires the "administer slider slides"
 * permission.
 */
class SlideAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account): AccessResultInterface {
    $admin = AccessResult::allowedIfHasPermission($account, 'administer slider slides');

    if ($operation === 'view') {
      /** @var \Drupal\slider_admin\SlideInterface $entity */
      $published = AccessResult::allowedIf($entity->isPublished())->addCacheableDependency($entity);
      return $published->orIf($admin);
    }

    return $admin;
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL): AccessResultInterface {
    return AccessResult::allowedIfHasPermission($account, 'administer slider slides');
  }

}
