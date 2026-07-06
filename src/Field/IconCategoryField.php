<?php
/**
 * @package     Joomla.Plugin
 * @subpackage  System.jicon
 * @copyright   (C) 2026 Angie Radtke
 * @license     GNU/GPL
 */

namespace Joomla\Plugin\System\JIcon\Field;

use Joomla\CMS\Form\Field\ListField;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;

defined('_JEXEC') or die;

/**
 * Selection field for the icon category.
 * Dynamically reads the subdirectories of media/icons and displays the number of icons for each category.
 */
final class IconCategoryField extends ListField
{
    protected $type = 'IconCategory';
    private const ICON_BASE = '/plugins/system/jicon/media/icons';

    protected function getOptions()
    {
        // "All Categories" first
        $options = [
            HTMLHelper::_('select.option', '', Text::_('PLG_SYSTEM_JICON_CATEGORY_ALL')),
        ];

        $base = JPATH_SITE . self::ICON_BASE;

        if (is_dir($base)) {
            $dirs = glob($base . '/*', GLOB_ONLYDIR) ?: [];
            sort($dirs);

            foreach ($dirs as $dir) {
                $name  = basename($dir);
                $count = count(glob($dir . '/*.svg') ?: []);
                $label = ucfirst($name) . ' (' . $count . ')';

                $options[] = HTMLHelper::_('select.option', $name, $label);
            }
        }

        return array_merge($options, parent::getOptions());
    }
}
