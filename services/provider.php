<?php
/**
 * @package     Joomla.Plugin
 * @subpackage  System.jicon
 * @copyright   (C) 2026 Angie Radtke
 * @license     GNU/GPL
 */

defined('_JEXEC') or die;

use Joomla\CMS\Extension\PluginInterface;
use Joomla\CMS\Factory;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\DI\Container;
use Joomla\DI\ServiceProviderInterface;
use Joomla\Plugin\System\JIcon\Extension\JIcon;

return new class () implements ServiceProviderInterface {
	/**
	* Register Services in DI Container
	* @ since 1.0
	* @param   Container  $container
	*/
	public function register(Container $container): void
	{
		$container->set(
			PluginInterface::class,
			$container->lazy(JIcon::class, function (Container $container) {
				$plugin = new JIcon(
					(array) PluginHelper::getPlugin('system', 'jicon')
				);

				$plugin->setApplication(Factory::getApplication());

				return $plugin;
})
		);
	}
};

