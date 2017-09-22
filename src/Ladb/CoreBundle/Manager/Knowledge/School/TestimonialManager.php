<?php

namespace Ladb\CoreBundle\Manager\Knowledge\School;

use Ladb\CoreBundle\Entity\Core\Block\Gallery;
use Ladb\CoreBundle\Entity\Core\Comment;
use Ladb\CoreBundle\Entity\Knowledge\School\Testimonial;
use Ladb\CoreBundle\Entity\Qa\Answer;
use Ladb\CoreBundle\Entity\Qa\Question;
use Ladb\CoreBundle\Manager\AbstractManager;
use Ladb\CoreBundle\Model\IndexableInterface;
use Ladb\CoreBundle\Utils\ActivityUtils;
use Ladb\CoreBundle\Utils\CommentableUtils;
use Ladb\CoreBundle\Utils\FieldPreprocessorUtils;
use Ladb\CoreBundle\Utils\SearchUtils;
use Ladb\CoreBundle\Utils\VotableUtils;

class TestimonialManager extends AbstractManager {

	const NAME = 'ladb_core.knowledge_school_testimonial_manager';

	/////

	public function delete(Testimonial $testimonial, $flush = true) {

		$school = $testimonial->getSchool();

		// Drecrement school testimonial count
		$school->incrementTestimonialCount(-1);

		parent::deleteEntity($testimonial, $flush);
	}

}