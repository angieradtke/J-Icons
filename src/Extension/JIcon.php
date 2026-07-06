<?php

/**
 * @package         Joomla.Plugin
 * @subpackage      System.JIcon
 * @copyright       (C) 2026 Angie Radtke
 * @license         GNU/GPL
 */
namespace Joomla\Plugin\System\JIcon\Extension;

use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\Event\DispatcherAwareInterface;
use Joomla\Event\DispatcherAwareTrait;
use Joomla\Event\SubscriberInterface;
use Joomla\Plugin\System\JIcon\Helper\JIconHelper;
use Joomla\Plugin\System\JIcon\Service\JIconService;

defined('_JEXEC') or die;
/**
 * JIcon System Plugin Extension.
 *
 * Manages SVG icon sprites, registration, and dynamic rendering.
 * Provides FontAwesome backward compatibility and Web Component integration.
 *
 * @since 1.0.0
 */
final class JIcon extends CMSPlugin implements SubscriberInterface, DispatcherAwareInterface
{
	use DispatcherAwareTrait;
	
	/**
	 * Helper instance for icon management.
	 *
	 * @var JIconHelper|null
	 *
	 * @since 1.0.0
	 */
	private ?JIconHelper $helper = null;
	
	/**
	 * Service instance for icon operations.
	 *
	 * @var JIconService|null
	 *
	 * @since 1.0.0
	 */
	private ?JIconService $service = null;
	
	/**
	 * Subscribes to plugin events.
	 *
	 * @return array<string, string|array> Event subscriptions.
	 *
	 * @since 1.0.0
	 */
	public static function getSubscribedEvents(): array
	{
		return [
			'onBeforeRender'       => 'onBeforeRender',
			'onAfterRender'        => 'onAfterRender',
			'onExtensionAfterSave' => 'onExtensionAfterSave',
		];
	}
	
	/**
	 * Get the helper instance.
	 * @return JIconHelper
	 * @since 1.0.0
	 */
	private function getHelper(): JIconHelper
	{
		if ($this->helper === null) {
			$this->helper = new JIconHelper($this->params);
		}
		
		return $this->helper;
	}
	
	/**
	 * Get the service instance.
	 *
	 * @return JIconService
	 *
	 * @since 1.0.0
	 */
	private function getService(): JIconService
	{
		if ($this->service === null) {
			$this->service = new JIconService($this->getHelper());
		}

		return $this->service;
	}

	/**
	 * Set the helper instance (for testing).
	 *
	 * @param JIconHelper $helper
	 * @return void
	 * @since 1.0.0
	 *
	 */
	public function setHelper(JIconHelper $helper): void
	{
		$this->helper = $helper;
	}

	/**
	 * Set the service instance (for testing).
	 *
	 * @param JIconService $service
	 * @return void
	 * @since 1.0.0
	 */
	public function setService(JIconService $service): void
	{
		$this->service = $service;
	}

	/**
	 * Before render event handler.
	 *
	 * Registers stylesheet and script assets via Web Asset Manager.
	 * Configures sprite references and handles FontAwesome backward compatibility.
	 *
	 * @return void
	 *
	 * @since 1.0.0
	 */
	public function onBeforeRender(): void
	{
		if (!$this->isEnabled()) {
			return;
		}

		$app       = $this->getApplication();
		$document  = $app->getDocument();
		$wa        = $document->getWebAssetManager();
		$css       = $this->getService()->getCustomColorCSS($this->params);

		// Register and use assets via Web Asset Manager
		$wa->registerAndUseStyle(
			'plg_system_jicon.styles',
			'plugins/system/jicon/media/css/j-icon.min.css'
		);
		$wa->registerAndUseScript(
			'plg_system_jicon.jicon',
			'plugins/system/jicon/media/js/j-icon.js',
			[],
			['type' => 'module']
		);

		if ($this->params->get('enable_bc_fontawesome', 0)) {
			$wa->registerAndUseScript(
				'plg_system_jicon.fontawesome',
				'plugins/system/jicon/media/js/bc-fontawesome.js',
				[],
				['type' => 'module']
			);
		}

		// Add custom color CSS as inline style
		if ($css) {
			$document->addStyleDeclaration($css);
		}

		// Register selected icons and ensure fingerprinted sprite file exists
		$this->registerSelectedIcons();
		$spriteUrl = $this->getHelper()->getSpriteUrl();

		// Provide sprite URL for external usage (e.g., Web Components)
		if ($spriteUrl !== null) {
			$configJson = json_encode(
				['spritePath' => $spriteUrl],
				JSON_UNESCAPED_SLASHES
			);

			$document->addScriptDeclaration(
				<<<JS
window.JIconConfig = {$configJson};

document.addEventListener('DOMContentLoaded', function () {
	function updateSpriteReferences(root) {
		root.querySelectorAll('use').forEach(function (use) {
			var href = use.getAttribute('href');

			if (href && href.charAt(0) === '#') {
				var newHref = window.JIconConfig.spritePath + href;
				use.setAttribute('href', newHref);
			}
		});
	}

	// Update existing SVGs
	updateSpriteReferences(document);

	// Also update dynamically added SVGs (AJAX, Web Components, etc.)
	new MutationObserver(function (mutations) {
		mutations.forEach(function (mutation) {
			mutation.addedNodes.forEach(function (node) {
				if (node.nodeType !== 1) {
					return;
				}

				if (node.tagName && node.tagName.toLowerCase() === 'use') {
					updateSpriteReferences(node.parentNode);
				} else {
					updateSpriteReferences(node);
				}
			});
		});
	}).observe(document.documentElement, {
		childList: true,
		subtree: true
	});
});
JS
			);
		}

		// Cache mode: render() should reference file, not inline
		if ($this->params->get('inject_position', 'body_start') === 'cached') {
			$this->getHelper()->setExternalSpriteUrl($spriteUrl);
		}
	}

