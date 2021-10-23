<?php

namespace App\Utils;

use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\DependencyInjection\ContainerInterface;
use App\Model\TaggableInterface;
use App\Entity\Core\TagUsage;

class TagUtils extends AbstractContainerAwareUtils {

	public function useTaggableTags(TaggableInterface $taggable, $previouslyUsedTags = null) {
        $om = $this->getDoctrine()->getManager();

		$tags = $taggable->getTags();
		if (is_null($previouslyUsedTags)) {
			$newUsedTags = $tags;
		} else {
			$newUsedTags = array();
			foreach ($tags as $tag) {
				$new = true;
				foreach ($previouslyUsedTags as $previouslyUsedTag) {
					if ($previouslyUsedTag->getId() == $tag->getId()) {
						$new = false;
						break;
					}
				}
				if ($new) {
					$newUsedTags[] = $tag;
				}
			}
		}
		$entityType = $taggable->getType();

		$tagUsageRepository = $om->getRepository(TagUsage::CLASS_NAME);
		foreach ($newUsedTags as $tag) {

			$tagUsage = $tagUsageRepository->findOneByTagAndEntityType($tag, $entityType);
			if (is_null($tagUsage)) {

				$tagUsage = new TagUsage();
				$tagUsage->setTag($tag);
				$tagUsage->setEntityType($entityType);

				$om->persist($tagUsage);
			}
			$tagUsage->incrementScore();

		}

		$om->flush();

	}

	public function getProposals(TaggableInterface $taggable, $maxResults = 30) {
        $om = $this->getDoctrine()->getManager();

		$proposals = array();

		$tagUsageRepository = $om->getRepository(TagUsage::CLASS_NAME);
		$tagUsages = $tagUsageRepository->findByEntityType($taggable->getType(), $maxResults);
		if (!is_null($tagUsages)) {
			foreach ($tagUsages as $tagUsage) {
				$proposals[] = array( $tagUsage->getTag()->getLabel(), $tagUsage->getHighlighted() );
			}
		}

		return $proposals;
	}

}