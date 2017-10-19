<?php

namespace Ladb\CoreBundle\Controller\Core;

use Imagine\Gd\Imagine;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Ladb\CoreBundle\Entity\Core\Picture;
use Ladb\CoreBundle\Form\Model\EditPicture;
use Ladb\CoreBundle\Handler\PictureUploadHandler;
use Ladb\CoreBundle\Form\Type\EditPictureType;

/**
 * @Route("/pictures")
 */
class PictureController extends Controller {

	/**
	 * @Route("/upload", name="core_picture_upload")
	 * @Template()
	 */
	public function uploadAction(Request $request) {
		$uploadHandler = $this->get(PictureUploadHandler::NAME);
		$uploadHandler->handle($request->get('post-processor'));
		exit(0);
	}

	/**
	 * @Route("/{id}/edit", requirements={"id" = "\d+"}, name="core_picture_edit")
	 * @Template("LadbCoreBundle:Core/Picture:edit-xhr.html.twig")
	 */
	public function editAction(Request $request, $id) {
		if (!$request->isXmlHttpRequest()) {
			throw $this->createNotFoundException('Only XML request allowed (core_picture_edit)');
		}

		$om = $this->getDoctrine()->getManager();
		$pictureRepository = $om->getRepository(Picture::CLASS_NAME);

		$picture = $pictureRepository->findOneById($id);
		if (is_null($picture)) {
			throw $this->createNotFoundException('Unable to find Picture entity (id='.$id.').');
		}
		if (!$this->get('security.authorization_checker')->isGranted('ROLE_ADMIN') && (is_null($picture->getUser()) || $picture->getUser()->getId() != $this->getUser()->getId())) {
			throw $this->createNotFoundException('Not allowed (core_picture_edit)');
		}

		$editPicture = new EditPicture();
		$editPicture->setLegend($picture->getLegend());
		$editPicture->setRotation($picture->getRotation());
		$editPicture->setCenterX100($picture->getCenterX100());
		$editPicture->setCenterY100($picture->getCenterY100());

		$form = $this->createForm(EditPictureType::class, $editPicture);

		return array(
			'picture'     => $picture,
			'form'        => $form->createView(),
			'formSection' => $request->get('formSection', 'pictures'),
			'sortable'    => $request->get('sortable', false),
		);
	}

	/**
	 * @Route("/{id}/update", requirements={"id" = "\d+"}, name="core_picture_update")
	 * @Method("POST")
	 * @Template("LadbCoreBundle:Core/Picture:update-error-xhr.html.twig")
	 */
	public function updateAction(Request $request, $id) {
		if (!$request->isXmlHttpRequest()) {
			throw $this->createNotFoundException('Only XML request allowed (core_picture_update)');
		}

		$om = $this->getDoctrine()->getManager();
		$pictureRepository = $om->getRepository(Picture::CLASS_NAME);

		$picture = $pictureRepository->findOneById($id);
		if (is_null($picture)) {
			throw $this->createNotFoundException('Unable to find Picture entity (id='.$id.').');
		}
		if (!$this->get('security.authorization_checker')->isGranted('ROLE_ADMIN') && (is_null($picture->getUser()) || $picture->getUser()->getId() != $this->getUser()->getId())) {
			throw $this->createNotFoundException('Not allowed (core_picture_update)');
		}

		$editPicture = new EditPicture();

		$form = $this->createForm(EditPictureType::class, $editPicture);
		$form->handleRequest($request);

		if ($form->isValid()) {

			// Legend
			$picture->setLegend($editPicture->getLegend());

			// Rotation
			$rotation = $editPicture->getRotation();
			if ($rotation != $picture->getRotation()) {

				if ($rotation == 0) {

					// Remove previously transformed image
					if (!$picture->isMaster()) {
						if ($filename = $picture->getAbsoluteTransformedPath()) {
							unlink($filename);
						}
					}

					list($width, $height) = getimagesize($picture->getAbsoluteMasterPath());

					$picture->setTransformedPath(null);
					$picture->setRotation($rotation);

				} else if ($rotation == 90 || $rotation == 180 || $rotation == 270) {

					// Instantiate Imagine
					$imagine = new Imagine();

					// Convert picture to image
					$image = $imagine->open($picture->getAbsoluteMasterPath());

					// Transform
					$image = $image->rotate($rotation);

					// Remove previously transformed image
					if (!$picture->isMaster()) {
						if ($filename = $picture->getAbsoluteTransformedPath()) {
							unlink($filename);
						}
					}

					$picture->setTransformedPath(sha1(uniqid(mt_rand(), true)).'.jpg');
					$picture->setRotation($rotation);

					// Save rotated image
					$image->save($picture->getAbsoluteTransformedPath(), array( 'format' => 'jpg' ));

				}

				list($width, $height) = getimagesize($picture->getAbsolutePath());

				$picture->setWidth($width);
				$picture->setHeight($height);
				$picture->setHeightRatio100($width > 0 ? $height / $width * 100 : 100);

			}

			// Center
			$picture->setCenterX100($editPicture->getCenterX100());
			$picture->setCenterY100($editPicture->getCenterY100());

			$om->flush();

			return $this->render('LadbCoreBundle:Core/Picture:update-success-xhr.html.twig', array(
				'picture'     => $picture,
				'formSection' => $request->get('formSection', 'pictures'),
				'sortable'    => $request->get('sortable', false),
			));
		}

		return array(
			'picture'     => $picture,
			'form'        => $form->createView(),
			'formSection' => $request->get('formSection', 'pictures'),
			'sortable'    => $request->get('sortable', false),
		);
	}

}
