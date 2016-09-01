<?php

namespace Ladb\CoreBundle\Entity\Funding;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * @ORM\Table("tbl_funding_charge")
 * @ORM\Entity(repositoryClass="Ladb\CoreBundle\Repository\Funding\DonationRepository")
 */
class Charge {

	const CLASS_NAME = 'LadbCoreBundle:Funding\Charge';

	/**
	 * @ORM\Column(name="id", type="integer")
	 * @ORM\Id
	 * @ORM\GeneratedValue(strategy="AUTO")
	 */
	private $id;

	/**
	 * @ORM\Column(name="start_month", type="smallint")
	 */
	private $startMonth;

	/**
	 * @ORM\Column(name="start_year", type="smallint")
	 */
	private $startYear;

	/**
	 * @ORM\Column(name="end_month", type="smallint")
	 */
	private $endMonth;

	/**
	 * @ORM\Column(name="end_year", type="smallint")
	 */
	private $endYear;

	/**
	 * @ORM\Column(type="integer")
	 */
	private $amount = 0;

}
