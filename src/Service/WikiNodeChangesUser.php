<?php

namespace Drupal\omnipedia_content\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\omnipedia_content\Service\WikiNodeChangesUserInterface;
use Drupal\omnipedia_core\Entity\NodeInterface;
use Drupal\user\UserInterface;

/**
 * The Omnipedia wiki node changes user service.
 */
class WikiNodeChangesUser implements WikiNodeChangesUserInterface {

  /**
   * All user role entities, keyed by role ID (rid).
   *
   * @var \Drupal\user\RoleInterface[]
   */
  protected $allRoles;

  /**
   * The Drupal user role entity storage.
   *
   * @var \Drupal\user\RoleStorageInterface
   */
  protected $roleStorage;

  /**
   * The Drupal user entity storage.
   *
   * @var \Drupal\user\UserStorageInterface
   */
  protected $userStorage;


  /**
   * Constructs this service object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The Drupal entity type manager.
   */
  public function __construct(EntityTypeManagerInterface $entityTypeManager) {

    // Save dependencies.
    $this->roleStorage = $entityTypeManager->getStorage('user_role');
    $this->userStorage = $entityTypeManager->getStorage('user');

  }

  /**
   * {@inheritdoc}
   */
  public function getUserToRenderAs(
    array $roles, NodeInterface $node, NodeInterface $previousNode
  ): ?UserInterface {

    if (!isset($this->allRoles)) {
      /** @var \Drupal\user\RoleInterface[] */
      $this->allRoles = $this->roleStorage->loadMultiple();
    }

    /** @var array */
    $excludeRoles = \array_diff(\array_keys($this->allRoles), $roles);

    // This builds and executes a \Drupal\Core\Entity\Query\QueryInterface to
    // get all active users that have the provided roles and not the excluded
    // roles.
    /** @var array */
    $uids = ($this->userStorage->getQuery())
      ->condition('status', 1)
      ->condition('roles', $roles, 'IN')
      ->condition('roles', $excludeRoles, 'NOT IN')
      ->execute();

    if (empty($uids)) {
      return null;
    }

    foreach ($uids as $uid) {

      /** @var \Drupal\user\UserInterface */
      $user = $this->userStorage->load($uid);

      if (
        $node->access('view', $user) &&
        $previousNode->access('view', $user)
      ) {
        return $user;
      }

    }

    return null;

  }

}
