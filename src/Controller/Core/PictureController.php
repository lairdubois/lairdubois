<?php

namespace App\Controller\Core;

use App\Controller\AbstractController;
use App\Controller\PublicationControllerTrait;
use App\Entity\Core\Member;
use App\Entity\Core\Picture;
use App\Form\Model\EditPicture;
use App\Form\Type\Core\EditPictureType;
use App\Handler\PictureUploadHandler;
use Imagine\Gd\Imagine;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

/**
 * @Route("/pictures")
 */
class PictureController extends AbstractController {

	use PublicationControllerTrait;

    public static function getSubscribedServices() {
        return array_merge(parent::getSubscribedServices(), array(
            '?'.PictureUploadHandler::class,
        ));
    }

	private function assertEditableGranted(Picture $picture, $context = '') {

		if ($this->get('security.authorization_checker')->isGranted('ROLE_ADMIN')) {
			$allowed = true;
		} else if ($picture->getUser()->getIsTeam() && !is_null($this->getUser())) {

			// Only members allowed
			$om = $this->getDoctrine()->getManager();
			$memberRepository = $om->getRepository(Member::class);
			$allowed = $memberRepository->existsByTeamAndUser($picture->getUser(), $this->getUser());

		} else {
			$allowed = $picture->getUser() == $this->getUser();
		}

		if (!$allowed) {
			throw $this->createNotFoundException('Not allowed ('.$context.')');
		}

		return true;
	}

	/////

	/**
	 * @Route("/{quality}/{postProcessor}/upload", requirements={"quality" = "ld|sd|hd", "postProcessor" = "none|square"}, name="core_picture_upload")
	 */
	public function upload(Request $request, $quality, $postProcessor) {

		$owner = $this->retrieveOwner($request);

		$uploadHandler = $this->get(PictureUploadHandler::class);
		$uploadHandler->handle($quality, $postProcessor, $owner);
		exit(0);
	}

	/**
	 * @Route("/{id}/edit", requirements={"id" = "\d+"}, name="core_picture_edit")
	 * @Template("Core/Picture/edit-xhr.html.twig")
	 */
	public function edit(Request $request, $id) {
		if (!$request->isXmlHttpRequest()) {
			throw $this->createNotFoundException('Only XML request allowed (core_picture_edit)');
		}

		$om = $this->getDoctrine()->getManager();
		$pictureRepository = $om->getRepository(Picture::class);

		$picture = $pictureRepository->findOneById($id);
		if (is_null($picture)) {
			throw $this->createNotFoundException('Unable to find Picture entity (id='.$id.').');
		}
		$this->assertEditableGranted($picture, 'core_picture_edit');

		$editPicture = new EditPicture();
		$editPicture->setLegend($picture->getLegend());
		$editPicture->setSourceUrl($picture->getSourceUrl());
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
	 * @Route("/{id}/update", requirements={"id" = "\d+"}, methods={"POST"}, name="core_picture_update")
	 * @Template("Core/Picture/update-error-xhr.html.twig")
	 */
	public function update(Request $request, $id) {
		if (!$request->isXmlHttpRequest()) {
			throw $this->createNotFoundException('Only XML request allowed (core_picture_update)');
		}

		$om = $this->getDoctrine()->getManager();
		$pictureRepository = $om->getRepository(Picture::class);

		$picture = $pictureRepository->findOneById($id);
		if (is_null($picture)) {
			throw $this->createNotFoundException('Unable to find Picture entity (id='.$id.').');
		}
		$this->assertEditableGranted($picture, 'core_picture_edit');

		$editPicture = new EditPicture();

		$form = $this->createForm(EditPictureType::class, $editPicture);
		$form->handleRequest($request);

		if ($form->isValid()) {

			// Legend & SourceUrl
			$picture->setLegend($editPicture->getLegend());
			$picture->setSourceUrl($editPicture->getSourceUrl());

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

			return $this->render('Core/Picture/update-success-xhr.html.twig', array(
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
