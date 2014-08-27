<?php
/**
 * @file
 * Hooks provided by the Group module.
 */

/**
 * @addtogroup hooks
 * @{
 */

/**
 * Act upon uninstallation of the Group module.
 *
 * This function differs from hook_modules_uninstalled() in a sense that it
 * allows you to act upon the uninstallation of the Group module by using the
 * group ids of all groups in your uninstallation logic. If you do not need
 * such functionality, just use hook_modules_uninstalled() instead.
 *
 * @param array $gids
 *   An array of group ids.
 */
function hook_group_uninstall($gids) {
  db_delete('my_entity_table')
    ->condition('my_entity_id_field', $gids, 'IN')
    ->execute();
}

/**
 * Provide entity metadata for the Group module.
 *
 * This is not a real hook but instead lists extra entity info keys you
 * can use in hook_entity_info().
 *
 * The following extra keys are available:
 * - group entity: Whether this entity can be attached to group entities. Note
 *   that group_entity_info_alter() takes care of the node entity. User
 *   entities are handled through group memberships. Defaults to FALSE,
 *   available options are:
 *   - FALSE: This entity may not be attached to groups.
 *   - 'single': This entity may only be attached to one group at any given
 *   time. The 'group' property of entities of this type will be a single group
 *   id (integer value).
 *   - 'multiple': This entity may be attached to multiple groups at the same
 *   time. The 'group' property on entities of this type will be an array of
 *   group ids.
 *
 * @see hook_entity_info()
 * @see group_entity_info_alter()
 */
function hook_entity_info() {
  $info['node'] = array(
    // ...
    'group entity' => 'single',
  );

  return $info;
}

/**
 * Define group permissions.
 *
 * This hook can supply permissions that the module defines, so that they
 * can be selected on the group permissions page and used to grant or restrict
 * access to actions the module performs.
 *
 * Permissions are checked using Group::userHasPermission().
 *
 * @return array
 * An array whose keys are permission names and whose corresponding values
 * are arrays containing the following key-value pairs:
 * - title: The human-readable name of the permission, to be shown on the
 *   permission administration page. This should be wrapped in the t() function
 *   so it can be translated.
 * - description: (optional) A description of what the permission does. This
 *   should be wrapped in the t() function so it can be translated.
 * - restrict access: (optional) A boolean which can be set to TRUE to indicate
 *   that site administrators should restrict access to this permission to
 *   trusted users. This should be used for permissions that have inherent
 *   security risks across a variety of potential use cases (for example, the
 *   "administer filters" and "bypass node access" permissions provided by
 *   Drupal core). When set to TRUE, a standard warning message defined in
 *   user_admin_permissions() and output via
 *   theme_user_permission_description() will be associated with the permission
 *   and displayed with it on the permission administration page. Defaults to
 *   FALSE.
 * - warning: (optional) A translated warning message to display for this
 *   permission on the permission administration page. This warning overrides
 *   the automatic warning generated by 'restrict access' being set to TRUE.
 *   This should rarely be used, since it is important for all permissions to
 *   have a clear, consistent security warning that is the same across the
 *   site. Use the 'description' key instead to provide any information that
 *   is specific to the permission you are defining.
 * - limit to: (optional) A bit flag to define which membership types can use
 *   this permission. Possible flags are GROUP_LIMIT_ANONYMOUS,
 *   GROUP_LIMIT_OUTSIDER and GROUP_LIMIT_MEMBER. Defaults to GROUP_LIMIT_ALL,
 *   which allows the permission to be set for any type of membership.
 *
 * @see hook_permission()
 */
function hook_group_permission() {
  return array(
    'contact group members' => array(
      'title' => t('Contact group members'),
      'description' => t('Send group members a private message.'),
    ),
  );
}

/**
 * Add filters to the group overview page.
 *
 * This hook is used to provide additional filters to the group overview page
 * found at admin/group. The filters should always be something that can be
 * used in a select element.
 *
 * Keep in mind that this only adds the filters to the user interface. To
 * actually have them do something, you need to write a hook_query_TAG_alter()
 * implementation for the 'group_overview' tag.
 *
 * @see group_filters_form()
 * @see group_query_group_overview_alter()
 */
function hook_group_filters() {
  // Get a list of all group types.
  $group_types = array();
  foreach (group_types() as $name => $group_type) {
    $group_types[$name] = $group_type->label();
  }

  // Build a group type filter.
  $filters['type'] = array(
    'title' => t('Group type'),
    'options' => array(
      '[any]' => t('any'),
    ) + $group_types,
  );

  return $filters;
}

