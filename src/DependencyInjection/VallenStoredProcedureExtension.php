<?php

namespace Vallen\StoredProcedureFactory\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Vallen\StoredProcedureFactory\StoredProcedureFactory;

class VallenStoredProcedureExtension extends Extension
{
    public function load(array $configs, ContainerBuilder $container): void
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $loader = new YamlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('services.yaml');

        // Set parameters for the StoredProcedureFactory service
        $container->setParameter('vallen_stored_procedure.hostname', $config['hostname']);
        $container->setParameter('vallen_stored_procedure.username', $config['username']);
        $container->setParameter('vallen_stored_procedure.password', $config['password']);
    }

    public function getAlias(): string
    {
        return 'vallen_stored_procedure';
    }
}