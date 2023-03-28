<?php
/**
 * @package    hubzero-cms
 * @copyright  Copyright (c) 2005-2020 The Regents of the University of California.
 * @license    http://opensource.org/licenses/MIT MIT
 */

// No direct access.
defined('_HZEXEC_') or die();

$html = '';

switch ($this->stage) {
    case 'before':
        $ptag = $this->parent->tag->get('tag');
        $active = Request::getString($ptag, '');
        $display = '';
        if ($active === 'active') {
            $display = '';
        } elseif ($this->depth > 1 && $active !== 'active') {
            $display = ' style="display:none;"';
        }
        if (count($this->children)) {
            $html .= '<input type="hidden" name="' . $ptag . '" value="' . $active . '">';
            $html .= '<ul name="tagfa-' . $this->props['root'] . '[' . $ptag . ']" id="tagfa-' . $ptag . '" class="option"' . $display . '>';
        }
    break;
    case 'during':
        $ptag = $this->parent->tag->get('tag');
        $ctag = $this->child->tag->get('tag');
        $count = $this->props['facets'][$ptag . '.' . $ctag];
        $depth = $this->depth;
        $checked = in_array($ctag, $this->props['filters']);
        $html .= '<li class="filter-option" ' . ($count == 0 ? ' style="display:none;"' : '') . '>';
        $html .= '<label>';
        $html .= '<input class="option" type="checkbox" id="tagfa-' . $ctag . '" name="tagfa-' . $this->props['root'] . '[' . $ptag . '][]" value="' . $ptag . '.' . $ctag . '"' . ($checked ? ' checked' : '') . '/><span class="tagfa-label">' . $this->child->label . '</span>';
        $html .= '<span class="facet-count">(' . $count . ')</span>';
        $html .= '</label>';
        $html .= $this->child->render('filter', $this->props, ++$depth); 
        $html .= "</li>";
    break;
    case 'after':
        if (count($this->children)) {
            $html .= '</ul>';
        }
    break;
}

echo $html;

return;