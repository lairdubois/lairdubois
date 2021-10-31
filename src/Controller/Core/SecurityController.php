<?php

namespace App\Controller\Core;

use App\Controller\AbstractController;
use App\Entity\Core\User;
use App\Form\Type\Security\RegisterType;
use App\Manager\Core\UserManager;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Symfony\Component\Routing\Annotation\Route;

class SecurityController extends AbstractController  {

    /**
     * @Route("/login", name="_security_login")
     */
    public function login(AuthenticationUtils $authenticationUtils) {

        // get the login error if there is one
        $error = $authenticationUtils->getLastAuthenticationError();
        // last username entered by the user
        $lastUsername = $authenticationUtils->getLastUsername();

        return $this->render('Security/Security/login.html.twig', array(
            'last_username' => $lastUsername,
            'error' => $error
        ));
    }

    /**
     * @Route("/logout", name="_security_logout")
     */
    public function logout() {
        throw new \LogicException('This method can be blank - it will be intercepted by the logout key on your firewall.');
    }

    /**
     * @Route("/register", name="_security_register")
     */
    public function register(Request $request, UserManager $userManager) {

        $user = new User();
        $form = $this->createForm(RegisterType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $userManager->createFromEntity($user);

            return $this->render('Security/Registration/confirmed.html.twig', array(
                'user' => $user,
            ));

        }

        return $this->render('Security/Registration/register.html.twig', array(
            'form' => $form->createView(),
        ));
    }


    /**
     * @Route("/fos_user_resetting_request", name="fos_user_resetting_request")
     */
    public function fos_user_resetting_request() {
        throw new \LogicException('TODO fos_user_resetting_request.');
    }


    /**
     * @Route("/fos_user_change_password", name="fos_user_change_password")
     */
    public function fos_user_change_password() {
        throw new \LogicException('TODO fos_user_change_password.');
    }

}