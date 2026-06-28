<?php
/**
 * @package     Joomla.Plugin
 * @subpackage  System.jicon
 * @copyright   (C) 2026 Angie Radtke
 * @license     GNU/GPL
 */

namespace Joomla\Plugin\System\JIcon\Extension;

use Joomla\CMS\Factory;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\Event\DispatcherAwareInterface;
use Joomla\Event\DispatcherAwareTrait;
use Joomla\Event\SubscriberInterface;
use Joomla\Plugin\System\JIcon\Helper\JIconHelper;
use Joomla\Plugin\System\JIcon\Service\JIconService;

defined('_JEXEC') or die;

final class JIcon extends CMSPlugin implements SubscriberInterface, DispatcherAwareInterface
{
    use DispatcherAwareTrait;

    private ?JIconHelper $helper = null;
    private ?JIconService $service = null;

    public static function getSubscribedEvents(): array
    {
        return [
            'onBeforeRender'       => 'onBeforeRender',
            'onAfterRender'        => 'onAfterRender',
            'onExtensionAfterSave' => 'onExtensionAfterSave',
        ];
    }

    private function getHelper(): JIconHelper
    {
        if ($this->helper === null) {
            $this->helper = new JIconHelper($this->params);
        }
        return $this->helper;
    }

    private function getService(): JIconService
    {
        if ($this->service === null) {
            $this->service = new JIconService($this->getHelper());
        }
        return $this->service;
    }

    public function setHelper(JIconHelper $helper): void
    {
        $this->helper = $helper;
    }

    public function setService(JIconService $service): void
    {
        $this->service = $service;
    }
	
	public function onBeforeRender(): void
	{
		if (!$this->isEnabled()) {
			return;
		}
		
		$app       = $this->getApplication();
		$document  = $app->getDocument();
		$mediaPath = 'plugins/system/jicon/media';
		$css = $this->getService()->getCustomColorCSS($this->params);
		$document->addStyleDeclaration($css);
		$document->addStyleSheet($mediaPath . '/css/j-icon.min.css');
		$document->addScript($mediaPath . '/js/j-icon.js', [], ['type' => 'module']);
		
		if ($this->params->get('enable_bc_fontawesome', 0)) {
			$document->addScript($mediaPath . '/js/bc-fontawesome.js', [], ['type' => 'module']);
		}
		
	
		
		//  Icons registrieren und gefingerprintetes Sprite-File sicherstellen
		$this->registerSelectedIcons();
		$spriteUrl = $this->getHelper()->getSpriteUrl();
		
		// URL fuer externe Nutzung (z. B. Web Components) bereitstellen
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
            var href = use.getAttribute('href') ;

            if (href && href.charAt(0) === '#') {
                var newHref = window.JIconConfig.spritePath + href;
                use.setAttribute('href', newHref);

            }
        });
    }

    // Bereits vorhandene SVGs
    updateSpriteReferences(document);

    // Auch spaeter hinzugefuegte SVGs (AJAX, Web Components usw.)
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
		
		// Cache-Modus: render() soll ueber die Datei verweisen, nicht inline
		if ($this->params->get('inject_position', 'body_start') === 'cached') {
			$this->getHelper()->setExternalSpriteUrl($spriteUrl);
		}
	}

    public function onAfterRender(): void
    {
        if (!$this->isEnabled()) {
            return;
        }

        $position = $this->params->get('inject_position', 'body_start');

        // Cache-Modus und Manuell: kein Inline-Sprite im Body
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

        if ($position === 'body_start') {
            $body = preg_replace('/<body([^>]*)>/i', '<body$1>' . $sprite, $body, 1);
        } else {
            $body = preg_replace('/<\/body>/i', $sprite . '</body>', $body, 1);
        }

        $app->setBody($body);
    }

    /**
     * Die in den Plugin-Parametern ausgewaehlten Icons im Helper registrieren.
     * Idempotent, kann also gefahrlos mehrfach pro Request aufgerufen werden.
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

    private function normalizeIconList($value): array
    {
        $result = [];

        // Falls String: JSON versuchen zu parsen
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

        return array_filter($result, static fn ($v) => is_string($v) && $v !== '');
    }

    private function flattenIconArray($array, &$result): void
    {
        foreach ($array as $item) {
            if (is_string($item)) {
                $item = trim($item);
                if ($item && ($item[0] === '[' || $item[0] === '{')) {
                    $decoded = json_decode($item, true);
                    if (is_array($decoded)) {
                        $this->flattenIconArray($decoded, $result);
                    } elseif ($item !== '') {
                        $result[] = $item;
                    }
                } elseif ($item !== '') {
                    $result[] = $item;
                }
            } elseif (is_array($item)) {
                $this->flattenIconArray($item, $result);
            }
        }
    }

    public function renderIcon(string $name, string $style = 'solid', string $classes = ''): string
    {
        return $this->getHelper()->render($name, $style, $classes);
    }

    private function isEnabled(): bool
    {
        return (bool) $this->params->get('enable_plugin', 1);
    }

    /**
     * Event: Beim Speichern des Plugins das Sprite-File neu erzeugen.
     */
    public function onExtensionAfterSave($event)
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
            // Fehler beim Speichern nicht blockieren
        }

        return true;
    }

    /**
     * Sprite als gefingerprintete Datei erzeugen (Version C).
     */
    public function saveSprite(): bool
    {
        $this->registerSelectedIcons();
        // Stellt die Datei sicher und liefert die URL, oder null wenn leer
        return $this->getHelper()->getSpriteUrl() !== null;
    }
}
