<?php

namespace AppBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;

class DashboardController extends Controller
{
    /**
     * @Route("/private", name="dashboard")
     */
    public function dashboardAction()
    {
        return $this->render('@App/Dashboard/dashboard.html.twig', array());
    }

}
