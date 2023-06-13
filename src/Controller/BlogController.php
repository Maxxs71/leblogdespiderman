<?php

namespace App\Controller;

use App\Entity\Article;
use App\Form\NewPublicationFormType;

use Doctrine\Persistence\ManagerRegistry;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

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
            return $this->redirectToRoute('blog_publication_view',[
                'slug' => $newArticle->getSlug(),
            ]);


        }


        return $this->render('blog/new_publication.html.twig',[
            'new_publication_form' => $form->createView(),
        ]);
    }

    /**
     * Controlleur de la page qui liste tous les articles
     */
    #[Route('/publication/liste/', name: 'publication_list')]
    public function publicationList(ManagerRegistry $doctrine, Request $request, PaginatorInterface $paginator): Response
    {

        $requestedPage = $request->query->getInt('page', 1);

            // TODO : verif pas négatif
        if($requestedPage < 1){
            throw new NotFoundHttpException();
        }

        $em = $doctrine->getManager();

        $query = $em->createQuery('SELECT a FROM App\Entity\Article a ORDER BY a.pblicationDate DESC');

        $articles = $paginator->paginate(
            $query,
            $requestedPage,
            10
        );


        return $this->render('blog/publication_list.html.twig', [
            'articles' => $articles,
        ]);
    }

    /**
     * Controleur de la page permettant de voir un article en détail
     */

    #[Route('/publication/{slug}/', name: 'publication_view')]
    public function publicationView(Article $article): Response
    {



        return $this->render('blog/publication_view.html.twig',[
            'article'=> $article,
        ]);
    }

    /**
     *  Controleur de la page admin servant a supprimer un article via son id passé dans l'URL
     *
     * Acces réservé aux administrateurs (ROLE_ADMIN)
     *
     */

    #[Route('/publication/suppression/{id}/', name: 'publication_delete', priority: 10)]
    #[IsGranted('ROLE_ADMIN')]
    public function publicationDelete(Article $article, ManagerRegistry $doctrine, Request $request): Response
    {

        // Verif si token csrf valide
        if(!$this->isCsrfTokenValid('blog_publication_delete_' . $article->getId(), $request->query->get('csrf_token'))){

            $this->addFlash('error', 'Token securite invalide, veuillez ré-essayer');
        } else {


        $em = $doctrine->getManager();
        $em->remove($article);
        $em->flush();

        $this->addFlash('succes', ' La publication a ete supprime avec success');
    }
        
        return $this->redirectToRoute(('blog_publication_list'));




    }


}
