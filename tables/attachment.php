<?php
/**
 * @package    hubzero-cms
 * @copyright  Copyright (c) 2005-2020 The Regents of the University of California.
 * @license    http://opensource.org/licenses/MIT MIT
 */

namespace Components\Publications\Tables;

use Hubzero\Database\Table;

/**
 * Table class for publication attachments
 */
class Attachment extends Table
{
	/**
	 * Constructor
	 *
	 * @param   object  &$db  Database
	 * @return  void
	 */
	public function __construct(&$db)
	{
		parent::__construct('#__publication_attachments', 'id', $db);
	}

	/**
	 * Validate data
	 *
	 * @return  boolean  True if data is valid
	 */
	public function check()
	{
		if (!$this->id)
		{
			$this->ordering = (intval($this->getLastOrder()) + 1);
		}

		return true;
	}

	/**
	 * Get array of file attachments
	 *
	 * @param   integer  $versionid  Pub version ID
	 * @param   string   $role
	 * @return  mixed    object or false
	 */
	public function getAttachmentsArray($versionid = null, $role = '')
	{
		if ($versionid === null)
		{
			return false;
		}

		$result = array();

		$filters = array('role' => $role);
		$items = $this->getAttachments($versionid, $filters);

		if ($items)
		{
			foreach ($items as $item)
			{
				$result[] = $item->path;
			}
		}

		return $result;
	}

