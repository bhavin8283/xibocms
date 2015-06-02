<?php
/*
 * Spring Signage Ltd - http://www.springsignage.com
 * Copyright (C) 2015 Spring Signage Ltd
 * (UserGroupFactory.php)
 */


namespace Xibo\Factory;


use Xibo\Entity\UserGroup;
use Xibo\Exception\NotFoundException;
use Xibo\Helper\Log;
use Xibo\Helper\Sanitize;
use Xibo\Storage\PDOConnect;

class UserGroupFactory
{
    /**
     * Get by Group Id
     * @param int $groupId
     * @return UserGroup
     * @throws NotFoundException
     */
    public static function getById($groupId)
    {
        $groups = UserGroupFactory::query(null, ['groupId' => $groupId, 'isUserSpecific' => -1]);

        if (count($groups) <= 0)
            throw new NotFoundException(__('Group not found'));

        return $groups[0];
    }

    /**
     * Get by Group Name
     * @param string $group
     * @return UserGroup
     * @throws NotFoundException
     */
    public static function getByName($group)
    {
        $groups = UserGroupFactory::query(null, ['group' => $group, 'isUserSpecific' => 0]);

        if (count($groups) <= 0)
            throw new NotFoundException(__('Group not found'));

        return $groups[0];
    }

    /**
     * Get by User Id
     * @param int $userId
     * @return array[UserGroup]
     * @throws NotFoundException
     */
    public static function getByUserId($userId)
    {
        $groups = UserGroupFactory::query(null, ['userId' => $userId, 'isUserSpecific' => 0]);

        foreach ($groups as $group) {
            /* @var UserGroup $group */
            $group->assignUser($userId);
        }

        return $groups;
    }

    /**
     * @param array $sortOrder
     * @param array $filterBy
     * @return array[UserGroup]
     * @throws \Exception
     */
    public static function query($sortOrder = null, $filterBy = null)
    {
        $entries = array();
        $params = array();

        try {
            $sql = '
            SELECT 	`group`.group,
				`group`.groupId,
				`group`.isUserSpecific,
				`group`.isEveryone,
				`group`.libraryQuota
              FROM `group`
             WHERE 1 = 1
            ';

            // Filter by Group Id
            if (Sanitize::getInt('groupId', $filterBy) != null) {
                $sql .= ' AND `group`.groupId = :groupId ';
                $params['groupId'] = Sanitize::getInt('groupId', $filterBy);
            }

            // Filter by Group Name
            if (Sanitize::getString('group', $filterBy) != null) {
                $sql .= ' AND `group`.group = :group ';
                $params['group'] = Sanitize::getString('group', $filterBy);
            }

            // Filter by User Id
            if (Sanitize::getInt('userId', $filterBy) != null) {
                $sql .= ' AND `group`.groupId IN (SELECT groupId FROM `lkusergroup` WHERE userId = :userId) ';
                $params['userId'] = Sanitize::getInt('userId', $filterBy);
            }

            if (Sanitize::getInt('isUserSpecific', $filterBy, 0) != -1) {
                $sql .= ' AND isUserSpecific = :isUserSpecific ';
                $params['isUserSpecific'] = Sanitize::getInt('isUserSpecific', $filterBy, 0);
            }

            if (Sanitize::getInt('isEveryone', $filterBy, 0) != -1) {
                $sql .= ' AND isEveryone = :isEveryone ';
                $params['isEveryone'] = Sanitize::getInt('isEveryone', $filterBy, 0);
            }

            // Sorting?
            if (is_array($sortOrder))
                $sql .= 'ORDER BY ' . implode(',', $sortOrder);

            Log::sql($sql, $params);

            foreach (PDOConnect::select($sql, $params) as $row) {
                $entries[] = (new UserGroup())->hydrate($row);
            }

            return $entries;

        } catch (\Exception $e) {

            Log::error($e);

            throw $e;
        }
    }
}