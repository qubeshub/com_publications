<?php
/**
 * @package    hubzero-cms
 * @copyright  Copyright (c) 2005-2020 The Regents of the University of California.
 * @license    http://opensource.org/licenses/MIT MIT
 */

use Hubzero\Content\Migration\Base;
// No direct access
defined('_HZEXEC_') or die();

/**
 * Migration script for adding blue instructors to the QUBES instructors group
 **/
class Migration20210310000001ComPublications extends Base
{
	public static $from_group_cn = 'blue_instructor_resources';
	public static $to_group_cn = 'instructors';

	/**
	 * Up
	 **/
	public function up()
	{
		$from_group = \Hubzero\User\Group::getInstance(self::$from_group_cn);
		$to_group = \Hubzero\User\Group::getInstance(self::$to_group_cn);

		if ($from_group && $to_group) {
			$this->log(sprintf('Adding members from group %s to group %s', self::$from_group_cn, self::$to_group_cn));
			$from_members = $from_group->get('members');
			$to_members = $to_group->get('members');

			$add_members = array_diff($from_members, $to_members); // Don't add members already in group

			$to_group->add('members', $add_members);
			$to_group->update();
		} else {
			$this->log(sprintf('Group %s or group %s does not exist!', self::$from_group_cn, self::$to_group_cn));
		}
	}

	/**
	 * Down
	 **/
	public function down()
	{
		$to_group = \Hubzero\User\Group::getInstance(self::$to_group_cn);
		if ($to_group) {
			$this->log(sprintf('Removing members from group %s', self::$to_group_cn));
			$remove_members = array_diff($to_group->get('members'), $to_group->get('managers')); // Don't remove managers

			$to_group->remove('members', $remove_members);
			$to_group->update();
		} else {
			$this->log(sprintf('Group %s does not exist!', self::$to_group_cn));
		}
	}
}