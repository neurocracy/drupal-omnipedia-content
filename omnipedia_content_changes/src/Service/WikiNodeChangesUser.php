<?php

namespace Drupal\omnipedia_content_changes\Service;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Session\PermissionsHashGeneratorInterface;
use Drupal\omnipedia_content_changes\Service\WikiNodeChangesUserInterface;
use Drupal\omnipedia_core\Entity\NodeInterface;
use Drupal\user\UserInterface;

/**
 * The Omnipedia wiki node changes user service.
 */
class WikiNodeChangesUser implements WikiNodeChangesUserInterface {

  /**
   * The cache bin name where user permission hashes are stored.
   *
   * @var string
   */
  protected const USER_PERMISSION_HASHES_CACHE_BIN =
    'omnipedia_content_changes_user_permission_hashes';

  /**
   * The default Drupal cache bin.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  protected $defaultCache;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * All user role entities, keyed by role ID (rid).
   *
   * @var \Drupal\user\RoleInterface[]
   */
  protected $allRoles;

  /**
   * The Drupal user permissions hash generator.
   *
   * @var \Drupal\Core\Session\PermissionsHashGeneratorInterface
   */
  protected $permissionsHashGenerator;

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
   * @param \Drupal\Core\Cache\CacheBackendInterface $defaultCache
   *   The default Drupal cache bin.
   *
   * @param \Drupal\Core\Session\AccountInterface $currentUser
   *   The current user.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The Drupal entity type manager.
   *
   * @param \Drupal\Core\Session\PermissionsHashGeneratorInterface $permissionsHashGenerator
   *   The Drupal user permissions hash generator.
   */
  public function __construct(
    AccountInterface                  $currentUser,
    CacheBackendInterface             $defaultCache,
    EntityTypeManagerInterface        $entityTypeManager,
    PermissionsHashGeneratorInterface $permissionsHashGenerator
  ) {

    // Save dependencies.
    $this->currentUser              = $currentUser;
    $this->defaultCache             = $defaultCache;
    $this->permissionsHashGenerator = $permissionsHashGenerator;
    $this->roleStorage = $entityTypeManager->getStorage('user_role');
    $this->userStorage = $entityTypeManager->getStorage('user');

  }

  /**
   * {@inheritdoc}
   *
   * @see \Drupal\Core\Cache\Context\AccountPermissionsCacheContext::getContext()
   *   We generate the permission hash in the exact same way as the
   *   'user.permissions' cache context.
   *
   * @todo Determine how well this scales, and if starts to have a noticeable
   *   performance impact, implement a system that only generates this when a
   *   user is added/edited/deleted, one user at a time.
   */
  public function getPermissionHashes(): array {

    /** @var object|null */
    $cached = $this->defaultCache->get(self::USER_PERMISSION_HASHES_CACHE_BIN);

    if (\is_object($cached)) {
      return $cached->data;
    }

    /** @var \Drupal\user\UserInterface[] */
    $allUsers = $this->userStorage->loadMultiple();

    /** @var string[] */
    $permissionHashes = [];

    foreach ($allUsers as $user) {
      $permissionHashes[\implode(',', $user->getRoles())] =
        $this->permissionsHashGenerator->generate($user);
    }

    // Remove all duplicate hash values.
    $permissionHashes = \array_unique($permissionHashes);

    $this->defaultCache->set(
      self::USER_PERMISSION_HASHES_CACHE_BIN, $permissionHashes,
      Cache::PERMANENT,
      Cache::mergeTags(
        // Invalidated whenever any role is added/updated/deleted.
        $this->roleStorage->getEntityType()->getListCacheTags(),
        // Invalidated whenever any user is added/updated/deleted.
        $this->userStorage->getEntityType()->getListCacheTags()
      )
    );

    return $permissionHashes;

  }

  /**
   * {@inheritdoc}
   */
  public function getPermissionHash(?UserInterface $user = null): string {

    if (!\is_object($user)) {
      $user = $this->currentUser;
    }

    return $this->permissionsHashGenerator->generate($user);

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