/**
 * Add mass group operations.
 *
 * This hook enables modules to inject custom operations into the mass
 * operations dropdown found at admin/group by associating a callback
 * function with the operation, which is called when the form is submitted.
 *
 * The callback function receives one initial argument, which is an array of
 * the selected group ids. If it is a form callback, it receives the form and
 * form state as well.
 *
 * @return array
 *   An array of operations. Each operation is an associative array that may
 *   contain the following key-value pairs:
 *   - label: (required) The label for the operation, displayed in the dropdown
 *     menu.
 *   - callback: (required) The function to call for the operation.
 *   - callback arguments: (optional) An array of additional arguments to pass
 *     to the callback function.
 *   - form callback: (optional) Whether the callback is a form builder. Set
 *     to TRUE to have the callback build a form such as a confirmation form.
 *     This form will then replace the group overview form, see the 'delete'
 *     operation for an example.
 *   - optgroup: (optional) The label of the <optgroup> this operation should
 *     be placed under. This will put the operation in the same <optgroup> as
 *     all other operations that specify the same label.
 */
function hook_group_operations() {
  // Acts upon selected groups but shows overview form right after.
  $operations['close'] = array(
    'label' => t('Close selected groups'),
    'callback' => 'mymodule_open_or_close_groups',
    'callback arguments' => array('close'),
    'optgroup' => t('Open or close'),
  );

  // Acts upon selected groups but shows overview form right after.
  $operations['open'] = array(
    'label' => t('Open selected groups'),
    'callback' => 'mymodule_open_or_close_groups',
    'callback arguments' => array('open'),
    'optgroup' => t('Open or close'),
  );

  // Shows a different form when this operation is selected.
  $operations['delete'] = array(
    'label' => t('Delete selected groups'),
    'callback' => 'group_multiple_delete_confirm',
    'form callback' => TRUE,
  );

  return $operations;
}

/**
 * Add group operation links.
 *
 * This hook enables modules to inject custom operations into the operations
 * column of the table found at admin/group by associating a callback function
 * with the operation, which is called when the form is submitted.
 *
 * The callback function receives the Group of the table row and should return
 * an array of links to display in the operations column.
 *
 * @param Group
 *   The group to format links for.
 *
 * @return array
 *   An array of links, declared as in theme_links(). At the very least, the
 *   'title' and 'href' keys should be defined.
 *
 * @see group_groups_form()
 * @see theme_links()
 */
function hook_group_operation_links(Group $group) {
  $operations = array();

  // Add an 'edit' link if available.
  if (group_access('update', $group)) {
    $operations['edit'] = array(
      'title' => t('edit'),
      'href' => "group/$group->gid/edit",
    );
  }

  // Add a 'delete' link if available.
  if (group_access('delete', $group)) {
    $operations['delete'] = array(
      'title' => t('delete'),
      'href' => "group/$group->gid/delete",
    );
  }

  return $operations;
}

/**
 * Add filters to the group member overview page.
 *
 * This hook is used to provide additional filters to the member overview page
 * found at group/%/members. The filters should always be something that can be
 * used in a select element.
 *
 * Keep in mind that this only adds the filters to the user interface. To
 * actually have them do something, you need to write a hook_query_TAG_alter()
 * implementation for the 'group_member_overview' tag.
 *
 * @see group_member_filters_form()
 * @see group_query_group_member_overview_alter()
 */
function hook_group_member_filters() {
  // Build a status filter.
  $filters['status'] = array(
    'title' => t('Status'),
    'options' => array(
      '[any]' => t('any'),
      1 => t('active'),
      0 => t('blocked'),
    ),
  );

  return $filters;
}

/**
 * Add mass group member operations.
 *
 * This hook enables modules to inject custom operations into the mass
 * operations dropdown found at group/%/members by associating a callback
 * function with the operation, which is called when the form is submitted.
 *
 * The callback function receives one initial argument, which is an array of
 * the selected membership ids. If it is a form callback, it receives the form
 * and form state as well.
 *
 * @param Group $group
 *   The group to show member operations for.
 *
 * @return array
 *   An array of operations. Each operation is an associative array that may
 *   contain the following key-value pairs:
 *   - label: (required) The label for the operation, displayed in the dropdown
 *     menu.
 *   - callback: (required) The function to call for the operation.
 *   - callback arguments: (optional) An array of additional arguments to pass
 *     to the callback function.
 *   - form callback: (optional) Whether the callback is a form builder. Set
 *     to TRUE to have the callback build a form such as a confirmation form.
 *     This form will then replace the member overview form, see the 'delete'
 *     operation for an example.
 *   - optgroup: (optional) The label of the <optgroup> this operation should
 *     be placed under. This will put the operation in the same <optgroup> as
 *     all other operations that specify the same label.
 *
 * @see group_member_options_form()
 */
