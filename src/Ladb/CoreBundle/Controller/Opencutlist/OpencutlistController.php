<?php

namespace Ladb\CoreBundle\Controller\Opencutlist;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Ladb\CoreBundle\Controller\AbstractController;
use Ladb\CoreBundle\Entity\Opencutlist\Access;
use Ladb\CoreBundle\Utils\PaginatorUtils;

/**
 * @Route("/opencutlist")
 */
class OpencutlistController extends AbstractController {

    const BRANCHE_PROD = 'master';
    const BRANCHE_DEV = '5.0.0';

	private function _createAccess(Request $request, $env, $kind) {
		$om = $this->getDoctrine()->getManager();

		$oclVersion = $request->get('v');
		$oclBuild = $request->get('build');
		$oclLanguage = $request->get('language');
		$sketchupLocale = $request->get('locale');

		if (strlen($oclLanguage) != 2) {
			$oclLanguage = null;
		}
		if (strlen($sketchupLocale) > 5) {
			$sketchupLocale = null;
		}

		$access = new Access();
		$access->setKind($kind);
		$access->setEnv($env);
		$access->setClientIp4($request->getClientIp());
		$access->setClientUserAgent($request->server->get('HTTP_USER_AGENT'));
		$access->setClientOclVersion($oclVersion);
		$access->setClientOclBuild($oclBuild);
		$access->setClientOclLanguage($oclLanguage);
		$access->setClientSketchupLocale($sketchupLocale);

		$om->persist($access);
		$om->flush();

		return $access;
	}

	/////

	/**
	 * @Route("/", name="core_opencutlist_ew")
	 */
	public function ewAction() {
		$response = $this->redirect('https://extensions.sketchup.com/extension/00f0bf69-7a42-4295-9e1c-226080814e3e/open-cut-list');
		return $response;
	}

	/**
	 * @Route("/manifest", name="core_opencutlist_manifest")
	 * @Route("/manifest-{env}", requirements={"env" = "dev|prod"}, name="core_opencutlist_manifest_env")
	 */
	public function manifestAction(Request $request, $env = 'prod') {

		$access = $this->_createAccess($request, $env, Access::KIND_MANIFEST);

		$response = $this->redirect('https://raw.githubusercontent.com/lairdubois/lairdubois-opencutlist-sketchup-extension/'.($access->getIsEnvDev() ? self::BRANCHE_DEV : self::BRANCHE_PROD).'/dist/manifest'.($access->getIsEnvDev() ? '-dev' : '').'.json');
		return $response;
	}

	/**
	 * @Route("/download", name="core_opencutlist_download")
	 * @Route("/download-{env}", requirements={"env" = "dev|prod"}, name="core_opencutlist_download_env")
	 */
	public function downloadAction(Request $request, $env = 'prod') {

		$access = $this->_createAccess($request, $env, Access::KIND_DOWNLOAD);

		$response = $this->redirect('https://raw.githubusercontent.com/lairdubois/lairdubois-opencutlist-sketchup-extension/'.($access->getIsEnvDev() ? self::BRANCHE_DEV : self::BRANCHE_PROD).'/dist/ladb_opencutlist'.($access->getIsEnvDev() ? '-dev' : '').'.rbz');
		return $response;
	}

	/**
	 * @Route("/tutorials", name="core_opencutlist_tutorials")
	 * @Route("/tutorials-{env}", requirements={"env" = "dev|prod"}, name="core_opencutlist_tutorials_env")
	 */
	public function tutorialsAction(Request $request, $env = 'prod') {

		$access = $this->_createAccess($request, $env, Access::KIND_TUTORIALS);

		$response = $this->redirect('https://raw.githubusercontent.com/lairdubois/lairdubois-opencutlist-sketchup-extension/'.($access->getIsEnvDev() ? self::BRANCHE_DEV : self::BRANCHE_PROD).'/docs/json/tutorials.json');
		return $response;
	}

	/**
	 * @Route("/docs", name="core_opencutlist_docs")
	 * @Route("/docs-{env}", requirements={"env" = "dev|prod"}, name="core_opencutlist_docs_env")
	 */
	public function docsAction(Request $request, $env = 'prod') {

		$access = $this->_createAccess($request, $env, Access::KIND_DOCS);

		$path = '';
		switch ($request->get('page', '')) {
			case 'features.parts.export':
				$path = '/features/parts/export';
				break;
			case 'features.parts.report':
				$path = '/features/parts/report';
				break;
			case 'features.parts.parts-list.labels':
				$path = '/features/parts/parts-list/printing-labels';
				break;
		}

		return JsonResponse::fromJsonString('{ "url": "https://docs.opencutlist.org'.$path.'" }');
	}

