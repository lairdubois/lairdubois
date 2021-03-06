<?php

namespace Ladb\CoreBundle\Utils;

use Doctrine\Common\Persistence\ObjectManager;
use Ladb\CoreBundle\Entity\Core\Block\Text;
use Ladb\CoreBundle\Entity\Core\Block\Gallery;
use Ladb\CoreBundle\Entity\Core\Block\Video;
use Ladb\CoreBundle\Model\BlockBodiedInterface;
use Ladb\CoreBundle\Model\BodiedInterface;
use Ladb\CoreBundle\Model\MultiPicturedInterface;
use Ladb\CoreBundle\Model\TimestampableInterface;

class BlockBodiedUtils {

	const NAME = 'ladb_core.block_bodied_utils';

	protected $om;
	protected $videoHostingUtils;

	public function __construct(ObjectManager $om, VideoHostingUtils $videoHostingUtils) {
		$this->om = $om;
		$this->videoHostingUtils = $videoHostingUtils;
	}

	/////

	public function copyBodyTo(BodiedInterface $entitySrc, BlockBodiedInterface $entityDest, $sortIndex = 0) {

		// Copy body to text block

		$textBlock = new Text();
		$textBlock->setBody($entitySrc->getBody());
		if ($entitySrc instanceof TimestampableInterface) {
			$textBlock->setCreatedAt($entitySrc->getCreatedAt());
			$textBlock->setUpdatedAt($entitySrc->getUpdatedAt());
		}
		$textBlock->setSortIndex($sortIndex);
		$entityDest->addBodyBlock($textBlock);

		$this->preprocessBlocks($entityDest);

	}

	public function copyPicturesTo(MultiPicturedInterface $entitySrc, BlockBodiedInterface $entityDest, $sortIndex = 1) {
		if ($entitySrc->getPictures()->count() == 0) {
			return;
		}

		// Copy pictures to gallery block

		$galleryBlock = new Gallery();
		foreach ($entitySrc->getPictures() as $picture) {
			$galleryBlock->addPicture($picture);
		}
		if ($entitySrc instanceof TimestampableInterface) {
			$galleryBlock->setCreatedAt($entitySrc->getCreatedAt());
			$galleryBlock->setUpdatedAt($entitySrc->getUpdatedAt());
		}
		$galleryBlock->setSortIndex($sortIndex);
		$entityDest->addBodyBlock($galleryBlock);

		$this->preprocessBlocks($entityDest);

	}

	public function copyBlocksTo(BlockBodiedInterface $entitySrc, BlockBodiedInterface $entityDest) {
		$sortIndexOffet = $entitySrc->getBodyBlocks()->count() > 0 ? $entitySrc->getBodyBlocks()->last()->getSortIndex() : 0;
		foreach ($entitySrc->getBodyBlocks() as $block) {

			$newBlock = null;

			if ($block instanceof Text) {
				$newBlock = new Text();
				$newBlock->setBody($block->getBody());
			}
			else if ($block instanceof Gallery) {
				$newBlock = new Gallery();
				foreach ($block->getPictures() as $picture) {
					$newBlock->addPicture($picture);
				}
			}
			else if ($block instanceof Video) {
				$newBlock = new Video();
				$newBlock->setUrl($block->getUrl());
				$newBlock->setKind($block->getKind());
				$newBlock->setEmbedIdentifier($block->getEmbedIdentifier());
			}

			if (!is_null($newBlock)) {
				$newBlock->setSortIndex($block->getSortIndex() + $sortIndexOffet);
				$newBlock->setCreatedAt($block->getCreatedAt());
				$newBlock->setUpdatedAt($block->getUpdatedAt());

				$entityDest->addBodyBlock($newBlock);
			}

		}

		$this->preprocessBlocks($entityDest);

	}

	/////

	public function preprocessBlocks(BlockBodiedInterface $entity, $originalBlocks = null) {

		// Merge text blocks
		$previousBlock = null;
		$blocksToRemove = array();
		foreach ($entity->getBodyBlocks() as $block) {
			if ($previousBlock != null && $previousBlock instanceof Text && $block instanceof Text) {
				$previousBlock->setBody($previousBlock->getBody()."\n\n".$block->getBody());
				$blocksToRemove[] = $block;
			} else {
				$previousBlock = $block;
			}
		}
		foreach ($blocksToRemove as $block) {
			$entity->removeBodyBlock($block);
		}

		$pictureCount = 0;
		$videoBlockCount = 0;
		foreach ($entity->getBodyBlocks() as $block) {

			// Check gallery blocks
			if ($block instanceof Gallery) {
				$pictureCount += $block->getPictures()->count();
			}

			// Check video blocks
			if ($block instanceof Video) {
				$kindAndEmbedIdentifier = $this->videoHostingUtils->getKindAndEmbedIdentifier($block->getUrl());
				$block->setKind($kindAndEmbedIdentifier['kind']);
				$block->setEmbedIdentifier($kindAndEmbedIdentifier['embedIdentifier']);
				$videoBlockCount++;
			}

		}
		$entity->setBodyBlockPictureCount($pictureCount);
		$entity->setBodyBlockVideoCount($videoBlockCount);

		// Remove unused blocks
		if (!is_null($originalBlocks)) {
			$blocks = $entity->getBodyBlocks();
			foreach ($originalBlocks as $block) {
				if (false === $blocks->contains($block)) {
					$this->om->remove($block);
				}
			}
		}

		// Copy text blocks into body field
		$body = '';
		foreach ($entity->getBodyBlocks() as $block) {
			if ($block instanceof Text) {
				$body .= $block->getBody()."\n";
			}
		}
		$entity->setBody($body);

	}

	public function getFirstPicture(BlockBodiedInterface $entity) {
		foreach ($entity->getBodyBlocks() as $block) {

			// Check gallery blocks
			if ($block instanceof Gallery) {
				return $block->getPictures()->first();
			}

		}
		return null;
	}

}