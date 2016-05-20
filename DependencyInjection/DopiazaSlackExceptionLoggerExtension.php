<?php

namespace Dopiaza\Slack\ExceptionLoggerBundle\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\DependencyInjection\Loader;

/**
 * This is the class that loads and manages your bundle configuration
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html}
 */
class DopiazaSlackExceptionLoggerExtension extends Extension
{
    /**
     * {@inheritdoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $loader = new Loader\YamlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('services.yml');

        $exceptionHandlerDefinition = $container->getDefinition('dopiaza_slack_exception_logger.listener');
        $exceptionHandlerDefinition->addMethodCall('setEnvironmentConfigurations', array($config['environments']));

        $notifierDefinition = $container->getDefinition('dopiaza_slack_exception_logger.notifier');
        $notifierDefinition->addMethodCall('setEnvironmentConfigurations', array($config['environments']));
        $notifierDefinition->addMethodCall('setWebhook', array($config['webhook']));
        $notifierDefinition->addMethodCall('setName', array($config['name']));
        if (array_key_exists('botname', $config))
        {
            $notifierDefinition->addMethodCall('setBotname', array($config['botname']));
        }
    }
}
