<?php

namespace Ladb\CoreBundle\Manager\Howto;

use Ladb\CoreBundle\Entity\Howto\Article;
use Ladb\CoreBundle\Event\PublicationEvent;
use Ladb\CoreBundle\Event\PublicationListener;
use Ladb\CoreBundle\Manager\AbstractPublicationManager;
use Ladb\CoreBundle\Utils\SearchUtils;

class ArticleManager extends AbstractPublicationManager {

	const NAME = 'ladb_core.article_manager';

	/////

	public function publish(Article $article, $flush = true) {

		$howto = $article->getHowto();

		$howto->incrementDraftArticleCount(-1);
		$howto->incrementPublishedArticleCount();
		$howto->setChangedAt(new \DateTime());
		$howto->setUpdatedAt(new \DateTime());

		parent::publishPublication($article, $flush);

		// Dispatch publication event
		$dispatcher = $this->get('event_dispatcher');
		$dispatcher->dispatch(PublicationListener::PUBLICATION_CHANGED, new PublicationEvent($howto));

		// Search index update
		$searchUtils = $this->get(SearchUtils::NAME);
		$searchUtils->replaceEntityInIndex($howto);

	}

	public function unpublish(Article $article, $flush = true) {

		$howto = $article->getHowto();

		$article->setIsDraft(true);
		$howto->incrementDraftArticleCount(1);
		$howto->incrementPublishedArticleCount(-1);

		parent::unpublishPublication($article, $flush);

		// Search index update
		$searchUtils = $this->get(SearchUtils::NAME);
		$searchUtils->replaceEntityInIndex($howto);

	}

	public function delete(Article $article, $withWitness = true, $flush = true) {

		$howto = $article->getHowto();
		if ($article->getIsDraft() === true) {
			$howto->incrementDraftArticleCount(-1);
		} else {
			$howto->incrementPublishedArticleCount(-1);
		}

		parent::deletePublication($article, $withWitness, $flush);

		$howto->removeArticle($article);

	}

}