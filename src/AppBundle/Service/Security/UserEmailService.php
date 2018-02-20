<?php

namespace AppBundle\Service\Security;

use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use AppBundle\Entity\User;

/**
 * Une classe qui possède des méthodes liées à la sécurité des emails utilisateurs
 */
class UserEmailService
{
    
    private $mailer;
    private $mailerUser;
    private $urlGenerator;
    private $twig;
    
    public function __construct(\Swift_Mailer $mailer, \Twig_Environment $twig, UrlGeneratorInterface $urlGenerator, string $mailerUser)
    {
        $this->mailer = $mailer;
        $this->mailerUser = $mailerUser;
        $this->urlGenerator = $urlGenerator;
        $this->twig = $twig;
    }
    
    /**
     * This method is used to send an email to verify that the user email is correct
     * 
     * @param User $user
     * 
     * @return boolean
     */
    public function sendValidationEmail(User $user){
        if(!$user->getEmailTemp()){
            
            return false;
        }
        
        $message = (new \Swift_Message('Validation email'))
            ->setFrom($this->mailerUser)
            ->setTo($user->getEmailTemp())
            ->setBody(
            $this->twig->render(
                '@App/Security/Email/emailvalidation-email.html.twig', array('link' => $this->urlGenerator->generate('validateemail', ['emailToken' => $user->getEmailToken(), 'emailTemp' => $user->getEmailTemp()], UrlGeneratorInterface::ABSOLUTE_URL))
            ), 'text/html'
            )
        ;
        
        return (bool) $this->mailer->send($message);
    }
}