	/**
	 * Get attachment used as default publication thumbnail
	 *
	 * @param   integer  $versionid  pub version id
	 * @return  object
	 */
	public function getDefault($versionid)
	{
		if ($versionid === null)
		{
			$versionid = $this->publication_version_id;
		}

		if (!$versionid)
		{
			return false;
		}

		$query  = "SELECT * FROM $this->_tbl WHERE publication_version_id=" . $this->_db->quote($versionid);
		$query .= " AND params LIKE '%pubThumb=1%' LIMIT 1";

		$this->_db->setQuery($query);
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
	 * Save project parameter
	 *
	 * @param   object  $record
	 * @param   string  $param
	 * @param   string  $value
	 * @return  void
	 */
	public function saveParam($record = null, $param = '', $value = 0)
	{
		if (!is_object($record))
		{
			return false;
		}

		// Clean up value
		$value = preg_replace('/=/', '', $value);

		if ($record->params)
		{
			$params = explode("\n", $record->params);
			$in = '';
			$found = 0;

			// Change param
			if (!empty($params))
			{
				foreach ($params as $p)
				{
					if (trim($p) != '' && trim($p) != '=')
					{
						$extracted = explode('=', $p);
						if (!empty($extracted))
						{
							$in .= $extracted[0] . '=';
							$default = isset($extracted[1]) ? $extracted[1] : 0;
							$in .= $extracted[0] == $param ? $value : $default;
							$in	.= "\n";
							if ($extracted[0] == $param) {
								$found = 1;
							}
						}
					}
				}
			}
			if (!$found)
			{
				$in .= "\n" . $param . '=' . $value;
			}
		}
		else
		{
			$in = $param . '=' . $value;
		}

		$record->params = $in;
		$record->store();
	}

	/**
	 * Update records for all publications in a project
	 *
	 * @param   integer  $projectid  project id
	 * @param   string   $field
	 * @param   string   $from
	 * @param   string   $to
	 * @return  bool
	 */
	public function updateRecords($projectid, $field, $from, $to)
	{
		$query = "UPDATE $this->_tbl AS A ";
		$query.= "JOIN #__publications AS B ON B.id=A.publication_id ";
		$query.= " SET A." . $field . "=" . $this->_db->quote($to);
		$query.= " WHERE A." . $field . "=" . $this->_db->quote($from)
				. " AND B.project_id=" . $this->_db->quote($projectid);

		$this->_db->setQuery($query);
		if ($this->_db->query())
		{
			return true;
		}
		return false;
	}

	/**
	 * Get attachments
	 *
	 * @param   integer  $versionid  pub version id
	 * @return  mixed    Array or bool
	 */
	public function sortAttachments($versionid)
	{
		if ($versionid === null)
		{
			$versionid = $this->publication_version_id;
		}

		if (!$versionid)
		{
			return false;
		}

		$query  = "SELECT a.*";
		$query .= " FROM $this->_tbl AS a";
		$query .= " WHERE a.publication_version_id=" . $this->_db->quote($versionid);
		$query .= " ORDER BY a.ordering ASC";

		$this->_db->setQuery($query);
		$results = $this->_db->loadObjectList();

		if ($results)
		{
			$attachments = array(
				'1'        => array(),
				'2'        => array(),
				'3'        => array(),
				'elements' => array()
			);

			foreach ($results as $result)
			{
				// Sort by role
				if ($result->role == 1)
				{
					$attachments['1'][] = $result;
				}
				elseif ($result->role == 2 || $result->role == 0)
				{
					$attachments['2'][] = $result;
				}
				else
				{
					$attachments['3'][] = $result;
				}

				$elementId = $result->element_id ? $result->element_id : 1;

				if (!isset($attachments['elements'][$elementId]))
				{
					$attachments['elements'][$elementId] = array();
				}

				// Add to elements array
				$attachments['elements'][$elementId][] = $result;
			}

			return $attachments;
		}

		return false;
	}

	/**
	 * Get connections
	 *
	 * @param   integer  $vid   pub version id
	 * @param   array    $find
	 * @return  object
	 */
	public function getConnections($vid = null, $find = array())
	{
		if (!$vid)
		{
			$vid = $this->publication_version_id;
		}
		if (!$vid)
		{
			return false;
		}
		if (empty($find))
		{
			return false;
		}

		$query  = "SELECT * FROM $this->_tbl WHERE publication_version_id=" . $this->_db->quote($vid);
		foreach ($find as $property => $value)
		{
			$query .= " AND " . $property . "=" . $this->_db->quote($value);
		}

		$this->_db->setQuery($query);
		return $this->_db->loadObjectList();
	}

	/**
	 * Get attachments
	 *
	 * @param   integer  $versionid  pub version id
	 * @param   array    $filters
	 * @return  object
	 */
	public function getAttachments($versionid, $filters=array())
	{
		if ($versionid === null)
		{
			$versionid = $this->publication_version_id;
		}

		$project = isset($filters['project']) && $filters['project'] != '' ? intval($filters['project']) : '';
		$count   = isset($filters['count']) && $filters['count'] == 1 ? 1 : 0;
		$select  = isset($filters['select']) && $filters['select'] != '' ? $filters['select'] : '';
		$aid     = isset($filters['id']) && intval($filters['id']) != 0 ? intval($filters['id']) : '';
		$type    = isset($filters['type']) && $filters['type'] != '' ? $filters['type'] : '';
		$element = isset($filters['element']) ? $filters['element'] : '';

		if ($versionid === null && !$project)
		{
			return false;
		}

		$query = $count ? "SELECT COUNT(*) " : "SELECT a.* ";
		if ($project && !$count)
		{
			$query.= ", p.id as publication_id, p.project_id  ";
		}
		if ($select)
		{
			$query = "SELECT ".$select." ";
		}
		elseif ($type == 'publication')
		{
			$query.= ", p.id as publication_id, p.project_id  ";
		}

		$query.= "FROM $this->_tbl AS a ";
		if ($project || $type)
		{
			$query .= "JOIN #__publication_versions AS v ON v.id=a.publication_version_id ";
			$query .= "JOIN #__publications AS p ON p.id=v.publication_id ";
		}
		$query.= "WHERE ";
		if ($aid)
		{
			$query .= " a.id='".$aid."' LIMIT 1 ";
			$this->_db->setQuery($query);
			return $this->_db->loadObjectList();
		}
		else
		{
			$query.= intval($project)
				? " p.project_id=" . $this->_db->quote($project)
				: " a.publication_version_id=" . $this->_db->quote($versionid);
		}
		if ($element)
		{
			if (intval($element))
			{
				$query .= " AND a.element_id=" . $this->_db->quote($element);
			}
			elseif (is_array($element))
			{
				// multiple elements, TBD
			}
		}
		if (isset($filters['role']) && $filters['role'] != '')
		{
			if (is_array($filters['role']))
			{
				$tquery = '';
				foreach ($filters['role'] as $role)
				{
					$tquery .= $this->_db->quote($role) . ",";
				}
				$tquery = substr($tquery, 0, strlen($tquery) - 1);
				$query .= " AND (a.role IN (" . $tquery . ")) ";
			}
			elseif ($filters['role'] == 4)
			{
				$query .= " AND (a.role=0 OR a.role=2) ";
			}
			else
			{
				$query .= " AND a.role=" . $this->_db->quote($filters['role']);
			}
		}
		if ($type)
		{
			$query .= " AND a.type=" . $this->_db->quote($type);
		}
		if (isset($filters['order']) && $filters['order'] != '')
		{
			$query .= " ORDER BY " . $filters['order'];
		}
		else
		{
			$query .= " ORDER BY a.ordering ASC";
		}
		if (isset($filters['limit']) && $filters['limit'] != 0 && !$count)
		{
			$query .= " LIMIT " . $filters['start'] . "," . $filters['limit'];
		}

		$this->_db->setQuery($query);
		return $count ? $this->_db->loadResult() : $this->_db->loadObjectList();
	}
	
	/**
	 * Get attachments count
	 *
	 * @param   integer  $versionid  pub version id
	 * @return  int
	 */
	public function getAttachmentsCount($versionid)
	{
		if ($versionid === null)
		{
			$versionid = $this->publication_version_id;
		}
		
		$query = "SELECT COUNT(*) FROM $this->_tbl AS a where a.publication_version_id = " . $this->_db->quote($versionid);
		$this->_db->setQuery($query);
		
		return $this->_db->loadResult();
	}

	/**
	 * Delete element attachment
	 *
	 * @param   integer  $vid
	 * @param   array    $find
	 * @param   integer  $elementId
	 * @param   string   $type
	 * @param   string   $role
	 * @return  mixed    object or FALSE
	 */
	public function deleteElementAttachment($vid = null, $find = array(), $elementId = 0, $type = 'file', $role = '')
	{
		if (!$vid)
		{
			$vid = $this->publication_version_id;
		}
		if (!$vid)
		{
			return false;
		}
		if (empty($find))
		{
			return false;
		}

		$query  = "DELETE FROM $this->_tbl WHERE publication_version_id=" . $this->_db->quote($vid)
				. " AND type=" . $this->_db->quote($type);
		$query .= " AND (element_id=0 OR element_id=" . intval($elementId) . ")";

		if ($role)
		{
			$query .= $role == 2 ? " AND (role = 0 OR role = 2)" : " AND role=" . intval($role);
		}

		foreach ($find as $property => $value)
		{
			$query .= " AND " . $property ."=" . $this->_db->quote($value);
		}

		$this->_db->setQuery($query);
		if (!$this->_db->query())
		{
			$this->setError($this->_db->getErrorMsg());
			return false;
		}
		return true;
	}

	/**
	 * Load element attachment
	 *
	 * @param   integer  $vid
	 * @param   array    $find
	 * @param   integer  $elementId
	 * @param   string   $type
	 * @param   string   $role
	 * @return  mixed    object or FALSE
	 */
	public function loadElementAttachment($vid = null, $find = array(), $elementId = 0, $type = 'file', $role = '')
	{
		if (!$vid)
		{
			$vid = $this->publication_version_id;
		}
		if (!$vid)
		{
			return false;
		}
		if (empty($find))
		{
			return false;
		}

		$query  = "SELECT * FROM $this->_tbl WHERE publication_version_id=" . $this->_db->quote($vid)
				. " AND type=" . $this->_db->quote($type);
		$query .= " AND (element_id=0 OR element_id=" . intval($elementId) . ")";

		if ($role)
		{
			$query .= $role == 2 ? " AND (role = 0 OR role = 2)" : " AND role=" . intval($role);
		}

		foreach ($find as $property => $value)
		{
			$query .= " AND " . $property ."=" . $this->_db->quote($value);
		}

		$query .= " LIMIT 1";

		$this->_db->setQuery($query);
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
	 * Load entry by version and path/object
	 *
	 * @param   integer  $vid         pub version id
	 * @param   string   $identifier  Attached object identifier (e.g. content path for files)
	 * @param   string   $type        Attachment type
	 * @return  mixed    object or FALSE
	 */
	public function loadAttachment($vid = null, $identifier = null, $type = 'file')
	{
		if (!$vid)
		{
			$vid = $this->publication_version_id;
		}
		if (!$vid)
		{
			return false;
		}
		if (!$identifier)
		{
			return false;
		}

		$query  = "SELECT * FROM $this->_tbl WHERE publication_version_id=" . $this->_db->quote($vid)
				. " AND type=" . $this->_db->quote($type);

		// Get types helper
		$attach = new \Components\Publications\Models\Attachments($this->_db);
		$prop = $attach->connector($type);
		$prop = $prop ? $prop : 'path';

		$query .= " AND " . $prop . "=" . $this->_db->quote($identifier);

		$this->_db->setQuery($query);
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
	 * Copy entry
	 *
	 * @param   object   $att
	 * @param   integer  $vid
	 * @param   integer  $elementId
	 * @param   integer  $uid
	 * @return  object   object or FALSE
	 */
	public function copyAttachment($att = null, $vid = null, $elementId = null, $uid = 0)
	{
		$pAttach = new self($this->_db);
		$pAttach->publication_id         = $att->publication_id;
		$pAttach->title                  = $att->title;
		$pAttach->access                 = $att->access;
		$pAttach->role                   = $att->role;
		$pAttach->element_id             = $elementId;
		$pAttach->path                   = $att->path;
		$pAttach->vcs_hash               = $att->vcs_hash;
		$pAttach->vcs_revision           = $att->vcs_revision;
		$pAttach->object_id              = $att->object_id;
		$pAttach->object_name            = $att->object_name;
		$pAttach->object_instance        = $att->object_instance;
		$pAttach->object_revision        = $att->object_revision;
		$pAttach->type                   = $att->type;
		$pAttach->params                 = $att->params;
		$pAttach->attribs                = $att->attribs;
		$pAttach->ordering               = $att->ordering;
		$pAttach->publication_version_id = $vid;
		$pAttach->created_by             = $uid;
		$pAttach->created                = \Date::toSql();
		if ($pAttach->store())
		{
			return $this->bind($pAttach);
		}
		return false;
	}

	/**
	 * Load entry by version and path
	 *
	 * @param   integer  $vid         pub version id
	 * @param   string   $identifier  content path or object id/name
	 * @return  boolean
	 */
	public function deleteAttachment($vid = null, $identifier = null, $type = 'file')
	{
		if (!$vid)
		{
			$vid = $this->publication_version_id;
		}
		if (!$vid)
		{
			return false;
		}
		if (!$identifier)
		{
			return false;
		}

		$attach = new \Components\Publications\Models\Attachments($this->_db);
		$prop = $attach->connector($type);
		$prop = $prop ? $prop : 'path';

		$query  = "DELETE FROM $this->_tbl WHERE publication_version_id=" . $this->_db->quote($vid);
		$query .= " AND " . $prop . "=" . $this->_db->quote($identifier);

		$this->_db->setQuery($query);
		if (!$this->_db->query())
		{
			$this->setError($this->_db->getErrorMsg());
			return false;
		}
		return true;
	}

	/**
	 * Load entries by pub version
	 *
	 * @param   integer  $vid  pub version id
	 * @return  boolean
	 */
	public function deleteAttachments($vid = null)
	{
		if (!$vid)
		{
			$vid = $this->publication_version_id;
		}
		if (!$vid)
		{
			return false;
		}

		$query = "DELETE FROM $this->_tbl WHERE publication_version_id=" . $this->_db->quote($vid);
		$this->_db->setQuery($query);
		if (!$this->_db->query())
		{
			$this->setError($this->_db->getErrorMsg());
			return false;
		}
		return true;
	}

	/**
	 * Check version duplicate
	 *
	 * @param   integer  $pid
	 * @param   integer  $vid
	 * @param   string   $base
	 * @return  mixed
	 */
	public function getVersionAttachments($pid = null, $vid = null)
	{
		if (!intval($pid) || !intval($vid))
		{
			return false;
		}

		$query  = "SELECT A.*, V.version_label, V.version_number FROM $this->_tbl AS A ";
		$query .= " JOIN #__publication_versions AS V ON A.publication_version_id = V.id ";
		$query .= " WHERE A.publication_id = " . $this->_db->quote($pid)
				. " AND A.publication_version_id !=" . $this->_db->quote($vid);
		$query .= " AND V.state!='3' AND V.main=1 AND A.role=1 ";
		$query .= " ORDER BY A.ordering";

		$this->_db->setQuery($query);
		return $this->_db->loadObjectList();
	}

	/**
	 * Get pub association
	 *
	 * @param   integer  $projectid
	 * @param   string   $path
	 * @param   string   $hash
	 * @param   integer  $primary
	 * @return  mixed    array or false
	 */
	public function getPubAssociation($projectid = 0, $path = '', $hash = '', $primary = 1)
	{
		if (!$projectid || (!$hash && !$path))
		{
			return false;
		}

		$pub = array(
			'id'=> '',
			'title' => '',
			'version' => 'default',
			'version_label' => ''
		);

		$query  = "SELECT a.publication_id , v.title, v.version_number, v.version_label FROM $this->_tbl as a ";
		$query .= "JOIN #__publication_versions AS v ON v.id=a.publication_version_id  ";
		$query .= "JOIN #__publications AS P ON P.id=v.publication_id  ";
		$query .= " WHERE P.project_id=" . $this->_db->quote($projectid);
		$query .= $hash ? " AND a.vcs_hash=" . $this->_db->quote($hash) : " AND a.path=" . $this->_db->quote($path);
		$query .= $primary ? " AND a.role=1 " : "";
		$query .= " ORDER BY v.id DESC LIMIT 1";

		$this->_db->setQuery($query);
		$result = $this->_db->loadObjectList();

		if ($result)
		{
			$pub['id']            = $result[0]->publication_id;
			$pub['title']         = $result[0]->title;
			$pub['version']       = $result[0]->version_number;
			$pub['version_label'] = $result[0]->version_label;
		}

		return $pub;
	}

	/**
	 * Get pub associations
	 *
	 * @param   integer  $projectid
	 * @param   string   $type
	 * @param   integer  $primary
	 * @return  mixed    array or false
	 */
	public function getPubAssociations($projectid = 0, $type = 'file', $primary = 1)
	{
		if (!$projectid)
		{
			return false;
		}

		$assoc = array();

		$query  = "SELECT a.path, a.publication_id , v.title, v.version_number, v.version_label FROM $this->_tbl as a ";
		$query .= "JOIN #__publication_versions AS v ON v.id=a.publication_version_id ";
		$query .= "JOIN #__publications AS P ON P.id=v.publication_id ";
		$query .= " WHERE P.project_id=" . $this->_db->quote($projectid) . " AND a.type=" . $this->_db->quote($type);
		$query .= $primary ? " AND a.role=1 " : "";
		$query .= " GROUP BY a.path ";
		$query .= " ORDER BY v.id DESC ";

		$this->_db->setQuery($query);
		$result = $this->_db->loadObjectList();

		if ($result)
		{
			foreach ($result as $r)
			{
				$pub = array();
				$pub['id']            = $r->publication_id;
				$pub['title']         = $r->title;
				$pub['version']       = $r->version_number;
				$pub['version_label'] = $r->version_label;

				$assoc[$r->path][] = $pub;
			}
		}

		return $assoc;
	}

	/**
	 * Reorder an attachment
	 *
	 * @param   string  $dir
	 * @return  boolean
	 */
	public function reorder($dir = 'down')
	{
		$neighbor = $this->getNeighbor($dir);

		switch ($dir)
		{
			case 'up':
				// Switch places: give item 1 the position of item 2, vice versa
				$orderup = $neighbor->ordering;
				$orderdn = $this->ordering;

				$this->ordering = $orderup;
				$neighbor->ordering = $orderdn;
			break;

			case 'down':
				// Switch places: give item 1 the position of item 2, vice versa
				$orderup = $this->ordering;
				$orderdn = $neighbor->ordering;

				$this->ordering = $orderdn;
				$neighbor->ordering = $orderup;
			break;
		}

		// Save changes
		$this->store();
		if ($neighbor->id)
		{
			$neighbor->store();
		}

		// Rebuild the ordering
		$this->rebuild();

		return true;
	}

	/**
	 * Get the record directly before or after this record
	 *
	 * @param   string   $dir  Direction to look
	 * @return  boolean  True on success
	 */
	public function getNeighbor($dir = 'down')
	{
		$neighbor = new self($this->_db);

		switch ($dir)
		{
			case 'up':
				$sql = "SELECT * FROM `$this->_tbl` WHERE publication_id=" . $this->_db->quote($this->publication_id) . " AND publication_version_id=" . $this->_db->quote($this->publication_version_id) . " AND ordering < " . $this->_db->quote($this->ordering) . " ORDER BY ordering DESC LIMIT 1";
			break;

			case 'down':
				$sql = "SELECT * FROM `$this->_tbl` WHERE publication_id=" . $this->_db->quote($this->publication_id) . " AND publication_version_id=" . $this->_db->quote($this->publication_version_id) . " AND ordering > " . $this->_db->quote($this->ordering) . " ORDER BY ordering LIMIT 1";
			break;
		}
		$this->_db->setQuery($sql);
		if ($result = $this->_db->loadAssoc())
		{
			$neighbor->bind($result);
		}

		return $neighbor;
	}

	/**
	 * Get the last number in an ordering
	 *
	 * @return  integer
	 */
	public function getLastOrder()
	{
		$this->_db->setQuery("SELECT `ordering` FROM $this->_tbl WHERE publication_id=" . $this->_db->quote($this->publication_id) . " AND publication_version_id=" . $this->_db->quote($this->publication_version_id) . " ORDER BY ordering DESC LIMIT 1");
		return $this->_db->loadResult();
	}

	/**
	 * Rebuild the ordering values
	 *
	 * @return  boolean  True on success
	 */
	public function rebuild()
	{
		$sql = "SELECT id FROM `$this->_tbl` WHERE publication_id=" . $this->_db->quote($this->publication_id) . " AND publication_version_id=" . $this->_db->quote($this->publication_version_id) . " ORDER BY ordering ASC, id ASC";

		$this->_db->setQuery($sql);
		$rows = $this->_db->loadObjectList();

		if ($rows)
		{
			foreach ($rows as $i => $row)
			{
				$sql = "UPDATE `$this->_tbl` SET `ordering`=" . ($i + 1) . " WHERE id=" . $this->_db->quote($row->id);
				$this->_db->setQuery($sql);
				if (!$this->_db->query())
				{
					$this->setError($this->_db->getErrorMsg());
					return false;
				}
			}
		}

		return true;
	}

	/**
	 * Get array of series that the publication belongs to
	 *
	 * @param   integer  $versionID  Publication version ID
	 *
	 * @return  mixed    series  or false
	 */
	public function getSeries($versionId)
	{
		if ($versionId === null)
		{
			return false;
		}

		// Get publication version id of series
		$query = "SELECT publication_version_id FROM `$this->_tbl` WHERE object_id=$versionId";
		$this->_db->setQuery($query);
		$publicationVersionIds = $this->_db->loadColumn();

		if (!empty($publicationVersionIds)) {
			$versionIdSqlArray = '(' . implode(',', $publicationVersionIds) . ')';
			$seriesQuery = "SELECT publication_id, title, abstract, version_number"
			. " FROM `#__publication_versions`"
			. " WHERE id in $versionIdSqlArray";
			$this->_db->setQuery($seriesQuery);
			$series = $this->_db->loadObjectList();
		}
		else
		{
			$series = [];
		}

		return $series;
	}
}
