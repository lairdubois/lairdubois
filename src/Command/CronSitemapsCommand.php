<?php

namespace App\Command;

use Liip\ImagineBundle\Imagine\Cache\CacheManager;
use Symfony\Component\Asset\Packages;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use App\Model\BlockBodiedInterface;
use App\Model\MultiPicturedInterface;
use App\Model\SitemapableInterface;
use App\Model\LicensedInterface;
use App\Model\PicturedInterface;
use App\Utils\PicturedUtils;
use App\Utils\VideoHostingUtils;
use Symfony\Component\Routing\RouterInterface;
use Twig\Environment;

class CronSitemapsCommand extends AbstractContainerAwareCommand {

	private $exportedVideosIdentifiers;

    public static function getSubscribedServices() {
        return array_merge(parent::getSubscribedServices(), array(
            'assets.packages' => '?'.Packages::class,
            'router' => '?'.RouterInterface::class,
            'twig' => '?'.Environment::class,
            '?'.PicturedUtils::class,
            '?'.VideoHostingUtils::class,
        ));
    }

    /////

    protected function configure() {
		$this
			->setName('ladb:cron:sitemaps')
			->addOption('force', null, InputOption::VALUE_NONE, 'Force updating')
			->setDescription('Generate sitemaps')
			->setHelp(<<<EOT
The <info>ladb:cron:sitemaps</info> generate sitemaps
EOT
			);
	}

	/////

	protected function execute(InputInterface $input, OutputInterface $output) {

		$forced = $input->getOption('force');
		$verbose = $input->getOption('verbose');

		$this->exportedVideosIdentifiers = array();

		$defs = array(
			array(
				'className' => \App\Entity\Wonder\Creation::class,
				'name'      => 'creation',
				'section'   => 'wonder-creations',
				'slugged'   => true,
			),
			array(
				'className' => \App\Entity\Wonder\Plan::class,
				'name'      => 'plan',
				'section'   => 'wonder-plans',
				'slugged'   => true,
			),
			array(
				'className' => \App\Entity\Wonder\Workshop::class,
				'name'      => 'workshop',
				'section'   => 'wonder-workshops',
				'slugged'   => true,
			),
			array(
				'className' => \App\Entity\Howto\Howto::class,
				'name'      => 'howto',
				'section'   => 'howto-howtos',
				'slugged'   => true,
			),
			array(
				'className' => \App\Entity\Workflow\Workflow::class,
				'name'      => 'workflow',
				'section'   => 'workflow-workflows',
				'slugged'   => true,
			),
			array(
				'className' => \App\Entity\Find\Find::class,
				'name'      => 'find',
				'section'   => 'find-finds',
				'slugged'   => true,
			),
			array(
				'className' => \App\Entity\Event\Event::class,
				'name'      => 'event',
				'section'   => 'event-events',
				'slugged'   => true,
			),
			array(
				'className' => \App\Entity\Qa\Question::class,
				'name'      => 'qa_question',
				'section'   => 'qa-questions',
				'slugged'   => true,
			),
			array(
				'className' => \App\Entity\Blog\Post::class,
				'name'      => 'blog_post',
				'section'   => 'blog-posts',
				'slugged'   => true,
			),
			array(
				'className' => \App\Entity\Faq\Question::class,
				'name'      => 'faq_question',
				'section'   => 'faq-questions',
				'slugged'   => true,
			),
			array(
				'className' => \App\Entity\Knowledge\Wood::class,
				'name'      => 'wood',
				'section'   => 'knowledge-woods',
				'slugged'   => true,
			),
			array(
				'className' => \App\Entity\Knowledge\Provider::class,
				'name'      => 'provider',
				'section'   => 'knowledge-providers',
				'slugged'   => true,
			),
			array(
				'className' => \App\Entity\Knowledge\School::class,
				'name'      => 'school',
				'section'   => 'knowledge-schools',
				'slugged'   => true,
			),
			array(
				'className' => \App\Entity\Knowledge\Book::class,
				'name'      => 'book',
				'section'   => 'knowledge-books',
				'slugged'   => true,
			),
			array(
				'className' => \App\Entity\Knowledge\Software::class,
				'name'      => 'software',
				'section'   => 'knowledge-softwares',
				'slugged'   => true,
			),
			array(
				'className' => \App\Entity\Core\User::class,
				'name'      => 'user',
				'section'   => 'core-users',
				'slugged'   => false,
			),
		);


		/////

		foreach ($defs as $def) {
			$this->_createSitemapFile($def['className'], $def['name'], $def['section'], $forced, $verbose, $output, $def['slugged']);
		}

		/////

		$templating = $this->get('twig');

		if ($verbose) {
			$output->write('<info>Building index sitemap...</info>');
		}

		$sitemaps = array();

		foreach ($defs as $def) {
			$sitemaps[] = $this->_getEntitySitemap($def['className'], $def['section']);
		}

		$data = $templating->render('Command/_cron-sitemap-index.xml.twig', array(
			'sitemaps' => $sitemaps,
		));

		if ($forced) {

			$filename = dirname(__FILE__).'/../../public/sitemap-index.xml';

			if ($verbose) {
				$output->write('<info> -> Wrinting '.$filename.' file...</info>');
			}

			$fp = fopen($filename, 'w');
			fwrite($fp, $data);
			fclose($fp);

			if ($verbose) {
				$output->writeln('<comment> [Done]</comment>');
			}
		} else {
			if ($verbose) {
				$output->writeln('<comment> [Fake]</comment>');
			}
		}

        return Command::SUCCESS;
	}

