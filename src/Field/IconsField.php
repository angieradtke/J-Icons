<?php
/**
 * @package     Joomla.Plugin
 * @subpackage  System.jicon
 * @copyright   (C) 2026 Angie Radtke
 * @license     GNU/GPL
 */

namespace Joomla\Plugin\System\JIcon\Field;
use Joomla\CMS\Factory;
use Joomla\CMS\Form\Field\CheckboxesField;
use Joomla\CMS\Language\Text;

defined('_JEXEC') or die;

final class IconsField extends CheckboxesField
{
	protected $type = 'icons';
	private const ICON_BASE = '/plugins/system/jicon/media/icons';
	
	private array $importantIcons = [
		'house', 'solid/search', 'solid/user', 'solid/gear', 'solid/heart',
		'solid/star', 'solid/check', 'solid/times', 'solid/arrow-right', 'solid/arrow-left',
		'solid/bars', 'solid/bell', 'solid/envelope', 'solid/download', 'solid/upload',
		'solid/trash', 'solid/edit', 'solid/folder-open', 'solid/calendar', 'solid/eye',
		'brands/facebook', 'brands/x-twitter', 'brands/linkedin', 'brands/github', 'brands/joomla',
	];
	
	public function getOptions()
	{
		$iconPath = JPATH_SITE . self::ICON_BASE;
		$options  = [];

		// get icon-styles from media/icons
		$styles = [];
		foreach (glob($iconPath . '/*', GLOB_ONLYDIR) ?: [] as $dir) {
			$styles[] = basename($dir);
		}
		sort($styles);
		
		$category = '';
		if ($this->form) {
			$category = (string) $this->form->getValue('icon_category', 'params', '');
		}

		// load choosen style
		if ($category !== '' && in_array($category, $styles, true)) {
			$styles = [$category];
		}

		foreach ($styles as $style) {
			$stylePath = $iconPath . '/' . $style;
			if (!is_dir($stylePath)) {
				continue;
			}
			
			$styleIcons = [];
			foreach (scandir($stylePath) as $file) {
				if (pathinfo($file, PATHINFO_EXTENSION) !== 'svg') {
					continue;
				}
				$iconName = pathinfo($file, PATHINFO_FILENAME);
				$styleIcons[] = [
					'value' => $style . '/' . $iconName,
					'text'  => $iconName,
					'icon'  => $stylePath . '/' . $file,
				];
			}
			
			usort($styleIcons, static fn ($a, $b) => strcmp($a['text'], $b['text']));
			$options[] = [
				'value'   => $style,
				'text'    => ucfirst($style),
				'options' => $styleIcons,
			];
		}
		
		return $options;
	}
	
