<?php
namespace AppBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
// Forms
use AppBundle\Form\ChangePasswordType;
use AppBundle\Form\ChangeEmailType;
use AppBundle\Form\UserInfoType;

// Services
use AppBundle\Service\Security\UserEmailService;

// Note : il serait tout à fait possible de fusionner les 3 Actions (userInfoAction, userPasswordAction, userEmailAction) en une seule
// Mais vu que je vous demande de faire le contenu de userInfoAction ;) J'ai divisé en 3
class UserController extends Controller
{

    /**
     * @Route("/private/user", name="user")
     */
    public function userAction()
    {
        return $this->render('@App/User/user.html.twig', array());
    }

    /**
     * Access for authenticated users
     * Get list of all user with pagination filter.
     * 
     * @Route("/private/user/infos", name="user_infos")
     *
     * @Method({"GET","POST"})
     *
     */
    public function userInfoAction(Request $request)
    {
        $sessionUser = $this->getUser();

        $userInfosForm = $this->createForm(UserInfoType::class, $sessionUser, array());
        $userInfosForm->handleRequest($request);

        if ($userInfosForm->isSubmitted() && $userInfosForm->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->flush();
            $this->addFlash("success", "Vos informations personnelles ont bien été modifiées.");
        }

        return $this->render('@App/User/user_infos.html.twig', array(
            'userInfosForm' => $userInfosForm->createView(),
        ));
    }

    /**
     * Changer le mdp de l'utilisateur
     * 
     * @Route("/private/user/password", name="userpassword")
     * 
     * @param Request $request
     * @param UserPasswordEncoderInterface $passwordEncoder
     * 
     * @return Response
     */
    public function userPasswordAction(Request $request, UserPasswordEncoderInterface $passwordEncoder)
    {
        $user = $this->getUser();
        $changePasswordForm = $this->createForm(ChangePasswordType::class, $user, array());
        $changePasswordForm->handleRequest($request);
        if ($changePasswordForm->isSubmitted() && $changePasswordForm->isValid()) {
            // Encode the password (you could also do this via Doctrine listener)
            $password = $passwordEncoder->encodePassword($user, $user->getPlainPassword());
            $user->setPassword($password);            
            // On sauvegarde l'utilisateur
            $this->getDoctrine()->getManager()->flush();
            $this->addFlash(
                "success", "Mot de passe changé avec succès"
            );
        }

        return $this->render('@App/User/userpassword.html.twig', array('changePasswordForm' => $changePasswordForm->createView()));
    }

    /**
     * Changer l'email de l'utilisateur
     * 
     * @Route("/private/user/email", name="useremail")
     * 
     * @param Request $request
     * @param UserEmailService $userEmailService
     * 
     * @return Response
     */
    public function userEmailAction(Request $request, UserEmailService $userEmailService)
    {
        $user = $this->getUser();
        $changeEmailForm = $this->createForm(ChangeEmailType::class, $user, array());
        $changeEmailForm->handleRequest($request);
        if ($changeEmailForm->isSubmitted() && $changeEmailForm->isValid()) {
            // On génère un token unique
            $emailToken = md5(uniqid());
            $user->setEmailToken($emailToken);
            // On sauvegarde l'utilisateur
            $this->getDoctrine()->getManager()->flush();
            // On lui envoie un email de validation
            $userEmailService->sendValidationEmail($user);
            $this->addFlash(
                "warning", "Un email de validation vous a été envoyé"
            );
        }
//        else{
//            dump($changeEmailForm->getErrors());exit;
//        }
        
        return $this->render('@App/User/useremail.html.twig', array('changeEmailForm' => $changeEmailForm->createView()));
    }
}
