<?php
/**
 * @package         Joomla.Plugin
 * @subpackage      System.jicon
 * @copyright   (C) 2026 Angie Radtke
 * @license         GNU/GPL
 */

namespace Joomla\Plugin\System\JIcon\Helper;

use Joomla\CMS\Uri\Uri;
use Joomla\Registry\Registry;
use enshrined\svgSanitize\Sanitizer;

defined('_JEXEC') or die;

final class JIconHelper
{
	/**
	 * svgsprite
	 * @var array
	 * @since 1.0
	 */
	private array $icons = [];

	/**
	 * @var string|null
	 * @since version
	 */
	private ?string $spriteCache = null;

	/**
	 * @var string
	 * @since version
	 */
	private string $iconPath;

	/**
	 * If set, render() uses this external sprite URL
	 * * instead of an internal fragment. Used in cache mode.
	 * @since 1.0
	 */
	private ?string $externalSpriteUrl = null;

	/**
	 * Constructor
	 */
	public function __construct()
	{
		$this->iconPath = JPATH_SITE . '/plugins/system/jicon/media/icons';
	}
	
	/**
	 * Register single icon
	 *
	 * @param   string  $name  Font-name
	 * @param   string  $style Font-style
	 *
	 * @return self
	 * @since 1.0
	 */
	public function register(string $name, string $style = 'solid'): self
	{
		$key = "$style/$name";

		if (!in_array($key, $this->icons))
		{
			$this->icons[] = $key;
		}

		return $this;
	}

	/**
	 * Register multiple icons
	 *
	 * @param   array   $names  Font-Name
	 * @param   string  $style  Font-Style
	 *
	 * @return JIconHelper
	 * @since 1.0
	 */
	public function registerMultiple(array $names, string $style = 'solid'): self
	{
		foreach ($names as $name)
		{
			$this->register($name, $style);
		}

		return $this;
	}

	/**
	 * Get all registered icons
	 */
	public function getRegistered(): array
	{
		return $this->icons;
	}
	
	/**
	 * Generate inline sprite with only registered icons
	 */
	public function getSprite(): string
	{
		if ($this->spriteCache !== null)
		{
			return $this->spriteCache;
		}

		$this->spriteCache = '<svg style="display:none" class="j-icon" xmlns="http://www.w3.org/2000/svg">' . "\n"
			. $this->renderSymbols()
			. '</svg>';

		return $this->spriteCache;
	}

	/**
	 * Build the <symbol> list from all registered icons.
	 */
	private function renderSymbols(): string
	{
		$symbols = '';

		foreach ($this->icons as $icon)
		{
			if (strpos($icon, '/') === false)
			{
				continue;
			}

			[$style, $name] = explode('/', $icon);
			$file     = $this->iconPath . '/' . $style . '/' . $name . '.svg';
			$realPath = realpath($file);
			if (!$realPath || strpos($realPath, realpath($this->iconPath)) !== 0)
			{
				throw new \Exception("Invalid path");
			}
			if (is_file($file))
			{
				$content  = file_get_contents($file);
				$symbolId = $name;
				$symbols  .= $this->svgToSymbol($content, $symbolId) . "\n";
			}
		}

		return $symbols;
	}
	
	/**
	 * Convert SVG to a `symbol` element. The `<path>` is intentionally
	 * * rendered as self-closing so that the external SVG file
	 * * is also valid XML.
 */
	private function svgToSymbol(string $svgContent, string $id): string
	{    // SVG bereinigen
		$sanitizer  = new Sanitizer();
		$svgContent = $sanitizer->sanitize($svgContent);
		if ($svgContent === false)
		{
			return '<!-- Invalid SVG: ' . htmlspecialchars($id, ENT_QUOTES, 'UTF-8') . ' -->';
		}
		// viewBox holen
		if (preg_match('/viewBox="([^"]*)"/i', $svgContent, $matches))
		{
			$viewBox = $matches[1];
		}
		else
		{
			$viewBox = '0 0 512 512';
		}


		// Retrieve SVG content between <svg> and </svg>
		if (preg_match('/<svg[^>]*>(.*?)<\/svg>/is', $svgContent, $matches))
		{
			$content = $matches[1];
		}
		else
		{
			return '<!-- Invalid SVG: ' . htmlspecialchars($id, ENT_QUOTES, 'UTF-8') . ' -->';
		}
		
		
		return '<symbol id="icon-'
			. htmlspecialchars($id, ENT_QUOTES, 'UTF-8')
			. '" viewBox="'
			. htmlspecialchars($viewBox, ENT_QUOTES, 'UTF-8')
			. '">'
			. $content
			. '</symbol>';
	}
	
