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

use CRM_Resourceactivity_ExtensionUtil as E;

/*
* Settings metadata file
*/

return [
  'resourceactivity_activity_status_id_scheduled' => [
    'group_name' => E::ts('CiviResource Activity Settings'),
    'group' => 'resourceactivity',
    'name' => 'resourceactivity_activity_status_id_scheduled',
    'type' => 'Integer',
    'html_type' => 'select',
    'add' => '4.3',
    'is_domain' => 1,
    'is_contact' => 0,
    // "is_required" must be FALSE when not required, not just falsy.
    'is_required' => FALSE,
    'title' => E::ts('Default activity status for assigned resource'),
    'description' => E::ts('Default activity status to set activities to when assigning a resource. If left unset, the "Scheduled" activity status is being used.'),
    'html_attributes' => [
      'class' => 'crm-select2',
    ],
    'pseudoconstant' => [
      'optionGroupName' => 'activity_status',
      'optionEditPath' => 'civicrm/admin/options/activity_status',
    ],
    'settings_pages' => [
      'resourceactivity' => [
        'weight' => 10,
      ]
    ],
  ],
  'resourceactivity_activity_status_id_cancelled' => [
    'group_name' => E::ts('CiviResource Activity Settings'),
    'group' => 'resourceactivity',
    'name' => 'resourceactivity_activity_status_id_cancelled',
    'type' => 'Integer',
    'html_type' => 'select',
    'add' => '4.3',
    'is_domain' => 1,
    'is_contact' => 0,
    // "is_required" must be FALSE when not required, not just falsy.
    'is_required' => FALSE,
    'title' => E::ts('Default activity status for unassigned resource'),
    'description' => E::ts('Default activity status to set activities to when unassigning a resource. If left unset, the "Cancelled" activity status is being used.'),
    'html_attributes' => [
      'class' => 'crm-select2',
    ],
    'pseudoconstant' => [
      'optionGroupName' => 'activity_status',
      'optionEditPath' => 'civicrm/admin/options/activity_status',
    ],
    'settings_pages' => [
      'resourceactivity' => [
        'weight' => 10,
      ]
    ],
  ],
];
