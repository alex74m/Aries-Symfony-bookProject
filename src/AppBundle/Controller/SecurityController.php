<?php
namespace AppBundle\Controller;

use AppBundle\Service\Security\UserEmailService;
use AppBundle\Form\RegisterUserType;
use AppBundle\Form\ResetPasswordType;
use AppBundle\Entity\User;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;

class SecurityController extends Controller
{

    const LOST_PASSWORD_VALIDITY_TIME = 1; // en heures

    /**
     * Use this function to authenticate a User "manually"
     * 
     * @param User $user
     */

    private function authenticateUser(User $user)
    {
        $token = new UsernamePasswordToken($user, null, 'private', $user->getRoles());

        $this->get('security.token_storage')->setToken($token);
    }

    /**
     * Permet d'enregistrer un nouvel utilisateur
     * 
     * @Route("/register", name="register")     
     * 
     * @param Request $request
     * @param UserPasswordEncoderInterface $passwordEncoder
     * @param UserEmailService $userEmailService
     * 
     * @return Response
     */
    public function registerAction(Request $request, UserPasswordEncoderInterface $passwordEncoder, UserEmailService $userEmailService)
    {
        // 1) Construire le form User
        $user = new User();
        $registerForm = $this->createForm(RegisterUserType::class, $user);

        // 2) Hydrater l'objet User
        $registerForm->handleRequest($request);
        if ($registerForm->isSubmitted() && $registerForm->isValid()) {

            // 3) Encoder le mdp
            $password = $passwordEncoder->encodePassword($user, $user->getPlainPassword());
            $user->setPassword($password);

            // 4) Faut-il valider l'email ? (boolean dans le fichier parameters.yml)
            $needEmailValidation = $this->getParameter('verify_email_after_registration');
            if ($needEmailValidation) {
                $emailToken = md5(uniqid());
                $user->setRoles(array('ROLE_USER_PENDING'))->setEmailTemp($user->getEmail())->setEmailToken($emailToken);
                $userEmailService->sendValidationEmail($user);
            }

            // 5) Sauvegarder l'utilisateur
            $em = $this->getDoctrine()->getManager();
            $em->persist($user);
            $em->flush();

            // 6) On authentifie l'utilisateur directement
            // afin de lui éviter de saisir à nouveau ses identifiants
            $this->authenticateUser($user);

            return $this->redirectToRoute('dashboard');
        }

        return $this->render(
                '@App/Security/Register/register.html.twig', array('registerForm' => $registerForm->createView())
        );
    }

    /**
     * Permet de se connecter
     * 
     * @Route("/login", name="login")
     * 
     * @param Request $request
     * @param AuthenticationUtils $authUtils
     * 
     * @return Response
     */
    public function loginAction(Request $request, AuthenticationUtils $authUtils)
    {
        // get the login error if there is one
        $error = $authUtils->getLastAuthenticationError();

        // last username entered by the user
        $lastUsername = $authUtils->getLastUsername();

        return $this->render('@App/Security/Login/login.html.twig', array(
                'last_username' => $lastUsername,
                'error' => $error,
        ));
    }

    /**
     * Url de deconnexion
     * 
     * @Route("/logout", name="logout")
     */
    public function logoutAction()
    {
        throw new \RuntimeException('You must activate the logout in your security firewall configuration.');
    }

    /**
     * Affiche un formulaire pour redéfinir son MDP, et envoie un email de redéfinition du mdp
     * 
     * @Route("/lostpassword", name="lostpassword")
     * 
     * @param Request $request
     * 
     * @return Response     
     */
    public function sendLostPasswordMailAction(Request $request, \Swift_Mailer $mailer)
    {
        $userMail = $request->get('email');
        $responseParams = [];
        if ($userMail) {
            $userRepository = $this->getDoctrine()->getRepository('AppBundle:User');
            /* @var $user User */
            $user = $userRepository->findOneByEmail($userMail);
            if ($user) {
                $resetToken = md5(uniqid());
                $user->setLostPasswordDate(new \DateTime())->setLostPasswordToken($resetToken);
                $this->getDoctrine()->getManager()->flush();
                $message = (new \Swift_Message('MDP Perdu'))
                    ->setFrom($this->getParameter('mailer_user'))
                    ->setTo($userMail)
                    ->setBody(
                    $this->renderView(
                        '@App/Security/Password/lostpassword-email.html.twig', array('link' => $this->generateUrl('resetpassword', ['lostPasswordToken' => $resetToken], UrlGeneratorInterface::ABSOLUTE_URL), 'validity' => self::LOST_PASSWORD_VALIDITY_TIME)
                    ), 'text/html'
                    )
                ;

                if ($mailer->send($message)) {
                    $this->addFlash(
                        "success", "Un email pour redéfinir votre mdp vous a été envoyé"
                    );
                } else {
                    $this->addFlash(
                        "danger", "Une erreur est survenue, merci d'essayer à nouveau"
                    );
                }
            } else {
                $this->addFlash(
                    "warning", "Email inconnu"
                );
            }
        }

        return $this->render('@App/Security/Password/lostpassword.html.twig', $responseParams);
    }