	/**
	 * Set the external sprite URL. If set, render() refers to
	 * this file instead of an internal fragment.
	 */
	public function setExternalSpriteUrl(?string $url): void
	{
		$this->externalSpriteUrl = $url;
	}
	
	/**
	 * Render icon. Without an external URL, use the internal sprite (#icon-...),
	 * * with an external URL, use the cached file (file.svg#icon-...).
    */
	public function render(
		string $name,
		string $style = '',
		string $classes = ''
	): string {
		
		$this->register($name, $style);
		$symbolId  = str_replace(['/', '.svg'], ['-', ''], "{$name}");
		$classList = 'j-icon ' . trim($classes);
		$href      = ($this->externalSpriteUrl ?? '') . '#icon-' . $symbolId;
		
		return '<svg class="' . htmlspecialchars($classList) . '" aria-hidden="true">'
			. '<use href="' . htmlspecialchars($href) . '"></use>'
			. '</svg>';
	}
	
	/**
	 * Public URL to the fingerprinted sprite file. Retrieves the file
	 * * and returns null if no icon is registered.
    */
	public function getSpriteUrl(): ?string
	{
		if ($this->icons === [])
		{
			return null;
		}
		
		[, $url] = $this->ensureSpriteCached();
		
		return $url;
	}
	
	/**
	 * Fingerprint based on the registered icons and the mtimes of the source files.
	 * Changes as soon as an icon is added, removed or an SVG file is updated.
 */
	private function getSpriteHash(): string
	{
		$parts = [];
		
		foreach ($this->icons as $icon)
		{
			if (strpos($icon, '/') === false)
			{
				continue;
			}
			
			[$style, $name] = explode('/', $icon);
			$file    = $this->iconPath . '/' . $style . '/' . $name . '.svg';
			$mtime   = is_file($file) ? filemtime($file) : 0;
			$parts[] = $icon . '|' . $mtime;
		}
		
		if ($parts === [])
		{
			return 'empty';
		}
		
		return substr(sha1(implode("\n", $parts)), 0, 12);
	}
	
	/**
	 * Absolute cache directory for the sprite files.
	 */
	private function getCacheDir(): string
	{
		return JPATH_SITE . '/plugins/system/jicon/media/cache';
	}
	
	/**
	 * absolute path and public URL for a hash.
	 *
	 * @return array{0:string,1:string}
	 */
	private function getSpritePaths(string $hash): array
	{
		$fileName = 'sprite.' . $hash . '.svg';
		$abs      = $this->getCacheDir() . '/' . $fileName;
		$url      = rtrim(Uri::root(true), '/') . '/plugins/system/jicon/media/cache/' . $fileName;
		
		return [$abs, $url];
	}
	
	/**
	 * Ensure the sprite file exists. Build it only if it does not yet exist for the current hash,
	 * and write to it atomically via a temporary file.
	 *
	 * @return array{0:string,1:string,2:string} [absoluterPfad, url, hash]
	 */
	public function ensureSpriteCached(): array
	{
		$hash = $this->getSpriteHash();
		[$abs, $url] = $this->getSpritePaths($hash);
		$dir = $this->getCacheDir();
		
		if (!is_dir($dir))
		{
			@mkdir($dir, 0755, true);
		}
		
		if (!is_file($abs))
		{
			$svg = $this->buildSpriteSvg();
			$tmp = $abs . '.' . uniqid('', true) . '.tmp';
			
			if (@file_put_contents($tmp, $svg) !== false)
			{
				@rename($tmp, $abs);
			}
			
			$this->cleanupOldSprites();
		}
		
		return [$abs, $url, $hash];
	}
	
	/**
	 * External Sprite SVG File
	 */
	private function buildSpriteSvg(): string
	{
		return '<svg xmlns="http://www.w3.org/2000/svg" style="display:none">' . "\n"
			. $this->renderSymbols()
			. '</svg>';
	}
	
	/**
	 * Clean up old, outdated sprite files.
	 */
	private function cleanupOldSprites(int $keep = 5): void
	{
		$dir = $this->getCacheDir();
		
		if (!is_dir($dir))
		{
			return;
		}
		
		foreach (glob($dir . '/*.tmp') ?: [] as $tmp)
		{
			@unlink($tmp);
		}
		
		$files = glob($dir . '/sprite.*.svg') ?: [];
		
		if (count($files) <= $keep)
		{
			return;
		}
		
		usort($files, static fn($a, $b) => filemtime($b) <=> filemtime($a));
		
		foreach (array_slice($files, $keep) as $old)
		{
			@unlink($old);
		}
	}
}