	private function _createSitemapFile($entityClassName, $entityName, $section, $forced, $verbose, OutputInterface $output, $slugged = true) {
		$templating = $this->get('twig');

		if ($verbose) {
			$output->writeln('<info>Building '.$section.' sitemap...</info>');
		}

		$urls = $this->_getEntityUrls($entityClassName, $entityName, $forced, $verbose, $output, $slugged);
		$data = $templating->render('Command/_cron-sitemap-entities.xml.twig', array(
			'urls' => $urls,
		));

		unset($urls);

		if ($forced) {

			$filename = dirname(__FILE__).'/../../public/sitemap-'.$section.'.xml';

			if ($verbose) {
				$output->write('<info> -> Wrinting '.$filename.' file...</info>');
			}

			$fp = fopen($filename, 'w');
			fwrite($fp, $data);
			fclose($fp);

			if ($verbose) {
				$output->writeln('<comment> [Done]</comment>');
			}
		} else {
			if ($verbose) {
				$output->writeln('<comment> [Fake]</comment>');
			}
		}

        return Command::SUCCESS;
	}

	/////

	private function _getEntityUrls($entityClassName, $entityName, $forced, $verbose, OutputInterface $output, $slugged = true) {
		$router = $this->get('router');
		$om = $this->getDoctrine()->getManager();
		$entityRepository = $om->getRepository($entityClassName);
		$picturedUtils = $this->get(PicturedUtils::class);
		$videoHostingUtils = $this->get(VideoHostingUtils::class);

		$urls = array();
		$entities = $entityRepository->findAll();

		$progress = new ProgressBar($output, count($entities));
		$progress->start();

		foreach ($entities as $entity) {

			$progress->advance();

			if ($entity instanceof SitemapableInterface && !$entity->getIsSitemapable()) {
				continue;
			}

			// Images & Videos
			$images = array();
			$videos = array();
			if ($entity instanceof MultiPicturedInterface) {
				foreach ($entity->getPictures() as $picture) {
					$image = $picturedUtils->getPictureSitemapData($picture);
					if (!is_null($image)) {
						$images[] = $image;
					}
				}
			} elseif ($entity instanceof PicturedInterface) {
				$image = $picturedUtils->getPictureSitemapData($entity->getMainPicture());
				if (!is_null($image)) {
					$images[] = $image;
				}
			}
			if ($entity instanceof BlockBodiedInterface) {
				foreach ($entity->getBodyBlocks() as $block) {
					if ($block instanceof \App\Entity\Core\Block\Video) {
						$video = $videoHostingUtils->getVideoSitemapData($block->getKind(), $block->getEmbedIdentifier());
						if (!is_null($video) && !$this->_isVideoAsExported($block->getKind(), $block->getEmbedIdentifier())) {
							$videos[] = $video;
							$this->_flagVideoAsExported($block->getKind(), $block->getEmbedIdentifier());
						}
					}
					if ($block instanceof \App\Entity\Core\Block\Gallery) {
						foreach ($block->getPictures() as $picture) {
							$image = $picturedUtils->getPictureSitemapData($picture);
							if (!is_null($image)) {
								$images[] = $image;
							}
						}
					}
				}
			}
			if ($entity instanceof \App\Entity\Find\Find) {
				if ($entity->getContent() instanceof \App\Entity\Find\Content\Video) {
					$video = $videoHostingUtils->getVideoSitemapData($entity->getContent()->getKind(), $entity->getContent()->getEmbedIdentifier());
					if (!is_null($video) && !$this->_isVideoAsExported($entity->getContent()->getKind(), $entity->getContent()->getEmbedIdentifier())) {
						$videos[] = $video;
						$this->_flagVideoAsExported($entity->getContent()->getKind(), $entity->getContent()->getEmbedIdentifier());
					}
				}
			}
			if ($entity instanceof \App\Entity\Howto\Howto) {
				foreach ($entity->getArticles() as $article) {
					foreach ($article->getBodyBlocks() as $block) {
						if ($block instanceof \App\Entity\Core\Block\Video) {
							$video = $videoHostingUtils->getVideoSitemapData($block->getKind(), $block->getEmbedIdentifier());
							if (!is_null($video) && !$this->_isVideoAsExported($block->getKind(), $block->getEmbedIdentifier())) {
								$videos[] = $video;
								$this->_flagVideoAsExported($block->getKind(), $block->getEmbedIdentifier());
							}
						}
						if ($block instanceof \App\Entity\Core\Block\Gallery) {
							foreach ($block->getPictures() as $picture) {
								$image = $picturedUtils->getPictureSitemapData($picture);
								if (!is_null($image)) {
									$images[] = $image;
								}
							}
						}
					}
				}
			}
			if ($entity instanceof \App\Entity\Qa\Question) {
				foreach ($entity->getAnswers() as $answer) {
					foreach ($answer->getBodyBlocks() as $block) {
						if ($block instanceof \App\Entity\Core\Block\Video) {
							$video = $videoHostingUtils->getVideoSitemapData($block->getKind(), $block->getEmbedIdentifier());
							if (!is_null($video) && !$this->_isVideoAsExported($block->getKind(), $block->getEmbedIdentifier())) {
								$videos[] = $video;
								$this->_flagVideoAsExported($block->getKind(), $block->getEmbedIdentifier());
							}
						}
						if ($block instanceof \App\Entity\Core\Block\Gallery) {
							foreach ($block->getPictures() as $picture) {
								$image = $picturedUtils->getPictureSitemapData($picture);
								if (!is_null($image)) {
									$images[] = $image;
								}
							}
						}
					}
				}
			}

			// License
			$license = null;
			if ($entity instanceof LicensedInterface) {
				$license = $entity->getLicense();
			}

			$urls[] = array(
				'loc'        => $router->generate('core_'.$entityName.'_show', $slugged ? array('id' => $entity->getSluggedId()) : array('username' => $entity->getUsernameCanonical()), UrlGeneratorInterface::ABSOLUTE_URL),
				'lastmod'    => is_null($entity->getUpdatedAt()) ? $entity->getCreatedAt()->format('Y-m-d\TH:i:sP') : $entity->getUpdatedAt()->format('Y-m-d\TH:i:sP'),
				'changefreq' => 'daily',
				'images'     => $images,
				'videos'     => $videos,
				'license'    => $license,
			);
		}

		$progress->finish();

		return $urls;
	}

