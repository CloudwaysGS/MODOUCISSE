<?php
// src/Controller/ErrorController.php

namespace App\Controller;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class ErrorController extends AbstractController
{
    public function error404()
    {
        return $this->render('bundles/TwigBundle/Exception/404.html.twig', [
            'controller_name' => 'ErrorController',
        ]);
        /*throw new NotFoundHttpException('Cette page n\'existe pas.');*/
    }
    public function error403()
    {
        return $this->render('bundles/TwigBundle/Exception/403.html.twig', [
            'controller_name' => 'ErrorController',
        ]);
        /*throw new NotFoundHttpException('Cette page n\'existe pas.');*/
    }
}
