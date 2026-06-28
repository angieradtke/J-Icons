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
use Joomla\Event\DispatcherInterface;
use Joomla\Plugin\System\JIcon\Extension\JIcon;
use Joomla\Plugin\System\JIcon\Helper\JIconHelper;
use Joomla\Plugin\System\JIcon\Service\JIconService;

return new class () implements ServiceProviderInterface {
    /**
     * Register Services in DI Container
     */
    public function register(Container $container): void
    {
        // Register Helper
        $container->set(
            JIconHelper::class,
            function (Container $container) {
                return new JIconHelper(
                    $container->get('config')
                );
            }
        );

        // Register Service
        $container->set(
            JIconService::class,
            function (Container $container) {
                return new JIconService(
                    $container->get(JIconHelper::class)
                );
            }
        );

        // Register Plugin
        $container->set(
            PluginInterface::class,
            function (Container $container) {
                $dispatcher = $container->get(DispatcherInterface::class);
                $plugin = new JIcon(
                    $dispatcher,
                    (array) PluginHelper::getPlugin('system', 'jicon')
                );

                $plugin->setApplication(Factory::getApplication());
                $plugin->setHelper($container->get(JIconHelper::class));
                $plugin->setService($container->get(JIconService::class));

                return $plugin;
            }
        );
    }
};

// Global Helper Function
if (!function_exists('jicon')) {
    /**
     * Global jicon() Helper Function
     * 
     * @param   string  $name       Icon name
     * @param   string  $style      Icon style (solid, regular, brands)
     * @param   string  $classes    Additional CSS classes
     * 
     * @return  string  SVG HTML
     */
    function jicon(string $name, string $style = 'solid', string $classes = ''): string
    {
        try {
            $container = Factory::getContainer();
            $plugin = $container->get(PluginInterface::class);
            
            if ($plugin instanceof JIcon) {
                return $plugin->renderIcon($name, $style, $classes);
            }
        } catch (\Exception $e) {
            return '';
        }

        return '';
    }
}