function hook_group_member_operations(Group $group) {
  // Acts upon selected members but shows overview form right after.
  $operations['block'] = array(
    'label' => t('Block selected members'),
    'callback' => 'mymodule_block_members',
  );

  // Shows a different form when this operation is selected.
  $operations['remove'] = array(
    'label' => t('Remove selected members'),
    'callback' => 'group_membership_multiple_delete_confirm',
    'form callback' => TRUE,
  );

  return $operations;
}

/**
 * Add group member operation links.
 *
 * This hook enables modules to inject custom operations into the operations
 * column of the table found at group/%/members by associating a callback
 * function with the operation, which is called when the form is submitted.
 *
 * The callback function receives the GroupMembership of the table row and
 * should return an array of links to display in the operations column.
 *
 * @param GroupMembership
 *   The membership to format links for.
 *
 * @return array
 *   An array of links, declared as in theme_links(). At the very least, the
 *   'title' and 'href' keys should be defined.
 *
 * @see group_members_form()
 * @see theme_links()
 */
function hook_group_member_operation_links(GroupMembership $group_membership) {
  $operations = array();

  // Add membership management links.
  if (group_access('administer members', group_load($group_membership->gid))) {
    $operations['edit-membership'] = array(
      'title' => t('edit'),
      'href' => 'group/member/' . $group_membership->mid . '/edit'
    );

    $operations['cancel-membership'] = array(
      'title' => t('cancel'),
      'href' => 'group/member/' . $group_membership->mid . '/cancel'
    );
  }

  return $operations;
}

/**
 * Provide information about group membership statuses exposed by your module.
 *
 * Membership statuses are usually simple strings such as Active or Blocked.
 * Modules may add their own membership statuses and handle group memberships
 * differently depending on their status. An example can be found in the Group
 * Invite (ginvite) submodule included in Group.
 *
 * @return array
 *   An array whose keys are membership status machine names and whose
 *   corresponding values are arrays containing the following key-value pairs:
 *   - title: The human readable title for the membership status.
 *   - active: (boolean) Whether this status should be considered as active,
 *     meaning the membership will actually grant permissions to the member.
 *     Set to FALSE for suspending statuses such as 'Blocked', 'Banned', etc.
 */
function hook_group_membership_status_info() {
  $info['banned-24'] = array(
    'title' => t('Banned (24 hours)'),
    'active' => FALSE,
  );

  $info['banned-48'] = array(
    'title' => t('Banned (48 hours)'),
    'active' => FALSE,
  );

  return $info;
}

/**
 * Provide information about group membership actions exposed by your module.
 *
 * Membership actions affect the way users are members of a group. An example
 * of how to implement your own membership action can be found in the Group
 * Invite (ginvite) submodule included in Group.
 *
 * Typically, a module that provides a membership action also provides at least
 * one group permission and optionally membership status to go with it. Check
 * out hook_group_permission() and hook_group_membership_status_info()
 * respectively on how to do so.
 *
 * Out of the box, membership actions are represented by buttons on the group
 * view. Other modules can choose to create different display options for them.
 *
 * @return array
 *   An array of actions that can affect the state of a membership, keyed by a
 *   unique machine name with each entry having the following keys:
 *   - label: The human readable label for the membership action. This will be
 *     used as the button label on the group view.
 *   - description: (optional) Extra information about what the action does to
 *     a membership. Can be used by other modules.
 *   - access callback: (optional) Callback that returns TRUE if the user has
 *     access to the action or FALSE otherwise. Always receives a group and
 *     user object as arguments and a group membership object if one exists or
 *     FALSE otherwise. Always allows access if a callback is omitted.
 *   - action callback: Callback which is called when the action is fired. Out
 *     of the box this happens when the button on the group view is pressed.
 *     Always receives a group and user object as arguments and a group
 *     membership object if one exists or FALSE otherwise.
 *
 * @see hook_group_permission()
 * @see hook_group_membership_status_info()
 */
function hook_group_membership_action_info() {
  $info['digest_subscribe'] = array(
    'label' => t('Subscribe to e-mail digest'),
    'description' => t('Receive e-mail notifications for events in this group.'),
    'access callback' => 'mymodule_member_is_not_subscribed',
    'action callback' => 'mymodule_subscribe_member_digest',
  );

  $info['digest_unsubscribe'] = array(
    'label' => t('Unsubscribe from e-mail digest'),
    'description' => t('Stop receiving e-mail notifications.'),
    'access callback' => 'mymodule_member_is_subscribed',
    'action callback' => 'mymodule_unsubscribe_member_digest',
  );

  return $info;
}

/**
 * @} End of "addtogroup hooks".
 */
