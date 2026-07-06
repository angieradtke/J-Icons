<?php
/**
 * @package     Joomla.Plugin
 * @subpackage  System.jicon
 * @copyright   (C) 2026 Angie Radtke
 * @license     GNU/GPL
 */

namespace Joomla\Plugin\System\JIcon\Service;

use Joomla\Registry\Registry;

defined('_JEXEC') or die;

final class JIconService
{
	/**
	 * Generate custom light and dark colors CSS from params.
	 */
	public function getCustomColorCSS(Registry $params): string
	{
		$colors = [
			'primary' => [
				$params->get('color_primary', '#0066cc'),
				$params->get('color_primary_dark', '#4da6ff'),
			],
			'success' => [
				$params->get('color_success', '#28a745'),
				$params->get('color_success_dark', '#51cf66'),
			],
			'danger' => [
				$params->get('color_danger', '#dc3545'),
				$params->get('color_danger_dark', '#ff6b6b'),
			],
			'warning' => [
				$params->get('color_warning', '#ffc107'),
				$params->get('color_warning_dark', '#ffa500'),
			],
			'info' => [
				$params->get('color_info', '#17a2b8'),
				$params->get('color_info_dark', '#74c0fc'),
			],
		];
		
		$css = ":root {\n";
		$css .= "  color-scheme: light dark;\n";
		
		foreach ($colors as $name => [$light, $dark]) {
			$css .= "  --j-icon-color-{$name}: light-dark({$light}, {$dark});\n";
		}
		
		$css .= "}\n";
		
		return $css;
	}
	
	/**
	 * Remove cached sprite.
	 */
	public function clearCache(): bool
	{
		$cacheDir = JPATH_SITE . '/plugins/system/jicon/media/cache';
		
		if (!is_dir($cacheDir)) {
			return true;
		}
		
		foreach (glob($cacheDir . '/sprite.*.svg') ?: [] as $file) {
			@unlink($file);
		}
		
		foreach (glob($cacheDir . '/*.tmp') ?: [] as $file) {
			@unlink($file);
		}
		
		return true;
	}
}
