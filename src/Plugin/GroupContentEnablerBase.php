<?php

/**
 * @file
 * Contains \Drupal\group\Plugin\GroupContentEnablerBase.
 */

namespace Drupal\group\Plugin;

use Drupal\group\Entity\GroupTypeInterface;
use Drupal\Core\Plugin\PluginBase;

/**
 * Provides a base class for GroupContentEnabler plugins.
 *
 * @see \Drupal\group\Annotation\GroupContentEnabler
 * @see \Drupal\group\GroupContentEnablerManager
 * @see \Drupal\group\Plugin\GroupContentEnablerInterface
 * @see plugin_api
 */
abstract class GroupContentEnablerBase extends PluginBase implements GroupContentEnablerInterface {

  /**
   * {@inheritdoc}
   *
   * @todo Consider doing configuration like BlockBase so we can remove this.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    // We call ::setConfiguration at construction to hide all non-configurable
    // keys such as 'id'. This causes the $configuration property to only list
    // that which is in fact configurable. However, ::getConfiguration still
    // returns the full configuration array.
    $this->setConfiguration($configuration);
  }

  /**
   * {@inheritdoc}
   */
  public function getProvider() {
    return $this->pluginDefinition['provider'];
  }

  /**
   * {@inheritdoc}
   */
  public function getLabel() {
    return $this->pluginDefinition['label'];
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return $this->pluginDefinition['description'];
  }

  /**
   * {@inheritdoc}
   */
  public function getEntityTypeId() {
    return $this->pluginDefinition['entity_type_id'];
  }

  /**
   * {@inheritdoc}
   */
  public function isEnforced() {
    return $this->pluginDefinition['enforced'];
  }

  /**
   * {@inheritdoc}
   */
  public function getContentTypeConfigId(GroupTypeInterface $group_type) {
    return $group_type->id() . '.' . str_replace(':', '.', $this->getPluginId());
  }

  /**
   * {@inheritdoc}
   */
  public function getContentTypeLabel(GroupTypeInterface $group_type) {
    return $group_type->label() . ': ' . $this->getLabel();
  }

  /**
   * {@inheritdoc}
   */
  public function getContentTypeDescription(GroupTypeInterface $group_type) {
    return $this->getDescription();
  }

  /**
   * {@inheritdoc}
   */
  public function getOperations() {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function getConfiguration() {
    return array(
      'id' => $this->getPluginId(),
      'data' => $this->configuration,
    );
  }

  /**
   * {@inheritdoc}
   */
  public function setConfiguration(array $configuration) {
    $configuration += array(
      'data' => array(),
    );
    $this->configuration = $configuration['data'] + $this->defaultConfiguration();
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return array();
  }

  /**
   * {@inheritdoc}
   */
  public function calculateDependencies() {
    return array();
  }

}
