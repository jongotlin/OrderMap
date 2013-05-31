<?php

namespace JGI\Bundle\AppBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

class DefaultController extends Controller
{
    /**
     * @Route("/")
     * @Template()
     */
    public function indexAction()
    {
        return [
            'orders' => $this->get('doctrine')
                ->getManager()
                ->getRepository('JGIAppBundle:Order')
                ->getForMap(),
        ];
    }
}
