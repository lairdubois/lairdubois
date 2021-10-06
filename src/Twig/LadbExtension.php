<?php

namespace App\Twig;

use App\Utils\UrlUtils;
use App\Utils\VideoHostingUtils;
use App\Parser\Markdown\LadbMarkdown;
use App\Utils\TypableUtils;
use App\Fos\UserManager;
use Psr\Container\ContainerInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Contracts\Service\ServiceSubscriberInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigFunction;
use Twig\TwigTest;

class LadbExtension extends AbstractExtension implements ServiceSubscriberInterface {

	private $container;

	public function __construct(ContainerInterface $container) {
		$this->container = $container;
	}

    public static function getSubscribedServices()
    {
        return array(
            'router' => '?'.RouterInterface::class,
            'translator' => '?'.TranslatorInterface::class,
            UserManager::class => '?'.UserManager::class,
            UrlUtils::class => '?'.UrlUtils::class,
            TypableUtils::class => '?'.TypableUtils::class,
            VideoHostingUtils::class => '?'.VideoHostingUtils::class,
        );
    }

	public function getFilters() {
		return array(
			new TwigFilter('ladb_file_size_format', array( $this, 'fileSizeFormatFilter' )),
			new TwigFilter('ladb_truncate_at', array( $this, 'truncateAtFilter' )),
			new TwigFilter('ladb_markdown', array( $this, 'markdownFilter' )),
			new TwigFilter('ladb_url_trim', array( $this, 'urlTrimFilter' )),
			new TwigFilter('ladb_url_truncate', array( $this, 'urlTruncateFilter' )),
			new TwigFilter('ladb_url_beautify', array( $this, 'urlBeautifyFilter' )),
			new TwigFilter('ladb_duration', array( $this, 'durationFilter' )),
			new TwigFilter('ladb_hours_minutes_duration', array( $this, 'hoursMinutesDurationFilter' )),
            new TwigFilter('transchoice', array( $this, 'simulateTranschoice' ), array(
                'deprecated' => true,
                'is_safe' => ['html'],
            )),
		);
	}

	public function getFunctions() {
		return array(
			new TwigFunction('ladb_entity', array( $this, 'entityFunction' )),
			new TwigFunction('ladb_entity_url_action', array( $this, 'entityUrlActionFunction' )),
			new TwigFunction('ladb_entity_type_stripped_name', array( $this, 'entityTypeStrippedNameFunction' )),
			new TwigFunction('ladb_entity_type_icon', array( $this, 'entityTypeIconFunction' )),
			new TwigFunction('ladb_value2json_tokens', array( $this, 'value2jsonTokensFunction' )),
			new TwigFunction('ladb_estimate_row_count', array( $this, 'estimateRowCountFunction' )),
			new TwigFunction('ladb_video_player_frame', array( $this, 'videoPlayerFrameFunction' )),
			new TwigFunction('ladb_video_icon_class', array( $this, 'videoIconClassFunction' )),
		);
	}

