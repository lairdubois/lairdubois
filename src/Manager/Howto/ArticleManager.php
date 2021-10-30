<?php

namespace App\Manager\Howto;

use App\Entity\Howto\Article;
use App\Event\PublicationEvent;
use App\Event\PublicationListener;
use App\Manager\AbstractPublicationManager;
use App\Utils\SearchUtils;

class ArticleManager extends AbstractPublicationManager {

    public static function getSubscribedServices() {
        return array_merge(parent::getSubscribedServices(), array(
            '?'.SearchUtils::class,
        ));
    }

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
		$dispatcher->dispatch(new PublicationEvent($howto), PublicationListener::PUBLICATION_CHANGED);

		// Search index update
		$searchUtils = $this->get(SearchUtils::class);
		$searchUtils->replaceEntityInIndex($howto);

	}

    public function unpublish(Article $article, $flush = true) {

		$howto = $article->getHowto();

		$article->setIsDraft(true);
		$howto->incrementDraftArticleCount(1);
		$howto->incrementPublishedArticleCount(-1);

		parent::unpublishPublication($article, $flush);

		// Search index update
		$searchUtils = $this->get(SearchUtils::class);
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