<?php

namespace Ladb\CoreBundle\Twig;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Ladb\CoreBundle\Utils\VideoHostingUtils;
use Ladb\CoreBundle\Parser\Markdown\LadbMarkdown;
use Ladb\CoreBundle\Utils\TypableUtils;

class LadbExtension extends \Twig_Extension {

	private $container;

	public function __construct(ContainerInterface $container) {
		$this->container = $container;
	}

	public function getFilters() {
		return array(
			new \Twig_SimpleFilter('ladb_file_size_format', array( $this, 'fileSizeFormatFilter' )),
			new \Twig_SimpleFilter('ladb_truncate_at', array( $this, 'truncateAtFilter' )),
			new \Twig_SimpleFilter('ladb_markdown', array( $this, 'markdownFilter' )),
			new \Twig_SimpleFilter('ladb_url_trim', array( $this, 'urlTrimFilter' )),
		);
	}

	public function getFunctions() {
		return array(
			new \Twig_SimpleFunction('ladb_entity', array( $this, 'entityFunction' )),
			new \Twig_SimpleFunction('ladb_entity_url_action', array( $this, 'entityUrlActionFunction' )),
			new \Twig_SimpleFunction('ladb_entity_type_stripped_name', array( $this, 'entityTypeStrippedNameFunction' )),
			new \Twig_SimpleFunction('ladb_value2json_tokens', array( $this, 'value2jsonTokensFunction' )),
			new \Twig_SimpleFunction('ladb_estimate_row_count', array( $this, 'estimateRowCountFunction' )),
			new \Twig_SimpleFunction('ladb_video_player_frame', array( $this, 'videoPlayerFrameFunction' )),
			new \Twig_SimpleFunction('ladb_video_icon_class', array( $this, 'videoIconClassFunction' )),
		);
	}

	// Filters /////

	public function fileSizeFormatFilter($fileSize, $decimals = 0) {
		$size = array( 'o', 'Ko', 'Mo', 'Go', 'To', 'Po', 'Eo', 'Zo', 'Yo');
		$factor = floor((strlen($fileSize) - 1) / 3);
		return sprintf("%.{$decimals}f", $fileSize / pow(1024, $factor)).' '.@$size[$factor];
	}

	public function truncateAtFilter($str, $needles = array( '{', '*', '[', '#' ), $separator = ' [...]') {
		$strLen = strlen($str);
		$pos = $strLen;
		foreach ($needles as $needle) {
			$needlePos = strpos($str, $needle);
			if ($needlePos) {
				$pos = min($pos, $needlePos);
			}
		}
		if ($pos < $strLen) {
			return substr($str, 0, $pos).$separator;
		}
		return $str;
	}

	public function markdownFilter($str) {
		$parser = new LadbMarkdown();
		return $parser->parse($str);
	}

	public function urlTrimFilter($str) {
		$str = preg_replace('#^https?://#', '', rtrim($str,'/'));
		return $str;
	}

	// Functions /////

	public function entityFunction($type, $id) {
		$typableUtils = $this->container->get(TypableUtils::NAME);
		$typable = $typableUtils->findTypable($type, $id);
		return $typable;
	}

	public function entityUrlActionFunction($entity, $action = 'show', $absoluteUrl = true, $useSluggedId = true, $additionalParams = null) {
		$url = '';
		if ($entity instanceof \Ladb\CoreBundle\Model\TypableInterface) {
			$typableUtils = $this->container->get(TypableUtils::NAME);
			$url = $typableUtils->getUrlAction($entity, $action, $absoluteUrl, $useSluggedId, $additionalParams);
		}
		return $url;
	}

    public function entityTypeStrippedNameFunction($entity, $delimiter = '_') {
        if ($entity instanceof \Ladb\CoreBundle\Model\TypableInterface) {
			$typableUtils = $this->container->get(TypableUtils::NAME);
			return $typableUtils->getStrippedName($entity, $delimiter);
        }
        return '';
    }

	public function value2jsonTokensFunction($value) {
        if (is_null($value) || strlen($value) == 0) {
            return '[]';
        }
        $tokens = explode(',', $value);
        $json = '';
        foreach ($tokens as $token) {
            $json .= ',{"id":"'.$token.'","name":"'.$token.'"}';
        }
        return '['.substr($json, 1).']';
    }

	public function estimateRowCountFunction($str, $maxCharPerRow = 100, $min = 2, $max = 40) {
		$rowCount = 0;
		$lines = explode("\n", $str);
		foreach ($lines as $line) {
			$strLen = strlen($line);
			if ($strLen == 0) {
				$rowCount++;
			} else {
				$rowCount += ceil(strlen($line) / $maxCharPerRow);
			}
		}
		return min($max, max($min, $rowCount));
	}

	public function videoPlayerFrameFunction($kind, $embedIdentifier, $width = '560', $height = '420', $styleClass = '', $autoPlay = false) {
		$videoHostingUtils = $this->container->get(VideoHostingUtils::NAME);
		return $videoHostingUtils->getPlayerFrame($kind, $embedIdentifier, $width, $height, $styleClass, $autoPlay);
	}

	public function videoIconClassFunction($kind, $prefix = 'ladb-icon-') {
		$videoHostingUtils = $this->container->get(VideoHostingUtils::NAME);
		return $videoHostingUtils->getIconClass($kind, $prefix);
	}

    public function getName() {
		return 'ladb_extension';
	}

}