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

use Civi\Api4\Activity;
use Civi\Api4\Entity;
use Civi\Api4\Resource;
use Civi\Api4\ResourceDemand;
use Civi\Core\DAO\Event\PostDelete;
use Civi\Core\DAO\Event\PostUpdate;
use Symfony\Component\EventDispatcher\EventSubscriberInterface as EventSubscriberInterfaceAlias;
use CRM_Resourceactivity_ExtensionUtil as E;

class ResourceAssignmentSubscriber implements EventSubscriberInterfaceAlias {

  /**
   * @inheritDoc
   */
  public static function getSubscribedEvents() {
    return [
      'civi.dao.postInsert' => 'insertUpdateResourceAssignment',
      'civi.dao.postUpdate' => 'insertUpdateResourceAssignment',
      'civi.dao.postDelete' => 'deleteResourceAssignment',
    ];
  }

  /**
   * @throws \API_Exception
   * @throws \Civi\API\Exception\UnauthorizedException
   * @throws \Exception
   */
  public function insertUpdateResourceAssignment(PostUpdate $event) {
    if ($event->object instanceof \CRM_Resource_DAO_ResourceAssignment) {
      $resource_activity_type = Utils::getResourceActivityType();
      [$resource, $resource_demand] = self::getResourceAssignmentContext($event->object);
      $activities = Activity::get(FALSE)
        ->addWhere('activity_type_id', '=', $resource_activity_type)
        ->addWhere('activity_resource_information.resource', '=', $resource['id'])
        ->addWhere('activity_resource_information.resource_demand', '=', $resource_demand['id'])
        ->execute();
      switch ($activities->count()) {
        case 0:
          $demand_entity_info = Entity::get(FALSE)
            ->addSelect('name', 'title', 'label_field')
            ->addWhere('table_name', '=', $resource_demand['entity_table'])
            ->execute()
            ->single();
          $demand_entity = civicrm_api4($demand_entity_info['name'], 'get', [
            'select' => ['id', $demand_entity_info['label_field']],
            'where' => [['id', '=', $resource_demand['entity_id']]],
          ])->single();
          $activity_create = Activity::create(FALSE)
            ->addValue('source_contact_id', \CRM_Core_Session::getLoggedInContactID())
            ->addValue('activity_type_id', $resource_activity_type)
            ->addValue('activity_resource_information.resource', $resource['id'])
            ->addValue('activity_resource_information.resource_demand', $resource_demand['id'])
            ->addValue('status_id', Utils::getDefaultActivityStatus('Scheduled'))
            ->addValue('subject', E::ts(
              'Resource %1 assigned to resource demand %2 for %3 %4',
              [
                1 => $resource['label'],
                2 => $resource_demand['label'],
                3 => $demand_entity_info['title'],
                4 => $demand_entity[$demand_entity_info['label_field']],
              ]
            ));

          // Calculate total duration (in minutes) from the demand's timeframes.
          $timeframes = \CRM_Resource_BAO_ResourceDemand::getInstance($resource_demand['id'])
            ->getResourcesBlockedTimeframes()
            ->getTimeframes();
          if (!empty($timeframes)) {
            $activity_create
              ->addValue('activity_date_time', date('Y-m-d H:i:s', $timeframes[0][0]));
          }
          $duration = 0;
          foreach ($timeframes as [$from, $to]) {
            $duration += ($to - $from) / 60;
          }
          if ($duration) {
            $activity_create->addValue('duration', $duration);
          }

          // Add contact resources as target contacts.
          if ($resource['entity_table'] == 'civicrm_contact') {
            $activity_create
              ->addValue('target_contact_id', [$resource['entity_id']]);
          }
            $activity_create->execute();
          break;
        case 1:
          $activity = $activities->single();
          Activity::update(FALSE)
            ->addWhere('id', '=', $activity['id'])
            ->addValue('status_id', Utils::getDefaultActivityStatus('Scheduled'))
            ->execute();
          break;
        default:
          throw new \Exception(E::ts(
            'More than one activity found for resource %1 and resource demand %2.',
            [
              1 => $resource['label'],
              2 => $resource_demand['label'],
            ]
          ));
      }
    }
  }

  /**
   * @throws \API_Exception
   * @throws \Civi\API\Exception\UnauthorizedException
   * @throws \Exception
   */
  public function deleteResourceAssignment(PostDelete $event) {
    if ($event->object instanceof \CRM_Resource_DAO_ResourceAssignment) {
      $resource_activity_type = Utils::getResourceActivityType();
      [$resource, $resource_demand] = self::getResourceAssignmentContext($event->object);
      $activities = Activity::get(FALSE)
        ->addWhere('activity_type_id', '=', $resource_activity_type)
        ->addWhere('activity_resource_information.resource', '=', $resource['id'])
        ->addWhere('activity_resource_information.resource_demand', '=', $resource_demand['id'])
        ->execute();
      switch ($activities->count()) {
        case 0:
          // No activity object exists, do not create one.
          break;
        case 1:
          // Update existing Activity with default negative activity status.
          $activity = $activities->single();
          Activity::update(FALSE)
            ->addWhere('id', '=', $activity['id'])
            ->addValue('status_id', Utils::getDefaultActivityStatus('Cancelled'))
            ->execute();
          break;
        default:
          throw new \Exception(E::ts(
            'More than one activity found for resource %1 and resource demand %2.',
            [
              1 => $resource['label'],
              2 => $resource_demand['label'],
            ]
          ));
      }
    }
  }

  /**
   * @throws \API_Exception
   * @throws \Civi\API\Exception\UnauthorizedException
   */
  public static function getResourceAssignmentContext(\CRM_Resource_DAO_ResourceAssignment $resource_assignment) {
    $resource = Resource::get(FALSE)
      ->addWhere('id', '=', $resource_assignment->resource_id)
      ->execute()
      ->single();
    $resource_demand = ResourceDemand::get(FALSE)
      ->addWhere('id', '=', $resource_assignment->resource_demand_id)
      ->execute()
      ->single();

    return [$resource, $resource_demand];
  }

}
