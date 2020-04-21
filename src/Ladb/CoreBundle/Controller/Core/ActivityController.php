<?php

namespace Ladb\CoreBundle\Controller\Core;

use Ladb\CoreBundle\Entity\Core\Activity\AbstractActivity;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Ladb\CoreBundle\Controller\AbstractController;
use Ladb\CoreBundle\Utils\PaginatorUtils;

/**
 * @Route("/activities")
 */
class ActivityController extends AbstractController {

	/**
	 * @Route("/", name="core_activity_list")
	 * @Route("/{filter}", requirements={"filter" = "[a-z-]+"}, name="core_activity_list_filter")
	 * @Route("/{filter}/{page}", requirements={"filter" = "[a-z-]+", "page" = "\d+"}, name="core_activity_list_filter_page")
	 * @Template("LadbCoreBundle:Core/Activity:list-xhr.html.twig")
	 */
	public function listAction(Request $request, $filter = "recent", $page = 0) {
		$om = $this->getDoctrine()->getManager();
		$activityRepository = $om->getRepository(AbstractActivity::CLASS_NAME);
		$paginatorUtils = $this->get(PaginatorUtils::NAME);

		$offset = $paginatorUtils->computePaginatorOffset($page, 9, 5);
		$limit = $paginatorUtils->computePaginatorLimit($page, 9, 5);
		$paginator = $activityRepository->findPaginedByUser($this->getUser(), $offset, $limit, $filter);
		$pageUrls = $paginatorUtils->generatePrevAndNextPageUrl('core_activity_list_filter_page', array( 'filter' => $filter ), $page, $paginator->count());

		$parameters = array(
			'filter'      => $filter,
			'prevPageUrl' => $pageUrls->prev,
			'nextPageUrl' => $pageUrls->next,
			'activitys'   => $paginator,
		);

		if ($page > 0) {
			return $this->render('LadbCoreBundle:Core/Activity:list-n-xhr.html.twig', $parameters);
		}

		return $parameters;
	}


}