<?php
/**
 * @package    hubzero-cms
 * @copyright  Copyright (c) 2005-2020 The Regents of the University of California.
 * @license    http://opensource.org/licenses/MIT MIT
 */

namespace Components\Publications\Tables;

use Hubzero\Database\Table;
use Components\Groups\Models\Orm\Group;
use Lang;

include_once Component::path('com_groups') . DS . 'models' . DS . 'orm' . DS . 'group.php';

/**
 * Table class for publication master type
 */
class MasterType extends Table
{
	/**
	 * Constructor
	 *
	 * @param   object  &$db  Database
	 * @return  void
	 */
	public function __construct(&$db)
	{
		parent::__construct('#__publication_master_types', 'id', $db);
	}

	/**
	 * Validate data
	 *
	 * @return  boolean  True if data is valid
	 */
	public function check()
	{
		if (trim($this->type) == '')
		{
			$this->setError(Lang::txt('Your publication master type must contain text.'));
			return false;
		}
		if (trim($this->alias) == '')
		{
			$this->setError(Lang::txt('Your publication master type alias must contain text.'));
			return false;
		}
		return true;
	}

	/**
	 * Get record by alias name or ID
	 *
	 * @param   string  $id
	 * @return  mixed   object or false
	 */
	public function getType($id = '')
	{
		if (!$id)
		{
			return false;
		}
		$field = is_numeric($id) ? 'id' : 'alias';

		$this->_db->setQuery("SELECT * FROM $this->_tbl WHERE $field=" . $this->_db->quote($id) . " LIMIT 1");
		$result = $this->_db->loadObjectList();
		return $result ? $result[0] : false;
	}

	/**
	 * Get record id by alias name
	 *
	 * @param   string  $alias
	 * @return  integer
	 */
	public function getTypeId($alias = '')
	{
		if (!$alias)
		{
			return false;
		}
		$this->_db->setQuery("SELECT id FROM $this->_tbl WHERE alias=" . $this->_db->quote($alias) . " LIMIT 1");
		return $this->_db->loadResult();
	}

	/**
	 * Get record alias by id
	 *
	 * @param   integer  $id
	 * @return  integer
	 */
	public function getTypeAlias($id='')
	{
		if (!$id)
		{
			return false;
		}
		$this->_db->setQuery("SELECT alias FROM $this->_tbl WHERE id=" . $this->_db->quote($id) . " LIMIT 1");
		return $this->_db->loadResult();
	}

	/**
	 * Get master type with a specified owner group
	 *
	 * 	REFACTOR: Stops at first master type found. Implement multiple master
	 *  	types in the future.
	 * 
	 * @return  array
	 */
	public function loadByOwnerGroup($group_id, $parents=1, $contributable=0)
	{
		$keys = array(
			'ownergroup' => $group_id
		);
		if ($contributable == 1) {
			$keys['contributable'] = 1;
		}
		$this->load($keys);

		if (!$this->id && $parents)
		{
			$gg = Group::oneOrFail($group_id);
			foreach ($gg->parents()->rows() as $parent) {
				$keys['ownergroup'] = $parent->get('gidNumber');
				if ($this->load($keys)) {
					break;
				}
			}
		}
		$this->_params = new \Hubzero\Config\Registry($this->params);

		return $this;
	}

	/**
	 * Get curator groups
	 *
	 * @return  array
	 */
	public function getCuratorGroups()
	{
		$groups = array();

		$query = "SELECT curatorgroup FROM $this->_tbl WHERE contributable=1
				  AND curatorgroup !=0 AND curatorgroup IS NOT null";

		$this->_db->setQuery($query);
		$results = $this->_db->loadObjectList();

		if ($results)
		{
			foreach ($results as $result)
			{
				if (trim($result->curatorgroup))
				{
					$groups[] = $result->curatorgroup;
				}
			}
		}

		return $groups;
	}

	/**
	 * Get types for which user is authorized (curation)
	 *
	 * @param   array  $usergroups
	 * @param   bool   $authorized
	 * @return  mixed  array or False
	 */
	public function getAuthTypes($usergroups = array(), $authorized = false)
	{
		$types = array();

		if (empty($usergroups))
		{
			return false;
		}
		if ($authorized == 'admin' || $authorized == 'curator')
		{
			// Access to all types
			$query = "SELECT id FROM $this->_tbl WHERE contributable=1";
		}
		else
		{
			$query = "SELECT id FROM $this->_tbl WHERE contributable=1
					  AND curatorgroup !=0 AND curatorgroup IS NOT null ";

			$tquery = '';
			foreach ($usergroups as $g)
			{
				$tquery .= "'" . $g->gidNumber . "',";
			}
			$tquery = substr($tquery, 0, strlen($tquery) - 1);
			$query .= " AND (curatorgroup IN (" . $tquery . ")) ";
		}

		$this->_db->setQuery($query);
		$results = $this->_db->loadObjectList();

		if ($results)
		{
			foreach ($results as $result)
			{
				if (trim($result->id))
				{
					$types[] = $result->id;
				}
			}
		}

		return $types;
	}