	public function getInput()
	{
		$groups = $this->getOptions();
		
		// get selected
		$selected = [];
		$rawValue = $this->value;
		
		if (is_array($rawValue)) {
			$selected = $rawValue;
		} elseif (is_string($rawValue) && $rawValue !== '') {
			// JSON
			$first = $rawValue[0] ?? '';
			if ($first === '[' || $first === '{') {
				$decoded = json_decode($rawValue, true);
				if (is_array($decoded)) {
					$selected = $decoded;
				}
			} else {
				$selected = array_map('trim', explode(',', $rawValue));
			}
		}
		
		$selected = array_filter($selected, static fn ($v) => !empty($v));
		
		// preselect Icons
		if (empty($selected)) {
			$selected = $this->importantIcons;
		}
		
		// load css / Js
		$doc = Factory::getApplication()->getDocument();
		$doc->addStyleSheet('../plugins/system/jicon/media/css/icon-picker.min.css');
		$fieldId = $this->id;
		// === HTML ===
		$html = '';
		// Hidden field nur als interner State-Holder, ohne name-Attribut, damit es die Checkbox-Werte nicht ueberschreibt
		$html .= '<input type="hidden" id="' . htmlspecialchars($fieldId, ENT_QUOTES) . '" ';
		$html .= 'value="' . htmlspecialchars(json_encode($selected), ENT_QUOTES) . '" />';
		$html .= '<div class="jicon-picker">';
		$html .= '<div class="jicon-toggle">';
		$html .= '<button class="btn btn-primary" type="button" id="' . htmlspecialchars($fieldId, ENT_QUOTES) . '_toggle">'. Text::_('PLG_SYSTEM_JICON_JICONTOGGLE').'</button>';
		$html .= '</div>';
		$html .= '<div class="jicon-container">';
		
		$important = [];
		foreach ($groups as $group) {
			foreach ($group['options'] as $icon) {
				if (in_array($icon['value'], $this->importantIcons, true)) {
					$important[] = $icon;
				}
			}
		}
		
		if (!empty($important)) {
			$html .= $this->renderGroup('Empfohlene Icons', $important, $selected, $fieldId);
		}
		
		// Alle Stile
		foreach ($groups as $group) {
			$html .= $this->renderGroup($group['text'], $group['options'], $selected, $fieldId);
		}
		
		$html .= '</div></div>';
		
		// JavaScript zur Synchronisierung (Checkboxen <-> Hidden State)  könnte eleganter ausgelagert
		
		$html .= '<script>
        (function() {
            const hiddenField = document.getElementById("' . htmlspecialchars($fieldId, ENT_QUOTES) . '");
            const checkboxes = document.querySelectorAll("input[name=\'' . htmlspecialchars($this->name, ENT_QUOTES) . '[]\']");
            const toggleButton = document.getElementById("' . htmlspecialchars($fieldId, ENT_QUOTES) . '_toggle");
            function updateHidden() {
                const checked = Array.from(checkboxes)
                    .filter(cb => cb.checked)
                    .map(cb => cb.value);
                hiddenField.value = JSON.stringify(checked);
                // console.log("Icons gespeichert:", checked);
            }
            
            function applyFromHidden() {
                if (!hiddenField) return;
                try {
                    const data = JSON.parse(hiddenField.value || "[]");
                    if (Array.isArray(data)) {
                        const values = new Set(data.map(String));
                        checkboxes.forEach(cb => {
                            cb.checked = values.has(cb.value);
                        });
                    }
                } catch (e) {
                    // ignore invalid JSON
                }
            }
            
      function toggleAll() {
        const allChecked = Array.from(checkboxes).every(cb => cb.checked);
        checkboxes.forEach(cb => {
            cb.checked = !allChecked;
            cb.dispatchEvent(new Event("change", { bubbles: true }));
        });
    }
            //  from Hidden to Checkboxen
            applyFromHidden();
            updateHidden();
            
                 checkboxes.forEach(cb => cb.addEventListener("change", updateHidden));
			    if (toggleButton) {
               toggleButton.addEventListener("click", toggleAll);}
        })();
        </script>';
		
		return $html;
	}
	
	private function renderGroup(string $label, array $icons, array $selected, string $fieldId): string
	{
		$html = '<fieldset class="jicon-group"><legend>' . htmlspecialchars($label, ENT_QUOTES) . '</legend>';
		$html .= '<div class="jicon-grid">';
		
		foreach ($icons as $icon) {
			$value = (string) $icon['value'];
			$text = (string) $icon['text'];
			$isChecked = in_array($value, $selected, true);
			$checked = $isChecked ? ' checked' : '';
			$svg = '';
			
			if (!empty($icon['icon']) && is_file($icon['icon'])) {
				$content = file_get_contents($icon['icon']);
				if ($content && preg_match('/<svg[^>]*>.*?<\/svg>/is', $content, $m)) {
					$svg = $m[0];
				}
			}
			
			$html .= '<label class="jicon-option">';
			if ($svg) {
				$html .= '<span class="jicon-preview" aria-hidden="true">' . $svg . '</span>';
			}
			$html .= '<span class="jicon-label" style="display:block; margin-top:8px">' . htmlspecialchars($text, ENT_QUOTES) . '</span>';
			$html .= '<input type="checkbox" name="' . htmlspecialchars($this->name, ENT_QUOTES) . '[]" ';
			$html .= 'value="' . htmlspecialchars($value, ENT_QUOTES) . '"' . $checked . ' />';
			$html .= '</label>';
		}
		
		$html .= '</div></fieldset>';
		return $html;
	}
}
