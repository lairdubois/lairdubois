<?php

namespace Ladb\CoreBundle\Manager\Blog;

use Ladb\CoreBundle\Entity\Blog\Post;
use Ladb\CoreBundle\Manager\AbstractPublicationManager;

class PostManager extends AbstractPublicationManager {

	const NAME = 'ladb_core.post_manager';

	/////

	public function publish(Post $post, $flush = true) {


		parent::publishPublication($post, $flush);
	}

	public function unpublish(Post $post, $flush = true) {


		parent::unpublishPublication($post, $flush);
	}

	public function delete(Post $post, $withWitness = true, $flush = true) {
		parent::deletePublication($post, $withWitness, $flush);
	}

}