	/**
	 * Get records
	 *
	 * @param   string   $select         Select query
	 * @param   integer  $contributable  Contributable?
	 * @param   integer  $supporting     Supporting?
	 * @param   string   $orderby        Order by
	 * @param   string   $config
	 * @return  array
	 */
	public function getTypes($select = '*', $contributable = 0, $supporting = 0, $orderby = 'id', $config = '')
	{
		$query  = "SELECT $select FROM $this->_tbl ";
		if ($contributable)
		{
			$query .= "WHERE contributable=1 ";
		}
		elseif ($supporting)
		{
			$query .= "WHERE supporting=1 ";
		}

		$query .= "ORDER BY ".$orderby;

		$this->_db->setQuery($query);
		$results = $this->_db->loadObjectList();
		if ($select == 'alias')
		{
			$types = array();
			if ($results)
			{
				foreach ($results as $result)
				{
					$types[] = $result->alias;
				}
			}
			return $types;
		}
		return $results;
	}

	/**
	 * Get records
	 *
	 * @param   array  $filters  Filters to build query from
	 * @return  array
	 */
	public function getRecords($filters = array())
	{
		$query  = "SELECT c.*";
		$query .= $this->_buildQuery($filters);

		if (!isset($filters['sort']) || !$filters['sort'])
		{
			$filters['sort'] = 'id';
		}
		if (!isset($filters['sort_Dir']) || !$filters['sort_Dir'])
		{
			$filters['sort_Dir'] = 'DESC';
		}
		$query .= " ORDER BY " . $filters['sort'] . " " . $filters['sort_Dir'];

		if (isset($filters['limit']) && $filters['limit'] != 0)
		{
			$query .= ' LIMIT ' . $filters['start'] . ',' . $filters['limit'];
		}

		$this->_db->setQuery($query);
		return $this->_db->loadObjectList();
	}

	/**
	 * Get record counts
	 *
	 * @param   array  $filters  Filters to build query from
	 * @return  array
	 */
	public function getCount($filters = array())
	{
		$filters['limit'] = 0;

		$query = "SELECT COUNT(*) " . $this->_buildQuery($filters);

		$this->_db->setQuery($query);
		return $this->_db->loadResult();
	}

	/**
	 * Build a query from filters
	 *
	 * @param   array   $filters  Filters to build query from
	 * @return  string  SQL
	 */
	protected function _buildQuery($filters = array())
	{
		$query  = "FROM $this->_tbl AS c";

		$where = array();

		if (count($where) > 0)
		{
			$query .= " WHERE ";
			$query .= implode(" AND ", $where);
		}

		return $query;
	}

	/**
	 * Check type usage
	 *
	 * @param   integer  $id  type id
	 * @return  integer
	 */
	public function checkUsage($id = null)
	{
		if (!$id)
		{
			$id = $this->id;
		}
		if (!$id)
		{
			return false;
		}

		include_once __DIR__ . DS . 'publication.php';

		$p = new \Components\Publications\Tables\Publication($this->_db);

		$this->_db->setQuery("SELECT count(*) FROM $p->_tbl WHERE master_type=" . $this->_db->quote($id));
		return $this->_db->loadResult();
	}

	/**
	 * Load by ordering
	 *
	 * @param   mixed  $ordering  Integer or string (alias)
	 * @return  mixed  False if error, Object on success
	 */
	public function loadByOrder($ordering = null)
	{
		if ($ordering === null)
		{
			return false;
		}

		$this->_db->setQuery("SELECT * FROM $this->_tbl WHERE ordering=" . $this->_db->quote($ordering) . " LIMIT 1");
		if ($result = $this->_db->loadAssoc())
		{
			return $this->bind($result);
		}
		else
		{
			$this->setError($this->_db->getErrorMsg());
			return false;
		}
	}

	/**
	 * Change order
	 *
	 * @param   integer  $dir
	 * @return  mixed    False if error, Object on success
	 */
	public function changeOrder($dir)
	{
		$newOrder = $this->ordering + $dir;

		// Load record in prev position
		$old = new self($this->_db);

		if ($old->loadByOrder($newOrder))
		{
			$old->ordering  = $this->ordering;
			$old->store();
		}

		$this->ordering = $newOrder;
		$this->store();

		return true;
	}
}
