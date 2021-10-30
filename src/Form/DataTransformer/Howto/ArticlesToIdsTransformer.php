<?php

namespace App\Form\DataTransformer\Howto;

use App\Entity\Howto\Article;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Exception\TransformationFailedException;
use Symfony\Component\Form\Exception\UnexpectedTypeException;

class ArticlesToIdsTransformer implements DataTransformerInterface {

	private $om;

	public function __construct(EntityManagerInterface $om) {
		$this->om = $om;
	}

	public function transform($articles) {
		if (null === $articles) {
			return '';
		}

		if (!$articles instanceof \Doctrine\Common\Collections\Collection) {
			throw new UnexpectedTypeException($articles, '\Doctrine\Common\Collections\Collection');
		}

		$idsArray = array();
		foreach ($articles as $article) {
			$idsArray[] = $article->getId();
		}
		return implode(',', $idsArray);
	}

	public function reverseTransform($idsString) {
		if (!$idsString) {
			return array();
		}

		$articles = array();
		$idsStrings = preg_split("/[,]+/", $idsString);
		$repository = $this->om->getRepository(Article::class);
		$sortIndex = 0;
		foreach ($idsStrings as $idString) {
			$id = intval($idString);
			if ($id == 0) {
				continue;
			}
			$article = $repository->find($id);
			$article->setSortIndex($sortIndex++);
			if (is_null($article)) {
				throw new TransformationFailedException();
			}
			$articles[] = $article;
		}

		return $articles;
	}

}