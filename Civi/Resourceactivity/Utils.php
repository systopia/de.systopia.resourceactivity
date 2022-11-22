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

use Civi\Api4\OptionValue;
use CRM_Resourceactivity_ExtensionUtil as E;

class Utils {

  /**
   * @throws \API_Exception
   * @throws \Civi\API\Exception\UnauthorizedException
   */
  public static function getResourceActivityType($map = FALSE) {
    $activity_type = OptionValue::get(FALSE)
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
    if (!in_array(strtolower($type), ['Scheduled', 'Cancelled'])) {
      throw new \Exception(E::ts('Unknown activity status class %1.', [1 => $type]));
    }
    if (!$status_id = \Civi::settings()->get('resourceactivity_default_participant_status_id_' . strtolower($type))) {
      // If no specific status is configured, use the reserved one ("Scheduled"
      // or "Cancelled").
      $activity_statuses = OptionValue::get()
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

}
