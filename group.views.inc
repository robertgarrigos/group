<?php
/**
 * @file
 * Hook implementations for the Views module.
 */

function group_views_plugins() {
  $plugins['access']['group_role'] = array(
    'title' => t('Group role'),
    'handler' => 'group_plugin_access_group_role',
    'parent' => 'views_plugin_access',
    'no ui' => FALSE,
    'uses options' => TRUE,
    'help' => t('help text'),
  );

  return $plugins;
}