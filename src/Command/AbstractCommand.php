<?php

namespace App\Command;

use App\Manager\Offer\OfferManager;
use App\Utils\BlockBodiedUtils;
use App\Utils\CommentableUtils;
use App\Utils\EmbeddableUtils;
use App\Utils\FieldPreprocessorUtils;
use App\Utils\KnowledgeUtils;
use App\Utils\MailerUtils;
use App\Utils\PropertyUtils;
use App\Utils\TextureUtils;
use App\Utils\TypableUtils;
use App\Utils\UserUtils;
use App\Utils\VotableUtils;
use App\Utils\WebScreenshotUtils;
use Psr\Container\ContainerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Contracts\Service\ServiceSubscriberInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Environment;

abstract class AbstractCommand extends Command implements ServiceSubscriberInterface {

    protected ContainerInterface $container;

    public static function getSubscribedServices() {
        return array(
            'parameter_bag' => '?'.ParameterBagInterface::class,
            'translator' => TranslatorInterface::class,
            'twig' => '?'.Environment::class,
            '?'.BlockBodiedUtils::class,
            '?'.CommentableUtils::class,
            '?'.EmbeddableUtils::class,
            '?'.FieldPreprocessorUtils::class,
            '?'.KnowledgeUtils::class,
            '?'.MailerUtils::class,
            '?'.OfferManager::class,
            '?'.PropertyUtils::class,
            '?'.TextureUtils::class,
            '?'.TypableUtils::class,
            '?'.UserUtils::class,
            '?'.VotableUtils::class,
            '?'.WebScreenshotUtils::class,
        );
    }

    public function setContainer(ContainerInterface $container) {
        $this->container = $container;
    }

    public function getContainer(): ContainerInterface {
        return $this->container;
    }

    public function getParameter($name) {
        return $this->container->get('parameter_bag')->get($name);
    }
}