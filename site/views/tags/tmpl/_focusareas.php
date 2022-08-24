<?php
/**
 * @package    hubzero-cms
 * @copyright  Copyright (c) 2005-2020 The Regents of the University of California.
 * @license    http://opensource.org/licenses/MIT MIT
 */

// No direct access.
defined('_HZEXEC_') or die();

// Get focus area class
$focus_area = new \Components\Tags\Models\FocusArea;

// FAs from tags
$fas = $focus_area::fromTags($this->tags);
$focus_area->set('subset', $fas->copy()->rows()->fieldsByKey('id'));
$roots = $focus_area::parents($fas, $null = true); // Get root FAs

$html = '';
foreach ($roots as $root) {
    $html .= '<h5>' . $root->label . '</h5>';
    $html .= $focus_area->render($root->id, 'html');
}

echo $html;

return;