	/**
	 * After render event handler.
	 *
	 * Injects sprite inline into document body if not in cache or manual mode.
	 * @return void
	 * @since 1.0.0
	 */
	public function onAfterRender(): void
	{
		if (!$this->isEnabled()) {
			return;
		}

		$position = $this->params->get('inject_position', 'body_start');
		
		// Cache mode and manual mode: do not inject inline sprite into body
		if ($position === 'manual' || $position === 'cached') {
			return;
		}

		$this->registerSelectedIcons();
		$app    = $this->getApplication();
		$body   = $app->getBody();
		$sprite = $this->getHelper()->getSprite();

		if (!is_string($body) || $sprite === '') {
			return;
		}
       /**  * better solution than regex? */
		if ($position === 'body_start') {
			$body = preg_replace('/<body([^>]*)>/i', '<body$1>' . $sprite, $body, 1);
		} else {
			$body = preg_replace('/<\/body>/i', $sprite . '</body>', $body, 1);
		}
		
		$app->setBody($body);
	}

	/**
	 * Register icons selected in plugin parameters.
	 *
	 * Idempotent: safe to call multiple times per request.
	 * @return void
	 * @since 1.0.0
	 */
	private function registerSelectedIcons(): void
	{
		$selectedIcons = $this->normalizeIconList($this->params->get('selected_icons', []));
		$helper        = $this->getHelper();

		foreach ($selectedIcons as $icon) {
			if (!str_contains($icon, '/')) {
				continue;
			}

			[$style, $name] = explode('/', $icon, 2);
			
			if ($style !== '' && $name !== '') {
				$helper->register($name, $style);
			}
		}
	}

	/**
	 * Normalize icon list to array of icon strings.
	 * Handles strings (JSON, comma-separated), arrays, and nested structures.
	 *
	 * @param mixed $value Input icon list
	 *
	 * @return array<string> Normalized icon list
	 * @since 1.0.0
	 */
	private function normalizeIconList($value): array
	{
		$result = [];

		// If string: attempt JSON parsing
		if (is_string($value)) {
			$value = trim($value);
			if ($value === '') {
				return [];
			}
			
			$decoded = json_decode($value, true);
			if (is_array($decoded)) {
				$value = $decoded;
			} else {
				$value = explode(',', $value);
			}
		}

		if (!is_array($value)) {
			return [];
		}

		$this->flattenIconArray($value, $result);
		
		return array_filter($result, static fn($v) => is_string($v) && $v !== '');
	}
	
	/**
	 * Flatten nested icon array structure.
	 *
	 * @param array $array Input array
	 * @param array $result Reference to output array
	 *
	 * @return void
	 *
	 * @since 1.0.0
	 */
	private function flattenIconArray($array, &$result): void
	{
		foreach ($array as $item) {
			if (is_string($item)) {
				$item = trim($item);
				if (!$item) {
					continue;
				}
				
				// Attempt to decode if JSON-like
				if ($item[0] === '[' || $item[0] === '{') {
					$decoded = json_decode($item, true);
					if (is_array($decoded)) {
						$this->flattenIconArray($decoded, $result);
						continue;
					}
				}
				
				// Add everything else directly
				$result[] = $item;
			} elseif (is_array($item)) {
				$this->flattenIconArray($item, $result);
			}
		}
	}
	
	/**
	 * Render an icon.
	 *
	 * @param string $name    Icon name
	 * @param string $style   Icon style (default: 'solid')
	 * @param string $classes Additional CSS classes
	 *
	 * @return string SVG markup
	 *
	 * @since 1.0.0
	 */
	public function renderIcon(string $name, string $style = 'solid', string $classes = ''): string
	{
		return $this->getHelper()->render($name, $style, $classes);
	}
	
	/**
	 * Check if plugin is enabled.
	 *
	 * @return bool
	 *
	 * @since 1.0.0
	 */
	private function isEnabled(): bool
	{
		return (bool) $this->params->get('enable_plugin', 1);
	}
	
	/**
	 * Extension save event handler.
	 *
	 * Regenerates sprite file when plugin configuration is saved.
	 *
	 * @param \Joomla\Event\Event $event Event object
	 *
	 * @return bool
	 *
	 * @since 1.0.0
	 */
	public function onExtensionAfterSave($event): bool
	{
		$context = $event->getArgument(0, '');
		$table   = $event->getArgument(1);
		
		if ($context !== 'com_plugins.plugin' || !$table || $table->element !== 'jicon') {
			return true;
		}
		
		try {
			$this->getService()->clearCache();
			$this->saveSprite();
		} catch (\Exception $e) {
			// Do not block plugin save on sprite error
		}
		
		return true;
	}
	
	/**
	 * Generate fingerprinted sprite file.
	 *
	 * @return bool True if sprite was generated successfully
	 *
	 * @since 1.0.0
	 */
	public function saveSprite(): bool
	{
		$this->registerSelectedIcons();
		return $this->getHelper()->getSpriteUrl() !== null;
	}
}
