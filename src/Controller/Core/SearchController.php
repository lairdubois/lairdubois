<?php

namespace App\Controller\Core;

use App\Controller\AbstractController;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use App\Utils\PaginatorUtils;
use App\Utils\SearchUtils;

/**
 * @Route("/rechercher")
 */
class SearchController extends AbstractController {

	/**
	 * @Route("/", name="core_search")
	 * @Route("/creations", name="core_search_creations")
	 */
	public function search(Request $request) {
		return $this->redirect($this->generateUrl('core_creation_list', array(
			'q'    => $request->get('q'),
		)));
	}

	/**
	 * @Route("/typeahead/users.json", defaults={"_format" = "json"}, name="core_search_typeahead_users_json")
	 * @Template("Core/Search/searchTypeaheadUsers.json.twig")
	 */
	public function searchTypeaheadUsers(Request $request, $page = 0) {
		$searchUtils = $this->get(SearchUtils::class);

		$family = $request->get('family');

		$searchParameters = $searchUtils->searchPaginedEntities(
			$request,
			$page,
			function($facet, &$filters, &$sort, &$noGlobalFilters, &$couldUseDefaultSort) use ($family) {
				$bool = new \Elastica\Query\BoolQuery();
				if (!is_null($family)) {
					switch ($family) {
						case 'users':
							$q0 = new \Elastica\Query\Term([ 'isTeam' => [ 'value' => false, 'boost' => 1.0 ] ]);
							$bool->addMust($q0);
							break;
						case 'teams':
							$q0 = new \Elastica\Query\Term([ 'isTeam' => [ 'value' => true, 'boost' => 1.0 ] ]);
							$bool->addMust($q0);
							break;
					}
				}

				$q1 = new \Elastica\Query\QueryString('*'.$facet->value.'*');
				$q1->setFields(array( 'displayname^10', 'username^5', 'fullname' ));
				$bool->addMust($q1);
				$q2 = new \Elastica\Query\SimpleQueryString($facet->value.'*', array( 'displayname^10', 'username^5', 'fullname' ));	// Starts with boost
				$bool->addShould($q2);
				$filters[] = $bool;
			},
			null,
			null,
			'core_user',
			\App\Entity\Core\User::CLASS_NAME,
			null
		);

		$parameters = array_merge($searchParameters,  array(
			'users'  => $searchParameters['entities'],
		));
		return $parameters;
	}

	/**
	 * @Route("/typeahead/tags.json", defaults={"_format" = "json"}, name="core_search_typeahead_tags_json")
	 * @Template("Core/Search/searchTypeaheadTags.json.twig")
	 */
	public function searchTypeaheadTags(Request $request, $page = 0) {
		$searchUtils = $this->get(SearchUtils::class);

		$searchParameters = $searchUtils->searchPaginedEntities(
			$request,
			$page,
			function($facet, &$filters, &$sort, &$noGlobalFilters, &$couldUseDefaultSort) {
				$filters[] = new \Elastica\Query\Match('label', $facet->value);
			},
			null,
			null,
			'core_tag',
			\App\Entity\Core\Tag::CLASS_NAME,
			null
		);

		$parameters = array_merge($searchParameters,  array(
			'tags'  => $searchParameters['entities'],
		));
		return $parameters;
	}

	/**
	 * @Route("/typeahead/input-skills.json", defaults={"_format" = "json"}, name="core_search_typeahead_input_skills_json")
	 * @Template("Core/Search/searchTypeaheadInputSkills.json.twig")
	 */
	public function searchTypeaheadInputSkills(Request $request, $page = 0) {
		$searchUtils = $this->get(SearchUtils::class);

		$searchParameters = $searchUtils->searchPaginedEntities(
			$request,
			$page,
			function($facet, &$filters, &$sort, &$noGlobalFilters, &$couldUseDefaultSort) {
				$filters[] = new \Elastica\Query\Match('label', $facet->value);
			},
			null,
			null,
			'input_skill',
			\App\Entity\Input\Skill::CLASS_NAME,
			null
		);

		$parameters = array_merge($searchParameters,  array(
			'skills'  => $searchParameters['entities'],
		));
		return $parameters;
	}

