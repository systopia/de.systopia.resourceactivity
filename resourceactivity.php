<?php
/*-------------------------------------------------------+
| SYSTOPIA CiviResource Activity                         |
| Copyright (C) 2022 SYSTOPIA                            |
| Author: J. Schuppe (schuppe@systopia.de)               |
+--------------------------------------------------------+
| This program is released as free software under the    |
| Affero GPL license. You can redistribute it and/or     |
| modify it under the terms of this license which you    |
| can read by viewing the included agpl.txt or online    |
| at www.gnu.org/licenses/agpl.html. Removal of this     |
| copyright header is strictly prohibited without        |
| written permission from the original author(s).        |
+--------------------------------------------------------*/

require_once 'resourceactivity.civix.php';
// phpcs:disable
use CRM_Resourceactivity_ExtensionUtil as E;
use Civi\Api4\Managed;
// phpcs:enable

/**
 * Implements hook_civicrm_config().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_config/
 */
function resourceactivity_civicrm_config(&$config) {
  _resourceactivity_civix_civicrm_config($config);
}

/**
 * Implements hook_civicrm_install().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_install
 */
function resourceactivity_civicrm_install(): void {
  _resourceactivity_civix_civicrm_install();
}

/**
 * Implements hook_civicrm_postInstall().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_postInstall
 */
function resourceactivity_civicrm_postInstall(): void {
  _resourceactivity_civix_civicrm_postInstall();

  // Reconcile managed entities again for our custom group to pick up the
  // correct activity type to be attached to.
  Managed::reconcile(FALSE)
    ->addModule(E::LONG_NAME)
    ->execute();

  // Add a foreign key constraint to the custom field, allowing only resource
  // demand IDs as values.
  CRM_Core_DAO::singleValueQuery("
  ALTER TABLE civicrm_value_activity_resource_information
      MODIFY resource_demand INT UNSIGNED DEFAULT NULL,
      ADD CONSTRAINT FK_civicrm_value_activity_resource_information_resource_demand FOREIGN KEY (resource_demand)
          REFERENCES civicrm_resource_demand(id);
  ");
  // Mark the managed entity as modified to prevent it from being reset.
  Api4\Managed::update(FALSE)
    ->addWhere('module', '=', E::LONG_NAME)
    ->addWhere('name', '=', 'CustomField__activity_resource_information__resource_demand')
    ->addValue('entity_modified_date', date('Y-m-d H:i:s'))
    ->execute();
}

/**
 * Implements hook_civicrm_uninstall().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_uninstall
 */
function resourceactivity_civicrm_uninstall(): void {
  _resourceactivity_civix_civicrm_uninstall();
}

/**
 * Implements hook_civicrm_enable().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_enable
 */
function resourceactivity_civicrm_enable(): void {
  _resourceactivity_civix_civicrm_enable();
}

/**
 * Implements hook_civicrm_disable().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_disable
 */
function resourceactivity_civicrm_disable(): void {
  _resourceactivity_civix_civicrm_disable();
}

/**
 * Implements hook_civicrm_upgrade().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_upgrade
 */
function resourceactivity_civicrm_upgrade($op, CRM_Queue_Queue $queue = NULL) {
  return _resourceactivity_civix_civicrm_upgrade($op, $queue);
}

/**
 * Implements hook_civicrm_entityTypes().
 *
 * Declare entity types provided by this module.
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_entityTypes
 */
function resourceactivity_civicrm_entityTypes(&$entityTypes): void {
  _resourceactivity_civix_civicrm_entityTypes($entityTypes);
}

/**
 * Implements hook_civicrm_managed().
 *
 * Generate a list of entities to create/deactivate/delete when this module
 * is installed, disabled, uninstalled.
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_managed
 */
function resourceactivity_civicrm_managed(&$entities) {
  _resourceactivity_civix_civicrm_managed($entities);

  // Synchronise activity type.
  $entities[] = [
    'module' => E::LONG_NAME,
    'name' => 'OptionValue__activity_type__resource_assignment',
    'entity' => 'OptionValue',
    'cleanup' => 'unused',
    'update' => 'unmodified',
    'params' => [
      'version' => 4,
      'values' => [
        'option_group_id.name' => 'activity_type',
        'name' => 'resource_assignment',
        'label' => 'Resource Assignment',
        'description' => E::ts('CiviResource Activity resource assignments'),
        'is_reserved' => TRUE,
        'is_active' => TRUE,
        'icon' => 'fa-tasks',
      ],
    ],
    'match' => ['option_group_id.name', 'name'],
  ];

  // Synchronise custom group on activity objects.
  $entities[] = [
    'module' => E::LONG_NAME,
    'name' => 'CustomGroup__activity_resource_information',
    'entity' => 'CustomGroup',
    'cleanup' => 'always',
    'update' => 'unmodified',
    'params' => [
      'version' => 4,
      'values' => [
        'name' => 'activity_resource_information',
        'title' => 'Activity Resource Information',
        'extends' => 'Activity',
        'extends_entity_column_value' => Api4\OptionValue::get(FALSE)
          ->addSelect('value')
          ->addWhere('option_group_id:name', '=', 'activity_type')
          ->addWhere('name', '=', 'resource_assignment')
          ->execute()
          ->column('value'),
        // Note: "is_reserved" hides the custom field group in the UI.
        'is_reserved' => 1,
        'table_name' => 'civicrm_value_activity_resource_information',
      ],
    ],
    'match' => ['name'],
  ];

  // Synchronise custom field for storing resource demand on activity objects.
  $entities[] = [
    'module' => E::LONG_NAME,
    'name' => 'CustomField__activity_resource_information__resource_demand',
    'entity' => 'CustomField',
    'cleanup' => 'always',
    'update' => 'unmodified',
    'params' => [
      'version' => 4,
      'values' => [
        'name' => 'resource_demand',
        'label' => 'Resource Demand',
        'custom_group_id.name' => 'activity_resource_information',
        'html_type' => 'Text',
        'data_type' => 'Int',
        'is_required' => 1,
        'is_searchable' => 0,
        'is_search_range' => 0,
        'is_view' => 1,
        'in_selector' => 0,
        'column_name' => 'resource_demand'
      ],
    ],
    'match' => ['custom_group_id.name', 'name'],
  ];
}
