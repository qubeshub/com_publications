<?php
/**
 * @package    hubzero-cms
 * @copyright  Copyright (c) 2005-2020 The Regents of the University of California.
 * @license    http://opensource.org/licenses/MIT MIT
 */

namespace Components\Publications\Models\Block;

use Components\Publications\Models\Block\Content as Base;
use stdClass;

/**
 * Content block
 */
class Extras extends Base
{
	/**
  * Block name
  *
  * @var		string
  */
	protected	$_name 			= 'extras';

	/**
  * Parent block name
  *
  * @var		string
  */
	protected	$_parentname 	= 'content';

	/**
  * Default manifest
  *
  * @var		string
  */
	protected	$_manifest 		= null;

	/**
  * Numeric block ID
  *
  * @var		integer
  */
	protected	$_blockId 		= 0;

	/**
	 * Get default manifest for the block
	 *
	 * @return  void
	 */
	public function getManifest($new = false)
	{
		// Load config from db
		$obj = new \Components\Publications\Tables\Block($this->_parent->_db);
		$manifest = $obj->getManifest($this->_name);

		// Fall back
		if (!$manifest)
		{
			$manifest = array(
				'name' 			=> 'extras',
				'label' 		=> 'Extras',
				'title' 		=> Lang::txt('COM_PUBLICATIONS_BLOCKS_EXTRAS_TITLE'),
				'draftHeading' 	=> Lang::txt('COM_PUBLICATIONS_BLOCKS_EXTRAS_DRAFT_HEADING'),
				'draftTagline'	=> Lang::txt('COM_PUBLICATIONS_BLOCKS_EXTRAS_DRAFT_TAGLINE'),
				'about'			=> '',
				'adminTips'		=> '',
				'elements' 		=> array(
					2 => array (
						'name'		=> 'dataselector',
						'type' 		=> 'attachment',
						'label'		=> 'Image Gallery',
						'about'		=> Lang::txt('COM_PUBLICATIONS_BLOCKS_EXTRAS_IMG_GALLERY_ABOUT'),
						'aboutProv'	=> Lang::txt('COM_PUBLICATIONS_BLOCKS_EXTRAS_IMG_GALLERY_ABOUT_PROV'),
						'adminTips'	=> '',
						'params' 	=> array (
							'type'			=> 'file',
							'title'			=> '',
							'required' 		=> 0,
							'min' 			=> 0,
							'max' 			=> 50,
							'role' 			=> 3,
							'typeParams'	=> array(
								'allowed_ext' 		=> array('gif', 'jpg', 'jpeg', 'png', 'bmp'),
								'required_ext'  	=> array(),
								'handler'			=> 'imageviewer',
								'handlers'			=> null,
								'directory'			=> 'gallery',
								'reuse' 			=> 1,
								'dirHierarchy' 		=> 0,
								'multiZip'			=> 0
							)
						)
					),
					3 => array (
						'name'		=> 'dataselector',
						'type' 		=> 'attachment',
						'label'		=> 'Supporting Docs',
						'about'		=> Lang::txt('COM_PUBLICATIONS_BLOCKS_EXTRAS_SUPP_DOCS_ABOUT'),
						'aboutProv'	=> Lang::txt('COM_PUBLICATIONS_BLOCKS_EXTRAS_SUPP_DOCS_ABOUT_PROV'),
						'adminTips'	=> '',
						'params' 	=> array (
							'type'			=> 'file',
							'title'			=> '',
							'required' 		=> 0,
							'min' 			=> 0,
							'max' 			=> 500,
							'role' 			=> 2,
							'typeParams'	=> array(
								'allowed_ext' 		=> array(),
								'required_ext'  	=> array(),
								'handler'			=> null,
								'handlers'			=> null,
								'directory'			=> '',
								'reuse' 			=> 1,
								'dirHierarchy' 		=> 1,
								'multiZip'			=> 0
							)
						)
					)
				),
				'params'	=> array(
					'required' 			=> 0,
					'published_editing' => 1,
					'collapse_elements' => 0,
					'verify_types'		=> 1
				)
			);

			return json_decode(json_encode($manifest), false);
		}

		return $manifest;
	}

    /**
	 * Reorder attachments
	 *
	 * @param   object   $manifest
	 * @param   integer  $blockId
	 * @param   object   $pub
	 * @param   integer  $actor
	 * @param   integer  $elementId
	 * @return  string   HTML
	 */
	public function reorder($manifest = null, $blockId = 0, $pub = null, $actor = 0, $elementId = 0)
	{
		// Set block manifest
		if ($this->_manifest === null)
		{
			$this->_manifest = $manifest ? $manifest : self::getManifest();
		}

		// Incoming
		$list = Request::getString('list', '');
		$attachments = explode("-", $list);

		$o = 1;
		foreach ($attachments as $id)
		{
			if (!trim($id))
			{
				continue;
			}

			$pAttachment = new \Components\Publications\Tables\Attachment($this->_parent->_db);
			if ($pAttachment->loadElementAttachment($pub->version_id, array('id' => $id), $elementId))
			{
				$pAttachment->ordering = $o;
				$o++;

				$pAttachment->store();
			}
		}

		$this->set('_message', Lang::txt('New attachment order saved'));

		return true;
	}
}
