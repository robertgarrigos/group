<?php

namespace Drupal\group\Cache\Context;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Cache\Context\CacheContextInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\group\Access\GroupPermissionsHashGeneratorInterface;
use Drupal\group\GroupMembershipLoaderInterface;

/**
 * Defines a cache context for "per group membership permissions" caching.
 *
 * Please read the following guide on how to best use this context:
 * https://www.drupal.org/docs/8/modules/group/turning-off-caching-when-it-doesnt-make-sense
 *
 * @todo Consider roles cache context.
 *
 * Cache context ID: 'user.group_permissions'.
 */
class GroupPermissionsCacheContext implements CacheContextInterface {

  /**
   * The account object.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $user;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The permissions hash generator.
   *
   * @var \Drupal\group\Access\GroupPermissionsHashGeneratorInterface
   */
  protected $permissionsHashGenerator;

  /**
   * The membership loader service.
   *
   * @var \Drupal\group\GroupMembershipLoaderInterface
   */
  protected $membershipLoader;

  /**
   * Constructs a new GroupMembershipPermissionsCacheContext class.
   *
   * @param \Drupal\Core\Session\AccountInterface $user
   *   The current user.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\group\Access\GroupPermissionsHashGeneratorInterface $hash_generator
   *   The permissions hash generator.
   * @param \Drupal\group\GroupMembershipLoaderInterface $membership_loader
   *   The group membership loader service.
   */
  public function __construct(AccountInterface $user, EntityTypeManagerInterface $entity_type_manager, GroupPermissionsHashGeneratorInterface $hash_generator, GroupMembershipLoaderInterface $membership_loader) {
    $this->user = $user;
    $this->entityTypeManager = $entity_type_manager;
    $this->permissionsHashGenerator = $hash_generator;
    $this->membershipLoader = $membership_loader;
  }

  /**
   * {@inheritdoc}
   */
  public static function getLabel() {
    return t("Group permissions");
  }

  /**
   * {@inheritdoc}
   */
  public function getContext() {
    if ($this->user->isAnonymous()) {
      return $this->permissionsHashGenerator->generateAnonymousHash();
    }
    return $this->permissionsHashGenerator->generateAuthenticatedHash($this->user);
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheableMetadata() {
    $cacheable_metadata = new CacheableMetadata();

    // If the user is anonymous, the result of this cache context may change
    // when any anonymous group role is updated.
    if ($this->user->isAnonymous()) {
      /** @var \Drupal\group\Entity\GroupTypeInterface $group_type */
      $storage = $this->entityTypeManager->getStorage('group_type');
      foreach ($storage->loadMultiple() as $group_type_id => $group_type) {
        $group_role_cacheable_metadata = new CacheableMetadata();
        $group_role_cacheable_metadata->createFromObject($group_type->getAnonymousRole());
        $cacheable_metadata->merge($group_role_cacheable_metadata);
      }
    }
    else {
      // An authenticated user's group permissions might change when:
      // - they are updated to have different roles
      // - they join a group
      // - they leave a group
      $cacheable_metadata->createFromObject($this->user);

      // - any of the outsider roles are updated
      /** @var \Drupal\group\Entity\GroupTypeInterface $group_type */
      $storage = $this->entityTypeManager->getStorage('group_type');
      foreach ($storage->loadMultiple() as $group_type_id => $group_type) {
        $group_role_cacheable_metadata = new CacheableMetadata();
        $group_role_cacheable_metadata->createFromObject($group_type->getOutsiderRole());
        $cacheable_metadata->merge($group_role_cacheable_metadata);
      }

      // - any of their synchronized outsider roles are updated
      /** @var \Drupal\group\Entity\Storage\GroupRoleStorageInterface $storage */
      $storage = $this->entityTypeManager->getStorage('group_role');
      foreach ($storage->loadSynchronizedByUserRoles($this->user->getRoles(TRUE)) as $group_role) {
        $group_role_cacheable_metadata = new CacheableMetadata();
        $group_role_cacheable_metadata->createFromObject($group_role);
        $cacheable_metadata->merge($group_role_cacheable_metadata);
      }

      // - any of their member roles are updated
      // - any of their memberships are updated
      foreach ($this->membershipLoader->loadByUser($this->user) as $group_membership) {
        $membership_cacheable_metadata = new CacheableMetadata();
        $membership_cacheable_metadata->createFromObject($group_membership);
        $cacheable_metadata->merge($membership_cacheable_metadata);

        foreach ($group_membership->getRoles() as $group_role) {
          $group_role_cacheable_metadata = new CacheableMetadata();
          $group_role_cacheable_metadata->createFromObject($group_role);
          $cacheable_metadata->merge($group_role_cacheable_metadata);
        }
      }

      // @todo Take bypass permission into account, delete permission in 8.2.x.
    }

    return $cacheable_metadata;
  }
}