    /**
     * Redéfinir un mdp depuis le lien de redéfinition envoyé par mail
     * 
     * @Route("/resetpassword/{lostPasswordToken}", name="resetpassword")
     * 
     * @param Request $request
     * @param UserPasswordEncoderInterface $passwordEncoder      
     * @param User $user    
     * 
     * @return Response
     */
    public function resetPasswordAction(Request $request, UserPasswordEncoderInterface $passwordEncoder, User $user = null)
    {

        if ($user) {
            // Changer le MDP si form submitted
            $passwordForm = $this->createForm(ResetPasswordType::class, $user);
            // handle the submit (will only happen on POST)
            $passwordForm->handleRequest($request);
            if ($passwordForm->isSubmitted() && $passwordForm->isValid()) {

                // Encode the password (you could also do this via Doctrine listener)
                $password = $passwordEncoder->encodePassword($user, $user->getPlainPassword());
                $user->setPassword($password);

                // Reset lostPassword token & date
                $user->setLostPasswordToken(null)->setLostPasswordDate(null);

                // On en profite pour valider l'email de l'utilisateur au cas où ce n'était pas déjà fait
                $user->removeRole('ROLE_USER_PENDING')->addRole('ROLE_USER')->setEmailTemp(null)->setEmailToken(null);

                // save the User!
                $em = $this->getDoctrine()->getManager();
                $em->flush();
                $this->authenticateUser($user);

                $this->addFlash(
                    "success", "Mot de passe changé avec succès"
                );

                return $this->redirectToRoute('dashboard');
            }

            // Afficher le formulaire
            $validity = self::LOST_PASSWORD_VALIDITY_TIME;
            if ($user->getLostPasswordDate()->modify("+$validity hour") >= new \DateTime()) {

                return $this->render('@App/Security/Password/resetpassword.html.twig', array('passwordForm' => $passwordForm->createView()));
            } else {
                $this->addFlash(
                    "warning", "Le lien n'est plus valide"
                );
            }
        }

        return $this->redirectToRoute('lostpassword');
    }

    /**
     * Valide un email depuis le lien de validation
     * 
     * @Route("/validateemail/{emailTemp}/{emailToken}", name="validateemail")
     * 
     * @param Request $request
     * @param User $user
     * 
     * @return Response
     */
    public function validateEmailAction(Request $request, User $user = null)
    {

        if ($user) {
            $user->setEmail($user->getEmailTemp())
                ->setEmailToken(null)
                ->setEmailTemp(null)
                ->addRole('ROLE_USER')
                ->removeRole('ROLE_USER_PENDING');
            $this->getDoctrine()->getManager()->flush();
            // On authentifie l'utilisateur au cas où ce ne soit pas déjà fait
            $this->authenticateUser($user);
            $this->addFlash(
                "success", "Email validé avec succès"
            );

            return $this->redirectToRoute('dashboard');
        }

        $this->addFlash(
            "warning", "Cet email n'existe pas, ou le lien est expiré"
        );

        return $this->redirectToRoute('login');
    }

    /**
     * Afficher la page d'accès refusé (droits insufisants)
     * 
     * @Route("/private/accessdenied", name="accessdenied")
     * 
     * @param Request $request
     * @param UserEmailService $userEmailService
     * 
     * @return Response
     */
    public function accessDeniedAction(Request $request, UserEmailService $userEmailService)
    {

        if ($request->get('resendEmailValidation') == 1) {
            if ($userEmailService->sendValidationEmail($this->getUser())) {
                $this->addFlash(
                    "success", "Email envoyé avec succès"
                );
            } else {
                $this->addFlash(
                    "danger", "Une erreur est survenue, merci d'essayer à nouveau"
                );
            }
        }

        return $this->render('@App/Security/AccessDenied/accessdenied.html.twig');
    }
}
