<?php

namespace Ladb\CoreBundle\Utils;

use Ladb\CoreBundle\Manager\Core\UserManager;
use Ladb\CoreBundle\Model\BlockBodiedInterface;
use Ladb\CoreBundle\Model\BodiedInterface;
use Ladb\CoreBundle\Model\HtmlBodiedInterface;
use Ladb\CoreBundle\Model\TitledInterface;
use Ladb\CoreBundle\Parser\Markdown\LadbMarkdown;

class FieldPreprocessorUtils extends AbstractContainerAwareUtils {

	const NAME = 'ladb_core.field_preprocessor_utils';

	public function preprocessFields($entity) {
		if ($entity instanceof TitledInterface) {
			$this->preprocessTitleField($entity);
		}
		if ($entity instanceof BlockBodiedInterface) {
			$this->preprocessBodyBlocksField($entity);
			$this->preprocessBodyField($entity);
		}
		if ($entity instanceof HtmlBodiedInterface) {
			$this->preprocessBodyField($entity);
			$this->preprocessHtmlBodyField($entity);
		}
	}

	public function preprocessTitleField(TitledInterface $titled) {
		mb_internal_encoding("UTF-8");		// Best place in php.ini
		$titled->setTitle(mb_strtoupper(mb_substr($titled->getTitle(), 0, 1)).mb_substr($titled->getTitle(), 1));
	}

	public function preprocessBodyField(BodiedInterface $bodied) {

		// Cleaup body field
		$patterns = array(
			'/\r\n\r\n(\r\n)+|\n\n\n+/', '/ ( )+/'
		);
		$replacements = array(
			"\n\n", ' '
		);
		$body = trim(preg_replace($patterns, $replacements, $bodied->getBody()));
		$body = (new \Emojione\Client(new \Emojione\Ruleset()))->toShort($body);
		$bodied->setBody($body);

	}

	public function preprocessHtmlBodyField(HtmlBodiedInterface $bodied) {

		// Render HTML Body
		$parser = new LadbMarkdown($this->get(UserManager::NAME), $this->get('router'));
		$htmlBody = $parser->parse($bodied->getBody());
		$bodied->setHtmlBody($htmlBody);

	}

	public function preprocessBodyBlocksField(BlockBodiedInterface $blockBodied) {
		foreach ($blockBodied->getBodyBlocks() as $block) {
			if ($block instanceof \Ladb\CoreBundle\Model\HtmlBodiedInterface) {
				$this->preprocessBodyField($block);
				$this->preprocessHtmlBodyField($block);
			}
		}
		$firstBlock = $blockBodied->getBodyBlocks()->first();
		if ($firstBlock instanceof \Ladb\CoreBundle\Entity\Core\Block\Text) {
			$blockBodied->setBodyExtract(strip_tags(mb_strimwidth($firstBlock->getHtmlBody(), 0, 250, '[...]')));
		}
	}

}