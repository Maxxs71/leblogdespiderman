<?php

namespace App\Controller;

use App\Entity\Article;
use App\Form\NewPublicationFormType;

use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Prefixe de la route et du nom de toutes les pages de la partie blog du site
 */
#[Route('/blog', name: 'blog_')]
class BlogController extends AbstractController
{
    #[Route('/nouvelle-publication/', name: 'new_publication')]
    #[IsGranted('ROLE_ADMIN')]
    public function newPublication(Request $request, ManagerRegistry $doctrine): Response
    {

        // Création d'un nouvel article vide
        $newArticle = new Article();

        // Creation du formualire de creation d'articles, lie a l'article vide
        $form = $this->createForm(NewPublicationFormType::class, $newArticle);

        // Liaison des données POST au formulaire
        $form->handleRequest($request);

        // Si le formualire a bien été envoyé sans erreurs
        if($form->isSubmitted() && $form->isValid()){


            // On termine d'hydrater l'article
            $newArticle
                ->setPblicationDate(new \DateTime())
                ->setAuthor( $this->getUser() )

            ;

            // Sauvegarde en base de données grace au manager des entites
            $em = $doctrine->getManager();
            $em -> persist(($newArticle));
            $em->flush();


            // Message flash de succes
            $this->addFlash('success','Article publié avec succes ! ');

            // TODO : Penser a rediriger sur la page qui montre le nouvel article
            return $this->redirectToRoute('main_home');


        }


        return $this->render('blog/new_publication.html.twig',[
            'new_publication_form' => $form->createView(),
        ]);
    }
}