	/**
	 * @Route("/typeahead/input-woods.json", defaults={"_format" = "json"}, name="core_search_typeahead_input_woods_json")
	 * @Template("Core/Search/searchTypeaheadInputWoods.json.twig")
	 */
	public function searchTypeaheadInputWoods(Request $request, $page = 0) {
		$searchUtils = $this->get(SearchUtils::class);

		$searchParameters = $searchUtils->searchPaginedEntities(
			$request,
			$page,
			function($facet, &$filters, &$sort, &$noGlobalFilters, &$couldUseDefaultSort) {
				$filters[] = new \Elastica\Query\Match('label', $facet->value);
			},
			null,
			null,
			'input_wood',
			\App\Entity\Input\Wood::CLASS_NAME,
			null
		);

		$parameters = array_merge($searchParameters,  array(
			'woods'  => $searchParameters['entities'],
		));
		return $parameters;
	}

	/**
	 * @Route("/typeahead/input-tools.json", defaults={"_format" = "json"}, name="core_search_typeahead_input_tools_json")
	 * @Template("Core/Search/searchTypeaheadInputTools.json.twig")
	 */
	public function searchTypeaheadInputTools(Request $request, $page = 0) {
		$searchUtils = $this->get(SearchUtils::class);

		$searchParameters = $searchUtils->searchPaginedEntities(
			$request,
			$page,
			function($facet, &$filters, &$sort, &$noGlobalFilters, &$couldUseDefaultSort) {
				$filters[] = new \Elastica\Query\Match('label', $facet->value);
			},
			null,
			null,
			'input_tool',
			\App\Entity\Input\Tool::CLASS_NAME,
			null
		);

		$parameters = array_merge($searchParameters,  array(
			'tools'  => $searchParameters['entities'],
		));
		return $parameters;
	}

	/**
	 * @Route("/typeahead/input-finishes.json", defaults={"_format" = "json"}, name="core_search_typeahead_input_finishes_json")
	 * @Template("Core/Search/searchTypeaheadInputFinishes.json.twig")
	 */
	public function searchTypeaheadInputFinishes(Request $request, $page = 0) {
		$searchUtils = $this->get(SearchUtils::class);

		$searchParameters = $searchUtils->searchPaginedEntities(
			$request,
			$page,
			function($facet, &$filters, &$sort, &$noGlobalFilters, &$couldUseDefaultSort) {
				$filters[] = new \Elastica\Query\Match('label', $facet->value);
			},
			null,
			null,
			'input_finish',
			\App\Entity\Input\Finish::CLASS_NAME,
			null
		);

		$parameters = array_merge($searchParameters,  array(
			'finishes'  => $searchParameters['entities'],
		));
		return $parameters;
	}

	/**
	 * @Route("/typeahead/input-hardwares.json", defaults={"_format" = "json"}, name="core_search_typeahead_input_hardwares_json")
	 * @Template("Core/Search/searchTypeaheadInputHardwares.json.twig")
	 */
	public function searchTypeaheadInputHardwares(Request $request, $page = 0) {
		$searchUtils = $this->get(SearchUtils::class);

		$searchParameters = $searchUtils->searchPaginedEntities(
			$request,
			$page,
			function($facet, &$filters, &$sort, &$noGlobalFilters, &$couldUseDefaultSort) {
				$filters[] = new \Elastica\Query\Match('label', $facet->value);
			},
			null,
			null,
			'input_hardware',
			\App\Entity\Input\Hardware::CLASS_NAME,
			null
		);

		$parameters = array_merge($searchParameters,  array(
			'hardwares'  => $searchParameters['entities'],
		));
		return $parameters;
	}

	/**
	 * @Route("/typeahead/softwares.json", defaults={"_format" = "json"}, name="core_search_typeahead_softwares_json")
	 * @Template("Core/Search/searchTypeaheadSoftwares.json.twig")
	 */
	public function searchTypeaheadSoftware(Request $request, $page = 0) {
		$searchUtils = $this->get(SearchUtils::class);


		$searchParameters = $searchUtils->searchPaginedEntities(
			$request,
			$page,
			function($facet, &$filters, &$sort, &$noGlobalFilters, &$couldUseDefaultSort) {
				$bool = new \Elastica\Query\BoolQuery();
				$q1 = new \Elastica\Query\QueryString('*'.$facet->value.'*');
				$q1->setFields(array( 'name' ));
				$bool->addMust($q1);
				$q2 = new \Elastica\Query\SimpleQueryString($facet->value.'*', array( 'name' ));	// Starts with boost
				$bool->addShould($q2);
				$filters[] = $bool;
			},
			null,
			null,
			'knowledge_software',
			\App\Entity\Knowledge\Software::CLASS_NAME,
			null
		);

		$parameters = array_merge($searchParameters, array(
			'softwares' => $searchParameters['entities'],
		));
		return $parameters;
	}

}