	private function _isVideoAsExported($kind, $embedIdentifer) {
		if (isset($this->exportedVideosIdentifiers[$kind.$embedIdentifer])) {
			return $this->exportedVideosIdentifiers[$kind.$embedIdentifer];
		}
		return false;
	}

	private function _flagVideoAsExported($kind, $embedIdentifer) {
		$this->exportedVideosIdentifiers[$kind.$embedIdentifer] = true;
	}

	/////

	private function _getEntitySitemap($entityClassName, $section) {
		$om = $this->getDoctrine()->getManager();
		$entityRepository = $om->getRepository($entityClassName);
		$lastCreatedEntity = $entityRepository->findLastCreated();
		$lastUpdatedEntity = $entityRepository->findLastUpdated();
		$createdAt = !is_null($lastCreatedEntity) ? $lastCreatedEntity->getCreatedAt() : null;
		$updatedAt = !is_null($lastUpdatedEntity) ? $lastUpdatedEntity->getUpdatedAt() : null;
		if (!is_null($createdAt) && $createdAt > $updatedAt) {
			$lastmod = $createdAt->format('Y-m-d\TH:i:sP');
		} else if (!is_null($updatedAt)) {
			$lastmod = $updatedAt->format('Y-m-d\TH:i:sP');
		} else {
			$lastmod = date_format(new \DateTime(), 'Y-m-d\TH:i:sP');
		}
		return array(
			'loc'     => $this->get('assets.packages')->getUrl('/sitemap-'.$section.'.xml', 'sitemaps'),
			'lastmod' => $lastmod,
		);
	}

}