	public function getTests() {
		return array(
			new TwigTest('ladb_instanceof', array( $this, 'isInstanceof' )),
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
		$parser = new LadbMarkdown($this->container->get(UserManager::class), $this->container->get('router'), $this->container->get(UrlUtils::class));
		return $parser->parse($str);
	}

	public function urlTrimFilter($str) {
		$str = preg_replace('#^https?://#', '', rtrim($str,'/'));
		return $str;
	}

	public function urlTruncateFilter($str, $removeProtocol = true, $lengthL = 14, $lengthR = 15, $separator = '...', $charset = 'UTF-8') {
		return $this->container->get(UrlUtils::class)->truncateUrl($str, $removeProtocol, $lengthL, $lengthR, $separator, $charset);
	}

	public function urlBeautifyFilter($str) {
		$components = parse_url($str);
		$str = '<span class="ladb-url-host">'.$components['host'].'</span>';
		if (isset($components['path'])) {
			$str .= '<span class="ladb-url-path">'.$components['path'].'</span>';
		}
		return '<span class="ladb-url">'.$str.'</span>';
	}

	public function durationFilter($duration, $long = false) {
		$d = new \DateTime();
		$d->add(new \DateInterval('PT'.$duration.'S'));
		$interval = $d->diff(new \DateTime());
		$translator = $this->container->get('translator');
		if ($interval->y > 0) {
			$str = $translator->transChoice('interval.'.($long ? 'long' : 'short').'.year', $interval->y, array('%count%' => $interval->y), 'date');
			if ($interval->m > 0) {
				$str .= ' '.$translator->transChoice('interval.'.($long ? 'long' : 'short').'.month', $interval->m, array('%count%' => $interval->m), 'date');
			}
		} else if ($interval->m > 0) {
			$str = $translator->transChoice('interval.'.($long ? 'long' : 'short').'.month', $interval->m, array('%count%' => $interval->m), 'date');
			if ($interval->d > 0) {
				$str .= ' '.$translator->transChoice('interval.'.($long ? 'long' : 'short').'.day', $interval->d, array('%count%' => $interval->d), 'date');
			}
		} else if ($interval->d > 0) {
			$str = $translator->transChoice('interval.'.($long ? 'long' : 'short').'.day', $interval->d, array('%count%' => $interval->d), 'date');
			if ($interval->h > 0) {
				$str .= ' '.$translator->transChoice('interval.'.($long ? 'long' : 'short').'.hour', $interval->h, array('%count%' => $interval->h), 'date');
			}
		} else if ($interval->h > 0) {
			$str = $translator->transChoice('interval.'.($long ? 'long' : 'short').'.hour', $interval->h, array('%count%' => $interval->h), 'date');
			if ($interval->i > 0) {
				$str .= ' '.$translator->transChoice('interval.'.($long ? 'long' : 'short').'.minute', $interval->i, array('%count%' => $interval->i), 'date');
			}
		} else if ($interval->i > 0) {
			$str = $translator->transChoice('interval.'.($long ? 'long' : 'short').'.minute', $interval->i, array('%count%' => $interval->i), 'date');
			if ($interval->s > 0) {
				$str .= ' '.$translator->transChoice('interval.'.($long ? 'long' : 'short').'.second', $interval->s, array('%count%' => $interval->s), 'date');
			}
		} else if ($interval->s > 0) {
			$str = $translator->transChoice('interval.'.($long ? 'long' : 'short').'.second', $interval->s, array('%count%' => $interval->s), 'date');
		} else {
			$str = '';
		}
		return $str;
	}

	public function hoursMinutesDurationFilter($duration, $long = false) {
		$str = '';
		if ($duration > 0) {
			$hours = floor($duration / 3600);
			$minutes = floor(($duration / 60) % 60);
			$translator = $this->container->get('translator');
			if ($hours > 0) {
				$str = $translator->transChoice('interval.'.($long ? 'long' : 'short').'.hour', $hours, array('%count%' => $hours), 'date');
				if ($minutes > 0) {
					$str .= ' '.$translator->transChoice('interval.'.($long ? 'long' : 'short').'.minute', $minutes, array('%count%' => $minutes), 'date');
				}
			} else if ($minutes > 0) {
				$str = $translator->transChoice('interval.'.($long ? 'long' : 'short').'.minute', $minutes, array('%count%' => $minutes), 'date');
			}
		}
		return $str;
	}

	public function entityFunction($type, $id) {
		$typableUtils = $this->container->get(TypableUtils::class);
		$typable = $typableUtils->findTypable($type, $id);
		return $typable;
	}

	public function entityUrlActionFunction($entity, $action = 'show', $absoluteUrl = true, $useSluggedId = true, $additionalParams = null) {
		$url = '';
		if ($entity instanceof \App\Model\TypableInterface) {
			$typableUtils = $this->container->get(TypableUtils::class);
			$url = $typableUtils->getUrlAction($entity, $action, $absoluteUrl, $useSluggedId, $additionalParams);
		}
		return $url;
	}

    public function entityTypeStrippedNameFunction($entityOrType, $delimiter = '_', $capitalize = false) {
		$typableUtils = $this->container->get(TypableUtils::class);
        if ($entityOrType instanceof \App\Model\TypableInterface) {
			return $typableUtils->getStrippedName($entityOrType, $delimiter, $capitalize);
        } else if (is_int($entityOrType) or is_string($entityOrType)) {
			return $typableUtils->getStrippedNameByType(intval($entityOrType), $delimiter, $capitalize);
		}
        return '';
    }

    public function entityTypeIconFunction($entityOrType) {
		$typableUtils = $this->container->get(TypableUtils::class);
        if ($entityOrType instanceof \App\Model\TypableInterface) {
			return $typableUtils->getIcon($entityOrType);
        } else if (is_int($entityOrType) or is_string($entityOrType)) {
			return $typableUtils->getIconByType(intval($entityOrType));
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
		$videoHostingUtils = $this->container->get(VideoHostingUtils::class);
		return $videoHostingUtils->getPlayerFrame($kind, $embedIdentifier, $width, $height, $styleClass, $autoPlay);
	}

	public function videoIconClassFunction($kind, $prefix = 'ladb-icon-') {
		$videoHostingUtils = $this->container->get(VideoHostingUtils::class);
		return $videoHostingUtils->getIconClass($kind, $prefix);
	}

	// Tests /////

	public function isInstanceof($object, $class) {
		$reflectionClass = new \ReflectionClass($class);
		return $reflectionClass->isInstance($object);
	}

    public function simulateTranschoice($message, $count, $parameters = array(), $catalog = 'messages')
    {
        if(!isset($parameters['%count%'])) {
            $parameters['%count%'] = $count;
        }
        return $this->container->get('translator')->trans($message, $parameters, $catalog);
	}
}