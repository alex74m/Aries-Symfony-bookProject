<?php

namespace AppBundle\DataFixtures;

use AppBundle\Entity\User;
use AppBundle\Entity\Book;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;

class AppFixtures extends Fixture
{
    
    private $userPasswordEncoderInterface;
    
    public function __construct(UserPasswordEncoderInterface $userPasswordEncoderInterface)
    {
        $this->userPasswordEncoderInterface = $userPasswordEncoderInterface;
    }
    
    public function load(ObjectManager $manager)
    {
        // create 15 users validated & 5 not validated
        for ($i = 0; $i < 20; $i++) {
            $user = new User();
            $user->setEmail("cdwdm-user-$i@yopmail.com")
                ->setFirstName("Prénom-usr-$i")
                ->setLastName("Nom-usr-$i")
                ->setPassword($this->userPasswordEncoderInterface->encodePassword($user, 'passpass'));
            if($i >= 15){
                $user->removeRole('ROLE_USER')->addRole('ROLE_USER_PENDING');
            }
            
            $manager->persist($user);
        }
        
        // create 2 admins
        for ($i = 0; $i < 2; $i++){
            $user = new User();
            $user->setEmail("cdwdm-admin-$i@yopmail.com")
                ->setFirstName("Prénom-adm-$i")
                ->setLastName("Nom-adm-$i")
                ->setPassword($this->userPasswordEncoderInterface->encodePassword($user, 'passpass'))
                ->addRole('ROLE_ADMIN');
            
            $manager->persist($user);
        }

        for ($i = 0; $i <= 8; $i++){
            $book = new Book();
            $book->setTitle('Saw'.$i);
            if ($i == 3) {
               $book->setAuthor(null);
            }else{
                $book->setAuthor('James Wan');
            }
           
            $manager->persist($book);
        }

        $manager->flush();
    }
}