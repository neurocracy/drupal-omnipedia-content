<?php

namespace Drupal\omnipedia_content\Service;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Cache\Context\CacheContextsManager;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\PermissionsHashGeneratorInterface;
use Drupal\omnipedia_content\Service\WikiNodeChangesInfoInterface;
use Drupal\omnipedia_core\Entity\NodeInterface;

/**
 * The Omnipedia wiki node changes info service.
 */
class WikiNodeChangesInfo implements WikiNodeChangesInfoInterface {

  /**
   * The cache bin name where user permission hashes are stored.
   *
   * @var string
   */
  protected const USER_PERMISSION_HASHES_CACHE_BIN =
    'omnipedia_content_changes_user_permission_hashes';

  /**
   * The Drupal cache contexts manager.
   *
   * @var \Drupal\Core\Cache\Context\CacheContextsManager
   */
  protected $cacheContextsManager;

  /**
   * The default Drupal cache bin.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  protected $defaultCache;

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
   * @param \Drupal\Core\Cache\Context\CacheContextsManager $cacheContextsManager
   *   The Drupal cache contexts manager.
   *
   * @param \Drupal\Core\Cache\CacheBackendInterface $defaultCache
   *   The default Drupal cache bin.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The Drupal entity type manager.
   *
   * @param \Drupal\Core\Session\PermissionsHashGeneratorInterface $permissionsHashGenerator
   *   The Drupal user permissions hash generator.
   */
  public function __construct(
    CacheContextsManager              $cacheContextsManager,
    CacheBackendInterface             $defaultCache,
    EntityTypeManagerInterface        $entityTypeManager,
    PermissionsHashGeneratorInterface $permissionsHashGenerator
  ) {

    // Save dependencies.
    $this->cacheContextsManager     = $cacheContextsManager;
    $this->defaultCache             = $defaultCache;
    $this->permissionsHashGenerator = $permissionsHashGenerator;
    $this->roleStorage = $entityTypeManager->getStorage('user_role');
    $this->userStorage = $entityTypeManager->getStorage('user');

  }

  /**
   * Get all unique permission hashes for all users.
   *
   * @return string[]
   *   An array of unique permission hash strings for all users, i.e. with all
   *   duplicate hashes reduced to a single entry. The keys are a comma-
   *   separated list of roles that the hashes correspond to.
   *
   * @see \Drupal\Core\Cache\Context\AccountPermissionsCacheContext::getContext()
   *   We generate the permission hash in the exact same way as the
   *   'user.permissions' cache context, but without having to pass it every
   *   single user.
   *
   * @todo Determine how well this scales, and if starts to have a noticeable
   *   performance impact, implement a system that only generates this when a
   *   user is added/edited/deleted, one user at a time.
   */
  protected function getPermissionHashes(): array {

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
   *
   * @todo Add i18n support.
   */
  public function getCacheIds(string $nid): array {

    /** @var string[] */
    $permissionHashes = $this->getPermissionHashes();

    // These are hard-coded for now. We only render on one theme, and currently
    // only have content in English.
    /** @var string[] */
    $cacheKeys = $this->cacheContextsManager->convertTokensToKeys([
      'languages:language_interface',
      'theme',
    ])->getKeys();

    /** @var string[] */
    $variations = [];

    // Build the variations, including the 'user.permissions' cache context in
    // the exact format CacheContextsManager::convertTokensToKeys() would
    // generate for the current user - instead we're building it for all
    // permission hashes currently represented by users in the site.
    foreach ($permissionHashes as $roles => $hash) {
      $variations[$roles] = $nid . ':' . \implode(
        ':', \array_merge($cacheKeys, ['[user.permissions]=' . $hash])
      );
    }

    return $variations;

  }

  /**
   * {@inheritdoc}
   */
  public function getCacheId(string $nid): string {

    // Build the cache keys to vary by based on these hard-coded cache contexts.
    // The cache contexts manager will automatically populate these with the
    // relevant values, e.g. the language code or current user's permissions
    // hash.
    /** @var array */
    $cacheKeys = $this->cacheContextsManager->convertTokensToKeys([
      'languages:language_interface',
      'theme',
      'user.permissions',
    ])->getKeys();

    return $nid . ':' . \implode(':', $cacheKeys);

  }

}
