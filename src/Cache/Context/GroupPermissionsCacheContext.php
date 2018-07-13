<?php

namespace Drupal\group\Cache\Context;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\group\Access\GroupPermissionsHashGeneratorInterface;

/**
 * Defines a cache context for "per group membership permissions" caching.
 *
 * @todo Info re variations and ways to disable this cache context.
 * @todo Consider roles cache context.
 *
 * Cache context ID: 'user.group_permissions'.
 */
class GroupPermissionsCacheContext {

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
   * Constructs a new GroupMembershipPermissionsCacheContext class.
   *
   * @param \Drupal\Core\Session\AccountInterface $user
   *   The current user.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\group\Access\GroupPermissionsHashGeneratorInterface $hash_generator
   *   The permissions hash generator.
   */
  public function __construct(AccountInterface $user, EntityTypeManagerInterface $entity_type_manager, GroupPermissionsHashGeneratorInterface $hash_generator) {
    $this->user = $user;
    $this->entityTypeManager = $entity_type_manager;
    $this->permissionsHashGenerator = $hash_generator;
  }

  /**
   * {@inheritdoc}
   */
  public static function getLabel() {
    return t("Group membership permissions");
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
    // @todo Take bypass permission into account, delete permission in 8.2.x.
    $cacheable_metadata = new CacheableMetadata();

    // If any of the membership's roles are updated, it could mean the list of
    // permissions changed as well. We therefore need to set the membership's
    // roles' cacheable metadata.
    //
    // Note that we do not set the membership's cacheable metadata because that
    // one is taken care of in the parent 'group_membership.roles' context.
    if ($this->hasExistingGroup()) {
      // Retrieve all of the group roles the user may get for the group.
      $group_roles = $this->groupRoleStorage()->loadByUserAndGroup($this->user, $this->group);

      // Merge the cacheable metadata of all the roles.
      foreach ($group_roles as $group_role) {
        $group_role_cacheable_metadata = new CacheableMetadata();
        $group_role_cacheable_metadata->createFromObject($group_role);
        $cacheable_metadata->merge($group_role_cacheable_metadata);
      }
    }

    return $cacheable_metadata;
  }
}
