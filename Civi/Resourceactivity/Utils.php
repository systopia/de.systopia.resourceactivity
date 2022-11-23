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

namespace Civi\Resourceactivity;

use Civi\Api4;
use CRM_Resourceactivity_ExtensionUtil as E;

class Utils {

  /**
   * @throws \API_Exception
   * @throws \Civi\API\Exception\UnauthorizedException
   */
  public static function getResourceActivityType($map = FALSE) {
    $activity_type = Api4\OptionValue::get(FALSE)
      ->addSelect('value', 'label')
      ->addWhere('option_group_id.name', '=', 'activity_type')
      ->addWhere('name', '=', 'resource_assignment')
      ->execute()
      ->single();
    return $map ? [$activity_type['value'] => $activity_type['label']] : $activity_type['value'];
  }

  /**
   * @throws \API_Exception
   * @throws \Civi\API\Exception\UnauthorizedException
   * @throws \Exception
   */
  public static function getDefaultActivityStatus($type) {
    if (!in_array(strtolower($type), ['scheduled', 'cancelled'])) {
      throw new \Exception(E::ts('Unknown activity status class %1.', [1 => $type]));
    }
    if (!$status_id = \Civi::settings()->get('resourceactivity_activity_status_id_' . strtolower($type))) {
      // If no specific status is configured, use the reserved one ("Scheduled"
      // or "Cancelled").
      $activity_statuses = Api4\OptionValue::get()
        ->addSelect('value')
        ->addWhere('option_group_id:name', '=', 'activity_status')
        ->addWhere('is_active', '=', TRUE)
        ->addWhere('name', '=', $type)
        ->execute();
      if ($activity_statuses->count() != 1) {
        throw new \Exception(E::ts('No active activity status %1', [1 => $type]));
      }
      $status_id = $activity_statuses->first()['value'];
    }
    return $status_id;
  }

  /**
   * Displays resource and resource demand labels and links to connected
   * entities in respective custom fields on activities.
   *
   * @param $displayValue
   * @param $value
   * @param $entityId
   * @param $fieldInfo
   *
   * @return void
   */
  public static function alterCustomFieldValues(&$displayValue, $value, $entityId, $fieldInfo) {
    static $resourceactivity_fields;
    if (!isset($resourceactivity_fields)) {
      $resourceactivity_fields = Api4\CustomField::get(FALSE)
        ->addSelect('id')
        ->addWhere('custom_group_id.name', '=', 'activity_resource_information')
        ->execute()
        ->column('id');
    }
    if (in_array($fieldInfo['id'], $resourceactivity_fields)) {
      switch ($fieldInfo['name']) {
        case 'resource':
          // Display resource label and link to connected entity.
          $resource = Api4\Resource::get(FALSE)
            ->addSelect('entity_table', 'entity_id', 'label')
            ->addWhere('id', '=', $value)
            ->execute()
            ->single();
          $link = \CRM_Utils_System::createDefaultCrudLink([
            'action' => 'view',
            'entity_table' => $resource['entity_table'],
            'id' => $resource['entity_id']
          ]);
          $displayValue = sprintf('%s (<a href="%s&selectedChild=resource">%s</a>)',
            htmlspecialchars($resource['label']),
            htmlspecialchars($link['url']),
            htmlspecialchars($link['title'])
          );
          break;
        case 'resource_demand':
          // Display resource demand label and link to connected entity.
          $resource_demand = Api4\ResourceDemand::get(FALSE)
            ->addSelect('entity_table', 'entity_id', 'label')
            ->addWhere('id', '=', $value)
            ->execute()
            ->single();
          $link = \CRM_Utils_System::createDefaultCrudLink([
            'action' => 'view',
            'entity_table' => $resource_demand['entity_table'],
            'id' => $resource_demand['entity_id']
          ]);
          $displayValue = sprintf('%s (<a href="%s">%s</a>)',
            htmlspecialchars($resource_demand['label']),
            htmlspecialchars($link['url']),
            htmlspecialchars($link['title'])
          );
          break;
      }
    }
  }

}
