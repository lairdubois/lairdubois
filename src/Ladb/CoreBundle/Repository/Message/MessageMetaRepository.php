<?php

namespace Ladb\CoreBundle\Repository\Message;

use Ladb\CoreBundle\Repository\AbstractEntityRepository;

class MessageMetaRepository extends AbstractEntityRepository {

	/////

	public function findOneByMessageAndParticipant($message, $participant) {
		$query = $this->getEntityManager()
			->createQuery('
                SELECT mm FROM LadbCoreBundle:Message\MessageMeta mm
                WHERE mm.message = :message
                AND mm.participant = :participant
            ')
			->setParameter('message', $message)
			->setParameter('participant', $participant);

		try {
			return $query->getSingleResult();
		} catch (\Doctrine\ORM\NoResultException $e) {
			return null;
		}
	}

}