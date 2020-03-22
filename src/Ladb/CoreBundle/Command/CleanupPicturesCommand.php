<?php

namespace Ladb\CoreBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\OutputInterface;

class CleanupPicturesCommand extends ContainerAwareCommand {

	protected function configure() {
		$this
			->setName('ladb:cleanup:pictures')
			->addOption('force', null, InputOption::VALUE_NONE, 'Force removing')
			->setDescription('Cleanup pictures')
			->setHelp(<<<EOT
The <info>ladb:cleanup:pictures</info> command remove unused pictures
EOT
		);
	}

	protected function execute(InputInterface $input, OutputInterface $output) {

		$om = $this->getContainer()->get('doctrine')->getManager();

		// Extract Pictures /////

		$queryBuilder = $om->createQueryBuilder();
		$queryBuilder
			->select(array( 'p' ))
			->from('LadbCoreBundle:Core\Picture', 'p')
		;

		try {
			$pictures = $queryBuilder->getQuery()->getResult();
		} catch (\Doctrine\ORM\NoResultException $e) {
			$pictures = array();
		}

		$pictureCounters = array();
		foreach ($pictures as $picture) {
			$pictureCounters[$picture->getId()] = array( $picture, 0 );
		}

		// Check Resources /////

		$output->writeln('<info>Checking resources...</info>');

		$queryBuilder = $om->createQueryBuilder();
		$queryBuilder
			->select(array( 'r', 'th' ))
			->from('LadbCoreBundle:Core\Resource', 'r')
			->leftJoin('r.thumbnail', 'th')
		;

		try {
			$resources = $queryBuilder->getQuery()->getResult();
		} catch (\Doctrine\ORM\NoResultException $e) {
			$resources = array();
		}

		foreach ($resources as $resource) {
			$thumbnail = $resource->getThumbnail();
			if (!is_null($thumbnail)) {
				$pictureCounters[$thumbnail->getId()][1]++;
			}
		}
		unset($resources);

		// Check Comments /////

		$output->writeln('<info>Checking comments...</info>');

		$queryBuilder = $om->createQueryBuilder();
		$queryBuilder
			->select(array( 'c', 'ps' ))
			->from('LadbCoreBundle:Core\Comment', 'c')
			->leftJoin('c.pictures', 'ps')
		;

		try {
			$comments = $queryBuilder->getQuery()->getResult();
		} catch (\Doctrine\ORM\NoResultException $e) {
			$comments = array();
		}

		foreach ($comments as $comment) {
			foreach ($comment->getPictures() as $picture) {
				$pictureCounters[$picture->getId()][1]++;
			}
		}
		unset($comments);

		// Check Messages /////

		$output->writeln('<info>Checking messages...</info>');

		$queryBuilder = $om->createQueryBuilder();
		$queryBuilder
			->select(array( 'm', 'ps' ))
			->from('LadbCoreBundle:Message\Message', 'm')
			->leftJoin('m.pictures', 'ps')
		;

		try {
			$messages = $queryBuilder->getQuery()->getResult();
		} catch (\Doctrine\ORM\NoResultException $e) {
			$messages = array();
		}

		foreach ($messages as $message) {
			foreach ($message->getPictures() as $picture) {
				$pictureCounters[$picture->getId()][1]++;
			}
		}
		unset($messages);

		// Check Avatars and Banners /////

		$output->writeln('<info>Checking avatars and banners...</info>');

		$queryBuilder = $om->createQueryBuilder();
		$queryBuilder
			->select(array( 'u', 'um', 'a', 'b' ))
			->from('LadbCoreBundle:Core\User', 'u')
			->innerJoin('u.meta', 'um')
			->leftJoin('u.avatar', 'a')
			->leftJoin('um.banner', 'b')
		;

		try {
			$users = $queryBuilder->getQuery()->getResult();
		} catch (\Doctrine\ORM\NoResultException $e) {
			$users = array();
		}

		foreach ($users as $user) {
			$avatar = $user->getAvatar();
			if (!is_null($avatar)) {
				$pictureCounters[$avatar->getId()][1]++;
			}
			$banner = $user->getMeta()->getBanner();
			if (!is_null($banner)) {
				$pictureCounters[$banner->getId()][1]++;
			}
		}
		unset($users);

		// Check Creations /////

		$output->writeln('<info>Checking creations...</info>');

		$queryBuilder = $om->createQueryBuilder();
		$queryBuilder
			->select(array( 'c', 'mp', 'ps', 'sk', 'sp' ))
			->from('LadbCoreBundle:Wonder\Creation', 'c')
			->leftJoin('c.mainPicture', 'mp')
			->leftJoin('c.pictures', 'ps')
			->leftJoin('c.sticker', 'sk')
			->leftJoin('c.strip', 'sp')
		;

		try {
			$creations = $queryBuilder->getQuery()->getResult();
		} catch (\Doctrine\ORM\NoResultException $e) {
			$creations = array();
		}

		foreach ($creations as $creation) {
			$mainPicture = $creation->getMainPicture();
			if (!is_null($mainPicture)) {
				$pictureCounters[$mainPicture->getId()][1]++;
			}
			foreach ($creation->getPictures() as $picture) {
				$pictureCounters[$picture->getId()][1]++;
			}
			$sticker = $creation->getSticker();
			if (!is_null($sticker)) {
				$pictureCounters[$sticker->getId()][1]++;
			}
			$strip = $creation->getStrip();
			if (!is_null($strip)) {
				$pictureCounters[$strip->getId()][1]++;
			}
		}
		unset($creations);

		// Check Plans /////

		$output->writeln('<info>Checking plans...</info>');

		$queryBuilder = $om->createQueryBuilder();
		$queryBuilder
			->select(array( 'p', 'mp', 'ps', 'sk', 'sp' ))
			->from('LadbCoreBundle:Wonder\Plan', 'p')
			->leftJoin('p.mainPicture', 'mp')
			->leftJoin('p.pictures', 'ps')
			->leftJoin('p.sticker', 'sk')
			->leftJoin('p.strip', 'sp')
		;

		try {
			$plans = $queryBuilder->getQuery()->getResult();
		} catch (\Doctrine\ORM\NoResultException $e) {
			$plans = array();
		}

		foreach ($plans as $plan) {
			$mainPicture = $plan->getMainPicture();
			if (!is_null($mainPicture)) {
				$pictureCounters[$mainPicture->getId()][1]++;
			}
			foreach ($plan->getPictures() as $picture) {
				$pictureCounters[$picture->getId()][1]++;
			}
			$sticker = $plan->getSticker();
			if (!is_null($sticker)) {
				$pictureCounters[$sticker->getId()][1]++;
			}
			$strip = $plan->getStrip();
			if (!is_null($strip)) {
				$pictureCounters[$strip->getId()][1]++;
			}
		}
		unset($plans);

		// Check Workshops /////

		$output->writeln('<info>Checking workshops...</info>');

		$queryBuilder = $om->createQueryBuilder();
		$queryBuilder
			->select(array( 'w', 'mp', 'ps', 'sk', 'sp' ))
			->from('LadbCoreBundle:Wonder\Workshop', 'w')
			->leftJoin('w.mainPicture', 'mp')
			->leftJoin('w.pictures', 'ps')
			->leftJoin('w.sticker', 'sk')
			->leftJoin('w.strip', 'sp')
		;

		try {
			$workshops = $queryBuilder->getQuery()->getResult();
		} catch (\Doctrine\ORM\NoResultException $e) {
			$workshops = array();
		}

		foreach ($workshops as $workshop) {
			$mainPicture = $workshop->getMainPicture();
			if (!is_null($mainPicture)) {
				$pictureCounters[$mainPicture->getId()][1]++;
			}
			foreach ($workshop->getPictures() as $picture) {
				$pictureCounters[$picture->getId()][1]++;
			}
			$sticker = $workshop->getSticker();
			if (!is_null($sticker)) {
				$pictureCounters[$sticker->getId()][1]++;
			}
			$strip = $workshop->getStrip();
			if (!is_null($strip)) {
				$pictureCounters[$strip->getId()][1]++;
			}
		}
		unset($workshops);

		// Check Finds /////

		$output->writeln('<info>Checking finds...</info>');

		$queryBuilder = $om->createQueryBuilder();
		$queryBuilder
			->select(array( 'f', 'c' ))
			->from('LadbCoreBundle:Find\Find', 'f')
			->leftJoin('f.content', 'c')
		;

		try {
			$finds = $queryBuilder->getQuery()->getResult();
		} catch (\Doctrine\ORM\NoResultException $e) {
			$finds = array();
		}

		foreach ($finds as $find) {
			$mainPicture = $find->getMainPicture();
			if (!is_null($mainPicture)) {
				$pictureCounters[$mainPicture->getId()][1]++;
			}
			$content = $find->getContent();
			if ($content instanceof \Ladb\CoreBundle\Entity\Find\Content\Gallery) {
				foreach ($content->getPictures() as $picture) {
					$pictureCounters[$picture->getId()][1]++;
				}
			}
		}
		unset($finds);

		// Check Events /////

		$output->writeln('<info>Checking events...</info>');

		$queryBuilder = $om->createQueryBuilder();
		$queryBuilder
			->select(array( 'e', 'mp', 'ps' ))
			->from('LadbCoreBundle:Event\Event', 'e')
			->leftJoin('e.mainPicture', 'mp')
			->leftJoin('e.pictures', 'ps')
		;

		try {
			$events = $queryBuilder->getQuery()->getResult();
		} catch (\Doctrine\ORM\NoResultException $e) {
			$events = array();
		}

		foreach ($events as $event) {
			$mainPicture = $event->getMainPicture();
			if (!is_null($mainPicture)) {
				$pictureCounters[$mainPicture->getId()][1]++;
			}
			foreach ($event->getPictures() as $picture) {
				$pictureCounters[$picture->getId()][1]++;
			}
		}
		unset($events);

		// Check Howtos and Articles /////

		$output->writeln('<info>Checking howtos...</info>');

		$queryBuilder = $om->createQueryBuilder();
		$queryBuilder
			->select(array( 'h', 'a', 'mp', 'sk', 'sp' ))
			->from('LadbCoreBundle:Howto\Howto', 'h')
			->leftJoin('h.articles', 'a')
			->leftJoin('h.mainPicture', 'mp')
			->leftJoin('h.sticker', 'sk')
			->leftJoin('h.strip', 'sp')
		;

		try {
			$howtos = $queryBuilder->getQuery()->getResult();
		} catch (\Doctrine\ORM\NoResultException $e) {
			$howtos = array();
		}

		foreach ($howtos as $howto) {
			$mainPicture = $howto->getMainPicture();
			if (!is_null($mainPicture)) {
				$pictureCounters[$mainPicture->getId()][1]++;
			}
			$sticker = $howto->getSticker();
			if (!is_null($sticker)) {
				$pictureCounters[$sticker->getId()][1]++;
			}
			foreach ($howto->getArticles() as $article) {
				$sticker = $article->getSticker();
				if (!is_null($sticker)) {
					$pictureCounters[$sticker->getId()][1]++;
				}
			}
			$strip = $howto->getStrip();
			if (!is_null($strip)) {
				$pictureCounters[$strip->getId()][1]++;
			}
		}
		unset($howtos);

		// Check Blog Posts /////

		$output->writeln('<info>Checking blog posts...</info>');

		$queryBuilder = $om->createQueryBuilder();
		$queryBuilder
			->select(array( 'p', 'mp' ))
			->from('LadbCoreBundle:Blog\Post', 'p')
			->leftJoin('p.mainPicture', 'mp')
		;

		try {
			$posts = $queryBuilder->getQuery()->getResult();
		} catch (\Doctrine\ORM\NoResultException $e) {
			$posts = array();
		}

		foreach ($posts as $post) {
			$mainPicture = $post->getMainPicture();
			if (!is_null($mainPicture)) {
				$pictureCounters[$mainPicture->getId()][1]++;
			}
		}
		unset($posts);

		// Check Promotion Graphics /////

		$output->writeln('<info>Checking promotion graphics...</info>');

		$queryBuilder = $om->createQueryBuilder();
		$queryBuilder
			->select(array( 'g', 'mp' ))
			->from('LadbCoreBundle:Promotion\Graphic', 'g')
			->leftJoin('g.mainPicture', 'mp')
		;

		try {
			$graphics = $queryBuilder->getQuery()->getResult();
		} catch (\Doctrine\ORM\NoResultException $e) {
			$graphics = array();
		}

		foreach ($graphics as $graphic) {
			$mainPicture = $graphic->getMainPicture();
			if (!is_null($mainPicture)) {
				$pictureCounters[$mainPicture->getId()][1]++;
			}
		}
		unset($graphics);

		// Check Qa Questions /////

		$output->writeln('<info>Checking qa questions...</info>');

		$queryBuilder = $om->createQueryBuilder();
		$queryBuilder
			->select(array( 'q', 'mp' ))
			->from('LadbCoreBundle:Qa\Question', 'q')
			->leftJoin('q.mainPicture', 'mp')
		;

		try {
			$questions = $queryBuilder->getQuery()->getResult();
		} catch (\Doctrine\ORM\NoResultException $e) {
			$questions = array();
		}

		foreach ($questions as $question) {
			$mainPicture = $question->getMainPicture();
			if (!is_null($mainPicture)) {
				$pictureCounters[$mainPicture->getId()][1]++;
			}
		}
		unset($question);

		// Check Faq Questions /////

		$output->writeln('<info>Checking faq questions...</info>');

		$queryBuilder = $om->createQueryBuilder();
		$queryBuilder
			->select(array( 'q', 'mp' ))
			->from('LadbCoreBundle:Faq\Question', 'q')
			->leftJoin('q.mainPicture', 'mp')
		;

		try {
			$questions = $queryBuilder->getQuery()->getResult();
		} catch (\Doctrine\ORM\NoResultException $e) {
			$questions = array();
		}

		foreach ($questions as $question) {
			$mainPicture = $question->getMainPicture();
			if (!is_null($mainPicture)) {
				$pictureCounters[$mainPicture->getId()][1]++;
			}
		}
		unset($question);

		// Check Workflows /////

		$output->writeln('<info>Checking workflows...</info>');

		$queryBuilder = $om->createQueryBuilder();
		$queryBuilder
			->select(array( 'w', 'mp' ))
			->from('LadbCoreBundle:Workflow\Workflow', 'w')
			->leftJoin('w.mainPicture', 'mp')
		;

		try {
			$workflows = $queryBuilder->getQuery()->getResult();
		} catch (\Doctrine\ORM\NoResultException $e) {
			$workflows = array();
		}

		foreach ($workflows as $workflow) {
			$mainPicture = $workflow->getMainPicture();
			if (!is_null($mainPicture)) {
				$pictureCounters[$mainPicture->getId()][1]++;
			}
		}
		unset($workflows);

		// Check Offers /////

		$output->writeln('<info>Checking offers...</info>');

		$queryBuilder = $om->createQueryBuilder();
		$queryBuilder
			->select(array( 'o', 'mp', 'ps' ))
			->from('LadbCoreBundle:Offer\Offer', 'o')
			->leftJoin('o.mainPicture', 'mp')
			->leftJoin('o.pictures', 'ps')
		;

		try {
			$offers = $queryBuilder->getQuery()->getResult();
		} catch (\Doctrine\ORM\NoResultException $e) {
			$offers = array();
		}

		foreach ($offers as $offer) {
			$mainPicture = $offer->getMainPicture();
			if (!is_null($mainPicture)) {
				$pictureCounters[$mainPicture->getId()][1]++;
			}
			foreach ($offer->getPictures() as $picture) {
				$pictureCounters[$picture->getId()][1]++;
			}
		}
		unset($offers);

		// Check Woods /////

		$output->writeln('<info>Checking woods...</info>');

		$queryBuilder = $om->createQueryBuilder();
		$queryBuilder
			->select(array( 'w', 'mp', 'eg', 'lb', 'tr', 'le', 'br' ))
			->from('LadbCoreBundle:Knowledge\Wood', 'w')
			->leftJoin('w.mainPicture', 'mp')
			->leftJoin('w.endgrain', 'eg')
			->leftJoin('w.lumber', 'lb')
			->leftJoin('w.tree', 'tr')
			->leftJoin('w.leaf', 'le')
			->leftJoin('w.bark', 'br')
		;

		try {
			$woods = $queryBuilder->getQuery()->getResult();
		} catch (\Doctrine\ORM\NoResultException $e) {
			$woods = array();
		}

		foreach ($woods as $wood) {
			$mainPicture = $wood->getMainPicture();
			if (!is_null($mainPicture)) {
				$pictureCounters[$mainPicture->getId()][1]++;
			}
			$endgrain = $wood->getEndgrain();
			if (!is_null($endgrain)) {
				$pictureCounters[$endgrain->getId()][1]++;
			}
			$lumber = $wood->getLumber();
			if (!is_null($lumber)) {
				$pictureCounters[$lumber->getId()][1]++;
			}
			$tree = $wood->getTree();
			if (!is_null($tree)) {
				$pictureCounters[$tree->getId()][1]++;
			}
			$leaf = $wood->getLeaf();
			if (!is_null($leaf)) {
				$pictureCounters[$leaf->getId()][1]++;
			}
			$bark = $wood->getLeaf();
			if (!is_null($bark)) {
				$pictureCounters[$bark->getId()][1]++;
			}
		}
		unset($woods);

		// Check Providers /////

		$output->writeln('<info>Checking Providers...</info>');

		$queryBuilder = $om->createQueryBuilder();
		$queryBuilder
			->select(array( 'p', 'mp', 'ph' ))
			->from('LadbCoreBundle:Knowledge\Provider', 'p')
			->leftJoin('p.mainPicture', 'mp')
			->leftJoin('p.photo', 'ph')
		;

		try {
			$providers = $queryBuilder->getQuery()->getResult();
		} catch (\Doctrine\ORM\NoResultException $e) {
			$providers = array();
		}

		foreach ($providers as $provider) {
			$mainPicture = $provider->getMainPicture();
			if (!is_null($mainPicture)) {
				$pictureCounters[$mainPicture->getId()][1]++;
			}
			$photo = $provider->getPhoto();
			if (!is_null($photo)) {
				$pictureCounters[$photo->getId()][1]++;
			}
		}
		unset($providers);

		// Check Schools /////

		$output->writeln('<info>Checking Schools...</info>');

		$queryBuilder = $om->createQueryBuilder();
		$queryBuilder
			->select(array( 's', 'mp', 'ph' ))
			->from('LadbCoreBundle:Knowledge\School', 's')
			->leftJoin('s.mainPicture', 'mp')
			->leftJoin('s.photo', 'ph')
		;

		try {
			$schools = $queryBuilder->getQuery()->getResult();
		} catch (\Doctrine\ORM\NoResultException $e) {
			$schools = array();
		}

		foreach ($schools as $school) {
			$mainPicture = $school->getMainPicture();
			if (!is_null($mainPicture)) {
				$pictureCounters[$mainPicture->getId()][1]++;
			}
			$photo = $school->getPhoto();
			if (!is_null($photo)) {
				$pictureCounters[$photo->getId()][1]++;
			}
		}
		unset($schools);

		// Check Books /////

		$output->writeln('<info>Checking Books...</info>');

		$queryBuilder = $om->createQueryBuilder();
		$queryBuilder
			->select(array( 'b', 'mp', 'bc' ))
			->from('LadbCoreBundle:Knowledge\Book', 'b')
			->leftJoin('b.mainPicture', 'mp')
			->leftJoin('b.backCover', 'bc')
		;

		try {
			$books = $queryBuilder->getQuery()->getResult();
		} catch (\Doctrine\ORM\NoResultException $e) {
			$books = array();
		}

		foreach ($books as $book) {
			$mainPicture = $book->getMainPicture();
			if (!is_null($mainPicture)) {
				$pictureCounters[$mainPicture->getId()][1]++;
			}
			$backCover = $book->getBackCover();
			if (!is_null($backCover)) {
				$pictureCounters[$backCover->getId()][1]++;
			}
		}
		unset($books);

		// Check Softwares /////

		$output->writeln('<info>Checking Softwares...</info>');

		$queryBuilder = $om->createQueryBuilder();
		$queryBuilder
			->select(array( 's', 'mp', 'scs' ))
			->from('LadbCoreBundle:Knowledge\Software', 's')
			->leftJoin('s.mainPicture', 'mp')
			->leftJoin('s.screenshot', 'scs')
		;

		try {
			$softwares = $queryBuilder->getQuery()->getResult();
		} catch (\Doctrine\ORM\NoResultException $e) {
			$softwares = array();
		}

		foreach ($softwares as $software) {
			$mainPicture = $software->getMainPicture();
			if (!is_null($mainPicture)) {
				$pictureCounters[$mainPicture->getId()][1]++;
			}
			$screenshot = $software->getScreenshot();
			if (!is_null($screenshot)) {
				$pictureCounters[$screenshot->getId()][1]++;
			}
		}
		unset($books);

		// Check Knowledge/Value/Pictures /////

		$output->writeln('<info>Checking knowledge value pictures...</info>');

		$queryBuilder = $om->createQueryBuilder();
		$queryBuilder
			->select(array( 'v', 'd' ))
			->from('LadbCoreBundle:Knowledge\Value\Picture', 'v')
			->leftJoin('v.data', 'd')
		;

		try {
			$values = $queryBuilder->getQuery()->getResult();
		} catch (\Doctrine\ORM\NoResultException $e) {
			$values = array();
		}

		foreach ($values as $value) {
			$content = $value->getData();
			if (!is_null($content)) {
				$pictureCounters[$content->getId()][1]++;
			}
		}
		unset($values);

		// Check Block/Gallery /////

		$output->writeln('<info>Checking block galleries...</info>');

		$queryBuilder = $om->createQueryBuilder();
		$queryBuilder
			->select(array( 'g', 'ps' ))
			->from('LadbCoreBundle:Core\Block\Gallery', 'g')
			->leftJoin('g.pictures', 'ps')
		;

		try {
			$galleries = $queryBuilder->getQuery()->getResult();
		} catch (\Doctrine\ORM\NoResultException $e) {
			$galleries = array();
		}

		foreach ($galleries as $gallery) {
			foreach ($gallery->getPictures() as $picture) {
				$pictureCounters[$picture->getId()][1]++;
			}
		}
		unset($galleries);

		// Check Textures /////

		$output->writeln('<info>Checking textures...</info>');

		$queryBuilder = $om->createQueryBuilder();
		$queryBuilder
			->select(array( 'm' ))
			->from('LadbCoreBundle:Knowledge\Wood\Texture', 'm')
		;

		try {
			$textures = $queryBuilder->getQuery()->getResult();
		} catch (\Doctrine\ORM\NoResultException $e) {
			$textures = array();
		}

		foreach ($textures as $texture) {
			if (!is_null($texture->getSinglePicture())) {
				$pictureCounters[$texture->getSinglePicture()->getId()][1]++;
			}
			if (!is_null($texture->getMosaicPicture())) {
				$pictureCounters[$texture->getMosaicPicture()->getId()][1]++;
			}
		}
		unset($textures);

		// Check Tooks /////

		$output->writeln('<info>Checking Tooks...</info>');

		$queryBuilder = $om->createQueryBuilder();
		$queryBuilder
			->select(array( 't', 'mp' ))
			->from('LadbCoreBundle:Youtook\Took', 't')
			->leftJoin('t.mainPicture', 'mp')
		;

		try {
			$tooks = $queryBuilder->getQuery()->getResult();
		} catch (\Doctrine\ORM\NoResultException $e) {
			$tooks = array();
		}

		foreach ($tooks as $took) {
			$mainPicture = $took->getMainPicture();
			if (!is_null($mainPicture)) {
				$pictureCounters[$mainPicture->getId()][1]++;
			}
		}
		unset($tooks);

		// Check Collections /////

		$output->writeln('<info>Checking Collections...</info>');

		$queryBuilder = $om->createQueryBuilder();
		$queryBuilder
			->select(array( 'c', 'mp' ))
			->from('LadbCoreBundle:Collection\Collection', 'c')
			->innerJoin('c.mainPicture', 'mp')
		;

		try {
			$collections = $queryBuilder->getQuery()->getResult();
		} catch (\Doctrine\ORM\NoResultException $e) {
			$collections = array();
		}

		foreach ($collections as $collection) {
			$mainPicture = $collection->getMainPicture();
			if (!is_null($mainPicture)) {
				$pictureCounters[$mainPicture->getId()][1]++;
			}
		}
		unset($collections);

		// Cleanup /////

		$forced = $input->getOption('force');
		$verbose = $input->getOption('verbose');
		$unusedPictureCount = 0;
		foreach ($pictureCounters as $pictureCounter) {
			$counter = $pictureCounter[1];
			if ($counter == 0) {
				$unusedPictureCount++;
				$picture = $pictureCounter[0];
				if ($verbose) {
					$output->writeln('<info> -> "'.$picture->getPath().'" is unused</info>');
				}
				if ($forced) {
					$om->remove($picture);
				}
			}
		}

		if ($forced) {
			if ($unusedPictureCount > 0) {
				$om->flush();
			}
			$output->writeln('<info>'.$unusedPictureCount.' pictures removed</info>');
		} else {
			$output->writeln('<info>'.$unusedPictureCount.' pictures to remove</info>');
		}
	}

}