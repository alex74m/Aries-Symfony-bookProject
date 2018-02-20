<?php

namespace AppBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

//use Doctrine\ORM\EntityNotFoundException;

use AppBundle\Entity\Book;
use AppBundle\Form\BookType;

class AdministrationController extends Controller
{
    
    /**
     * Page accessible seulement aux admins
     * 
     * @Route("/private/administration", name="administration")
     */
    public function administrationAction()
    {
        return $this->render('@App/Administration/administration.html.twig', array());
    }
    
    /**
     * Page accessible seulement aux admins, où sont listé tous les utilisateurs du site
     * Get list of all user with pagination filter.
     * 
     * @Route("/private/administration/users", name="administrationusers", requirements={"page":"\d+"})
     *
     * @Method({"GET"})
     * 
     */
    public function administrationUsersAction(Request $request, $page=1)
    {
        $em = $this->getDoctrine()->getManager();
        $listUsers = $em->getRepository('AppBundle:User')->findAll();

        if ($request->query->get('query') != null) {
            $query = trim($request->query->get('query'));
            $listUsers = $em->getRepository('AppBundle:User')->​findUserByFirstNameOrLastName($query);
        }

        $page = $request->query->getInt('page', 1);
        $nbPerPage = $this->getParameter('nbUserPerPage');
        $nbPages = ceil(count($listUsers) / $nbPerPage);

        $paginator  = $this->get('knp_paginator');
        $pagination = $paginator->paginate(
            $listUsers,
            $page,
            $nbPerPage
        );

        return $this->render('@App/Administration/users.html.twig', array(
            'listUsers' => $pagination,
            'page' => $page,
            'nbPages' => $nbPages
        ));
    }

    /**
     * Page accessible seulement aux admins, pour créer un livre
     * 
     * @Route("/private/administration/books", name="administrationbooks")
     * 
     * @Method({"GET","POST"})
     *
     */
    public function administrationBooksAction(Request $request)
    {
        $title = $request->request->get('app_book')['title'];
        $author = $request->request->get('app_book')['author'];
        
        $book = new Book();
        $book->setTitle($title);
        $book->setAuthor($author);

        $bookForm = $this->createForm(BookType::class, $book, array());
        $bookForm->handleRequest($request);

        if ($bookForm->isSubmitted() && $bookForm->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($book);
            $em->flush();
            $this->addFlash("success", "Le livre a bien été sauvegardé.");
        }

        return $this->render('@App/Administration/books.html.twig', array(
            'bookForm' => $bookForm->createView(),
        ));
    }    

}
