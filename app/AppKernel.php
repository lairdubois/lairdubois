<?php

use Symfony\Component\HttpKernel\Kernel;
use Symfony\Component\Config\Loader\LoaderInterface;

class AppKernel extends Kernel
{
    public function registerBundles()
    {
        $bundles = array(
            new Symfony\Bundle\FrameworkBundle\FrameworkBundle(),
            new Symfony\Bundle\SecurityBundle\SecurityBundle(),
            new Symfony\Bundle\TwigBundle\TwigBundle(),
            new Symfony\Bundle\MonologBundle\MonologBundle(),
            new Symfony\Bundle\SwiftmailerBundle\SwiftmailerBundle(),
            new Symfony\Bundle\AsseticBundle\AsseticBundle(),
            new Doctrine\Bundle\DoctrineBundle\DoctrineBundle(),
			new Doctrine\Bundle\MigrationsBundle\DoctrineMigrationsBundle(),
			new Sensio\Bundle\FrameworkExtraBundle\SensioFrameworkExtraBundle(),

            new FOS\UserBundle\FOSUserBundle(),
			new FOS\ElasticaBundle\FOSElasticaBundle(),
			new Stof\DoctrineExtensionsBundle\StofDoctrineExtensionsBundle(),
			new Liip\ImagineBundle\LiipImagineBundle(),

			new Gos\Bundle\WebSocketBundle\GosWebSocketBundle(),
			new Gos\Bundle\PubSubRouterBundle\GosPubSubRouterBundle(),

			new OldSound\RabbitMqBundle\OldSoundRabbitMqBundle(),

			new BenTools\WebPushBundle\WebPushBundle(),

            new Ladb\CoreBundle\LadbCoreBundle(),
        );

        if (in_array($this->getEnvironment(), array('dev', 'test'))) {
			$bundles[] = new Symfony\Bundle\DebugBundle\DebugBundle();
			$bundles[] = new Symfony\Bundle\WebProfilerBundle\WebProfilerBundle();
			$bundles[] = new Sensio\Bundle\DistributionBundle\SensioDistributionBundle();
			$bundles[] = new Sensio\Bundle\GeneratorBundle\SensioGeneratorBundle();
        }

        return $bundles;
    }

	public function getRootDir()
	{
		return __DIR__;
	}

	public function getCacheDir()
	{
		return dirname(__DIR__).'/var/cache/'.$this->getEnvironment();
	}

	public function getLogDir()
	{
		return dirname(__DIR__).'/var/logs';
	}

	public function registerContainerConfiguration(LoaderInterface $loader)
	{
		$loader->load($this->getRootDir().'/config/config_'.$this->getEnvironment().'.yml');
	}
}
