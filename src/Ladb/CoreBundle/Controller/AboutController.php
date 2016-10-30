<?php

namespace Ladb\CoreBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

/**
 * @Route("/a-propos")
 */
class AboutController extends Controller {

	/**
	 * @Route("/", name="core_about")
	 * @Template()
	 */
	public function aboutAction() {
		$response = $this->forward('LadbCoreBundle:About:faq');
		return $response;
	}

	/**
	 * @Route("/faq.html", name="core_about_faq")
	 * @Template()
	 */
	public function faqAction() {
		return $this->redirect($this->generateUrl('core_faq_question_list'));
	}

	/**
	 * @Route("/mentions-legales.html", name="core_about_legals")
	 * @Template()
	 */
	public function legalsAction() {
		return array();
	}

	/**
	 * @Route("/credits.html", name="core_about_credits")
	 * @Template()
	 */
	public function creditsAction() {
		$credits = array(
			array( 'name' => 'Symfony', 'url' => 'http://www.symfony.com', 'labels' => array( 'PHP' ) ),
			array( 'name' => 'FOSUserBundle', 'url' => 'https://github.com/FriendsOfSymfony/FOSUserBundle', 'labels' => array( 'PHP' ) ),
			array( 'name' => 'FOSElasticaBundle', 'url' => 'https://github.com/FriendsOfSymfony/FOSElasticaBundle', 'labels' => array( 'PHP' ) ),
			array( 'name' => 'StofDoctrineExtensionsBundle', 'url' => 'https://github.com/stof/StofDoctrineExtensionsBundle', 'labels' => array( 'PHP' ) ),
			array( 'name' => 'LiipImagineBundle', 'url' => 'https://github.com/liip/LiipImagineBundle', 'labels' => array( 'PHP' ) ),

			array( 'name' => 'Geocoder-PHP', 'url' => 'http://geocoder-php.org/Geocoder/', 'labels' => array( 'PHP' ) ),
			array( 'name' => 'Addressing', 'url' => 'https://github.com/commerceguys/addressing', 'labels' => array( 'PHP' ) ),
			array( 'name' => 'Codebird-PHP', 'url' => 'http://www.jublo.net/projects/codebird/php', 'labels' => array( 'PHP' ) ),
			array( 'name' => 'Facebook PHP SDK', 'url' => 'https://github.com/facebook/php-graph-sdk', 'labels' => array( 'PHP' ) ),
			array( 'name' => 'Markdown', 'url' => 'http://markdown.cebe.cc/', 'labels' => array( 'PHP' ) ),
			array( 'name' => 'PHP Phantomjs', 'url' => 'https://github.com/jonnnnyw/php-phantomjs', 'labels' => array( 'PHP' ) ),
			array( 'name' => 'ImageOptimizer', 'url' => 'https://github.com/psliwa/image-optimizer', 'labels' => array( 'PHP' ) ),
			array( 'name' => 'EmailValidator', 'url' => 'https://github.com/egulias/EmailValidator', 'labels' => array( 'PHP' ) ),
			array( 'name' => 'Hashids', 'url' => 'http://hashids.org/php/', 'labels' => array( 'PHP' ) ),
			array( 'name' => 'Stripe PHP', 'url' => 'https://github.com/stripe/stripe-php', 'labels' => array( 'PHP' ) ),

			array( 'name' => 'Elasticsearch', 'url' => 'https://www.elastic.co', 'labels' => array( 'JAVA' ) ),

			array( 'name' => 'jQuery', 'url' => 'https://jquery.com/', 'labels' => array( 'JS' ) ),
			array( 'name' => 'jQuery UI', 'url' => 'http://jqueryui.com/', 'labels' => array( 'JS' ) ),
			array( 'name' => 'jquery-file-upload', 'url' => 'https://blueimp.github.io/jQuery-File-Upload/', 'labels' => array( 'JS' ) ),
			array( 'name' => 'jquery-autocomplete', 'url' => 'https://github.com/devbridge/jQuery-Autocomplete', 'labels' => array( 'JS' ) ),
			array( 'name' => 'jquery-autosize', 'url' => 'http://www.jacklmoore.com/autosize', 'labels' => array( 'JS' ) ),
			array( 'name' => 'jquery-cornerslider', 'url' => 'https://github.com/reshetech/cornerSlider', 'labels' => array( 'JS' ) ),
			array( 'name' => 'jquery-form', 'url' => 'https://github.com/malsup/form', 'labels' => array( 'JS' ) ),
			array( 'name' => 'jquery-jscroll', 'url' => 'http://jscroll.com/', 'labels' => array( 'JS' ) ),
			array( 'name' => 'jquery-scrollto', 'url' => 'https://github.com/flesler/jquery.scrollTo', 'labels' => array( 'JS' ) ),
			array( 'name' => 'jquery-tocify', 'url' => 'http://gregfranko.com/jquery.tocify.js/', 'labels' => array( 'JS' ) ),
			array( 'name' => 'jquery-ui-touch-punch', 'url' => 'https://github.com/furf/jquery-ui-touch-punch/issues', 'labels' => array( 'JS' ) ),
			array( 'name' => 'jquery-sticky', 'url' => 'http://stickyjs.com/', 'labels' => array( 'JS' ) ),
			array( 'name' => 'jquery-readmorejs', 'url' => 'http://jedfoster.com/Readmore.js/', 'labels' => array( 'JS' ) ),
			array( 'name' => 'jquery-payment', 'url' => 'https://github.com/stripe/jquery.payment', 'labels' => array( 'JS' ) ),

			array( 'name' => 'Bootstrap', 'url' => 'http://getbootstrap.com/', 'labels' => array( 'HTML', 'CSS', 'JS' ) ),
			array( 'name' => 'bootstrap-datetimepicker', 'url' => 'https://github.com/Eonasdan/bootstrap-datetimepicker', 'labels' => array( 'JS' ) ),
			array( 'name' => 'bootstrap-markdown', 'url' => 'http://github.com/toopay/bootstrap-markdown', 'labels' => array( 'JS' ) ),

			array( 'name' => 'UIKit', 'url' => 'http://getuikit.com/', 'labels' => array( 'HTML', 'CSS', 'JS' ) ),

			array( 'name' => 'masonry', 'url' => 'http://masonry.desandro.com', 'labels' => array( 'JS' ) ),
			array( 'name' => 'moment.js', 'url' => 'http://momentjs.com', 'labels' => array( 'JS' ) ),
			array( 'name' => 'blueimp-gallery', 'url' => 'https://github.com/blueimp/Gallery', 'labels' => array( 'HTML', 'CSS', 'JS' ) ),

			array( 'name' => 'EmojiOne', 'url' => 'http://emojione.com/', 'labels' => array( 'PHP', 'CSS', 'JS' ) ),

			array( 'name' => 'Leaflet', 'url' => 'http://leafletjs.com/', 'labels' => array( 'JS' ) ),

			array( 'name' => 'pngquant', 'url' => 'https://pngquant.org/', 'labels' => array( 'TOOL' ) ),
			array( 'name' => 'optipng', 'url' => 'http://optipng.sourceforge.net/', 'labels' => array( 'TOOL' ) ),
			array( 'name' => 'jpegoptim', 'url' => 'https://github.com/tjko/jpegoptim', 'labels' => array( 'TOOL' ) ),
		);

		return array(
			'credits' => $credits,
		);
	}

	// Backward compatibilities /////

	/**
	 * @Route("/blog", name="core_about_blog")
	 * @Route("/blog/", name="core_about_blog_slash")
	 * @Route("/blog/{filter}", requirements={"filter" = "[a-z-]+"}, name="core_about_blog_filter")
	 * @Template()
	 */
	public function blogListAction($filter = 'recent') {
		return $this->redirect($this->generateUrl('core_blog_post_list_filter', array( 'filter' => $filter )));
	}

	/**
	 * @Route("/blog/{id}.html", name="core_about_blog_post_show")
	 * @Template()
	 */
	public function blogShowAction($id) {
		return $this->redirect($this->generateUrl('core_blog_post_show', array( 'id' => $id )));
	}

}
