<?php
/**
 * @file
 * Definition of group_plugin_access_group_role.
 */

/**
 * Access plugin that provides group role based access control.
 *
 * @ingroup views_access_plugins
 */
class group_plugin_access_group_role extends views_plugin_access {

  /**
   * Generate a nice label for a contextual filter (argument).
   *
   * @param string $key
   *   The key of the argument to generate the label for.
   *
   * @return string
   *   The label for the argument.
   */
  function argument_label($key) {
    $arguments = $this->view->get_items('argument');
    $relationships = $this->view->get_items('relationship');

    // Check for an administrative title first.
    if (!empty($arguments[$key]['ui_name'])) {
      $label = $arguments[$key]['ui_name'];
    }
    // Then see if it uses a relationship and fall back to that name.
    elseif ($arguments[$key]['relationship'] != 'none') {
      $label = $relationships[$arguments[$key]['relationship']]['label'];
    }
    // Finally, fall back to the argument's key.
    else {
      $label = $key;
    }

    return $label;
  }

  /**
   * Retrieve the options when this is a new access
   * control plugin
   */
  function option_definition() {
    $options = parent::option_definition();
    $options['group'] = '';
    $options['roles'] = array();

    return $options;
  }

  function options_form(&$form, &$form_state) {
    parent::options_form($form, $form_state);

    $show_info = FALSE;
    $arguments = $this->view->get_items('argument');

    $group_args = array();
    foreach ($arguments as $key => $settings) {
      // Only allow "Group: Group ID" and "Group membership: Group gid" contextual filters.
      $valid_argument = $settings['table'] == 'group_membership' && $settings['field'] == 'group';
      $valid_argument = $valid_argument || $settings['table'] == 'groups' && $settings['field'] == 'gid';

      if ($valid_argument) {
        // Try to find a nice label for the select element.
        $label = $this->argument_label($key);

        // If we couldn't find one, show some info text below.
        if ($label == $key) {
          $show_info = TRUE;
        }

        $group_args[$key] = $label;
      }
    }

    if (empty($group_args)) {
      $form['info'] = array(
        '#markup' => t('This access filter requires at least either one "Group: Group ID" or "Group membership: Group gid" contextual filter.'),
      );

      return;
    }

    $form['group'] = array(
      '#type' => 'select',
      '#title' => t('Group'),
      '#options' => $group_args,
      '#description' => t("Select the contextually provided group to check the current user's roles for."),
    );

    if ($show_info) {
      $form['group']['#description'] .= '<br />' . t("Hint: You can easily identify contextual filters if you set an administrative title for them.");
    }

    $form['roles'] = array(
      '#type' => 'checkboxes',
      '#title' => t('Group role'),
      '#options' => group_role_labels(TRUE),
      '#default_value' => $this->options['roles'],
      '#description' => t('Only users with the selected group roles will be able to access this display.'),
      '#required' => TRUE,
    );
  }

  /**
   * Return a string to display as the clickable title for the
   * access control.
   */
  function summary_title() {
    $group = $this->options['group'];
    $roles = array_filter($this->options['roles']);
    $arguments = $this->view->get_items('argument');

    if (empty($group) || empty($roles) || empty($arguments[$group])) {
      return t('Invalid configuration!');
    }

    $group_roles = group_role_labels(FALSE);
    $singular = '@role in @group';
    $plural = 'One of @count roles in @group';
    $replace = array(
      '@group' => $this->argument_label($group),
      '@role' => $group_roles[key($roles)],
    );

    return format_plural(count($roles), $singular, $plural, $replace);
  }

  /**
   * Determine if the current user has access or not.
   */
  function access($account) {
    // We can implement access checks here for blocks.
    return TRUE;
  }

  /**
   * Determine the access callback and arguments.
   */
  function get_access_callback() {
    // We can NOT implement access checks here for pages because we have dynamic
    // arguments. Unless we specify what part of the URL the group id is in.
    return TRUE;
  }
}
