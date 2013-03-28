<?php
/**
 * @file
 * Group editing UI.
 */

/**
 * Generates the group editing form.
 */
function group_form($form, &$form_state, $group, $op = 'edit') {
  if ($op == 'add') {
    drupal_set_title(t('Create @name', array('@name' => group_type_load($group->type)->label)));
  }

  $form['type'] = array(
    '#type' => 'value',
    '#value' => $group->type,
  );

  $form['title'] = array(
    '#type' => 'textfield',
    '#title' => t('Title'),
    '#default_value' => $group->title,
    '#description' => t('The name of the group.'),
    '#required' => TRUE,
    '#maxlength' => 255,
    '#size' => 30,
  );

  // Load fields based on group type.
  field_attach_form('group', $group, $form, $form_state, entity_language('group', $group));

  $form['actions'] = array('#type' => 'actions');
  $form['actions']['submit'] = array(
    '#type' => 'submit',
    '#value' => t('Save group'),
    '#weight' => 40,
  );

  if ($op == 'edit') {
    $form['actions']['delete'] = array(
      '#type' => 'submit',
      '#value' => t('Delete group'),
      '#weight' => 45,
      '#limit_validation_errors' => array(),
      '#submit' => array('group_form_submit_delete'),
    );
  }

  return $form;
}

/**
 * Form API validate callback for the group form.
 */
function group_form_validate($form, &$form_state) {
  entity_form_field_validate('group', $form, $form_state);
}

/**
 * Form API submit callback for the group form.
 */
function group_form_submit($form, &$form_state) {
  $group = entity_ui_form_submit_build_entity($form, $form_state);
  $group->save();

  $form_state['redirect'] = "group/$group->gid";
}

/**
 * Form API submit callback for the delete button.
 */
function group_form_submit_delete($form, &$form_state) {
  $form_state['redirect'] = 'group/' . $form_state['group']->gid . '/delete';
}