	/**
	 * @Route("/stats", name="core_opencutlist_stats")
	 * @Route("/stats/{page}", requirements={"page" = "\d+"}, name="core_opencutlist_stats_page")
	 * @Template("LadbCoreBundle:Opencutlist:stats.html.twig")
	 * @Security("is_granted('ROLE_ADMIN') or is_granted('ROLE_OPENCUTLIST')", statusCode=404, message="Not allowed (core_opencutlist_stats)")
	 */
	public function statsAction(Request $request, $page = 0) {

		$env = $request->get('env', 'prod');
		$days = intval($request->get('days', '28'));
		$continent = $request->get('continent', null);
		$language = $request->get('language', null);
		$locale = $request->get('locale', null);

		$om = $this->getDoctrine()->getManager();
		$accessRepository = $om->getRepository(Access::CLASS_NAME);
		$paginatorUtils = $this->get(PaginatorUtils::NAME);

		$offset = $paginatorUtils->computePaginatorOffset($page);
		$limit = $paginatorUtils->computePaginatorLimit($page);
		$paginator = $accessRepository->findPagined($offset, $limit, $env, $days, $continent, $language, $locale);
		$pageUrls = $paginatorUtils->generatePrevAndNextPageUrl('core_opencutlist_stats_page', array( 'env' => $env, 'days' => $days, 'continent' => $continent, 'language' => $language, 'locale' => $locale ), $page, $paginator->count());

		$downloadsByDay = $accessRepository->countUniqueGroupByDay(Access::KIND_DOWNLOAD, $env, $days, $continent, $language, $locale);
		$manifestsByDay = $accessRepository->countUniqueGroupByDay(Access::KIND_MANIFEST, $env, $days, $continent, $language, $locale);
		$tutorialsByDay = $accessRepository->countUniqueGroupByDay(Access::KIND_TUTORIALS, $env, $days, $continent, $language, $locale);
		$docssByDay = $accessRepository->countUniqueGroupByDay(Access::KIND_DOCS, $env, $days, $continent, $language, $locale);

		$downloadsByCountryCode = $accessRepository->countUniqueGroupByCountryCode(Access::KIND_DOWNLOAD, $env, $days, $continent, $language, $locale);
		$downloadsByCountry = array();
		$downloadsCount = 0;
		foreach ($downloadsByCountryCode as $row) {
			$downloadsByCountry[] = array(
				'count' => $row['count'],
				'countryCode' => $row['countryCode'],
				'country' => \Locale::getDisplayRegion('-'.$row['countryCode'], 'fr'),
			);
			$downloadsCount += $row['count'];
		}

		$manifestsByCountryCode = $accessRepository->countUniqueGroupByCountryCode(Access::KIND_MANIFEST, $env, $days, $continent, $language, $locale);
		$manifestsByCountry = array();
		$manifestsCount = 0;
		foreach ($manifestsByCountryCode as $row) {
			$manifestsByCountry[] = array(
				'count' => $row['count'],
				'countryCode' => $row['countryCode'],
				'country' => \Locale::getDisplayRegion('-'.$row['countryCode'], 'fr'),
			);
			$manifestsCount += $row['count'];
		}

		$tutorialsByCountryCode = $accessRepository->countUniqueGroupByCountryCode(Access::KIND_TUTORIALS, $env, $days, $continent, $language, $locale);
		$tutorialsByCountry = array();
		$tutorialsCount = 0;
		foreach ($tutorialsByCountryCode as $row) {
			$tutorialsByCountry[] = array(
				'count' => $row['count'],
				'countryCode' => $row['countryCode'],
				'country' => \Locale::getDisplayRegion('-'.$row['countryCode'], 'fr'),
			);
			$tutorialsCount += $row['count'];
		}

		$docsByCountryCode = $accessRepository->countUniqueGroupByCountryCode(Access::KIND_DOCS, $env, $days, $continent, $language, $locale);
		$docsByCountry = array();
		$docsCount = 0;
		foreach ($docsByCountryCode as $row) {
			$docsByCountry[] = array(
				'count' => $row['count'],
				'countryCode' => $row['countryCode'],
				'country' => \Locale::getDisplayRegion('-'.$row['countryCode'], 'fr'),
			);
			$docsCount += $row['count'];
		}

		$parameters = array(
			'env'                => $env,
			'days'               => $days,
			'continent'          => $continent,
			'language'           => $language,
			'locale'             => $locale,
			'prevPageUrl'        => $pageUrls->prev,
			'nextPageUrl'        => $pageUrls->next,
			'accesses'           => $paginator,
			'downloadsByDay'     => $downloadsByDay,
			'manifestsByDay'     => $manifestsByDay,
			'tutorialsByDay'     => $tutorialsByDay,
			'docsByDay'     	 => $docssByDay,
			'downloadsByCountry' => $downloadsByCountry,
			'manifestsByCountry' => $manifestsByCountry,
			'tutorialsByCountry' => $tutorialsByCountry,
			'docsByCountry' 	 => $docsByCountry,
			'downloadsCount'     => $downloadsCount,
			'manifestsCount'     => $manifestsCount,
			'tutorialsCount'     => $tutorialsCount,
			'docsCount'     	 => $docsCount,
		);

		if ($request->isXmlHttpRequest()) {
			return $this->render('LadbCoreBundle:Opencutlist:list-xhr.html.twig', $parameters);
		}

		return $parameters;
	}

}