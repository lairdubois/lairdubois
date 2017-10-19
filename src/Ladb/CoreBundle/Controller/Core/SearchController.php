<?php

namespace Ladb\CoreBundle\Controller\Core;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Ladb\CoreBundle\Utils\PaginatorUtils;
use Ladb\CoreBundle\Utils\SearchUtils;

/**
 * @Route("/rechercher")
 */
class SearchController extends Controller {

	/**
	 * @Route("/", name="core_search")
	 * @Route("/creations", name="core_search_creations")
	 * @Template()
	 */
	public function searchAction(Request $request) {
		return $this->redirect($this->generateUrl('core_creation_list', array(
			'q'    => $request->get('q'),
		)));
	}

	/**
	 * @Route("/typeahead/tags.json", defaults={"_format" = "json"}, name="core_search_typeahead_tags_json")
	 * @Template()
	 */
	public function searchTypeaheadTagsAction(Request $request, $page = 0) {
		$searchUtils = $this->get(SearchUtils::NAME);

		$searchParameters = $searchUtils->searchPaginedEntities(
			$request,
			$page,
			function($facet, &$filters, &$sort) {
				$filters[] = new \Elastica\Query\Match('label', $facet->value);
			},
			null,
			'fos_elastica.index.ladb.core_tag',
			\Ladb\CoreBundle\Entity\Core\Tag::CLASS_NAME,
			null
		);

		$parameters = array_merge($searchParameters,  array(
			'tags'  => $searchParameters['entities'],
		));
		return $parameters;
	}

	/**
	 * @Route("/typeahead/input-skills.json", defaults={"_format" = "json"}, name="core_search_typeahead_input_skills_json")
	 * @Template()
	 */
	public function searchTypeaheadInputSkillsAction(Request $request, $page = 0) {
		$searchUtils = $this->get(SearchUtils::NAME);

		$searchParameters = $searchUtils->searchPaginedEntities(
			$request,
			$page,
			function($facet, &$filters, &$sort) {
				$filters[] = new \Elastica\Query\Match('label', $facet->value);
			},
			null,
			'fos_elastica.index.ladb.input_skill',
			\Ladb\CoreBundle\Entity\Input\Skill::CLASS_NAME,
			null
		);

		$parameters = array_merge($searchParameters,  array(
			'skills'  => $searchParameters['entities'],
		));
		return $parameters;
	}

	/**
	 * @Route("/typeahead/input-woods.json", defaults={"_format" = "json"}, name="core_search_typeahead_input_woods_json")
	 * @Template()
	 */
	public function searchTypeaheadInputWoodsAction(Request $request, $page = 0) {
		$searchUtils = $this->get(SearchUtils::NAME);

		$searchParameters = $searchUtils->searchPaginedEntities(
			$request,
			$page,
			function($facet, &$filters, &$sort) {
				$filters[] = new \Elastica\Query\Match('label', $facet->value);
			},
			null,
			'fos_elastica.index.ladb.input_wood',
			\Ladb\CoreBundle\Entity\Input\Wood::CLASS_NAME,
			null
		);

		$parameters = array_merge($searchParameters,  array(
			'woods'  => $searchParameters['entities'],
		));
		return $parameters;
	}

	/**
	 * @Route("/typeahead/input-tools.json", defaults={"_format" = "json"}, name="core_search_typeahead_input_tools_json")
	 * @Template()
	 */
	public function searchTypeaheadInputToolsAction(Request $request, $page = 0) {
		$searchUtils = $this->get(SearchUtils::NAME);

		$searchParameters = $searchUtils->searchPaginedEntities(
			$request,
			$page,
			function($facet, &$filters, &$sort) {
				$filters[] = new \Elastica\Query\Match('label', $facet->value);
			},
			null,
			'fos_elastica.index.ladb.input_tool',
			\Ladb\CoreBundle\Entity\Input\Tool::CLASS_NAME,
			null
		);

		$parameters = array_merge($searchParameters,  array(
			'tools'  => $searchParameters['entities'],
		));
		return $parameters;
	}

	/**
	 * @Route("/typeahead/input-finishes.json", defaults={"_format" = "json"}, name="core_search_typeahead_input_finishes_json")
	 * @Template()
	 */
	public function searchTypeaheadInputFinishesAction(Request $request, $page = 0) {
		$searchUtils = $this->get(SearchUtils::NAME);

		$searchParameters = $searchUtils->searchPaginedEntities(
			$request,
			$page,
			function($facet, &$filters, &$sort) {
				$filters[] = new \Elastica\Query\Match('label', $facet->value);
			},
			null,
			'fos_elastica.index.ladb.input_finish',
			\Ladb\CoreBundle\Entity\Input\Finish::CLASS_NAME,
			null
		);

		$parameters = array_merge($searchParameters,  array(
			'finishes'  => $searchParameters['entities'],
		));
		return $parameters;
	}

}
