<?php

namespace App\Controller;

use App\Entity\Article;
use App\Form\CommentFormType;
use App\Form\NewPublicationFormType;
use App\Entity\Comment;
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
    #[Route('/nouvelle-publication/', name: 'publication_new')]
    #[IsGranted('ROLE_ADMIN')]
    public function publicationNew(Request $request, ManagerRegistry $doctrine): Response
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


        return $this->render('blog/publication_new.html.twig',[
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
    public function publicationView(Article $article, Request $request, ManagerRegistry $doctrine): Response
    {
        // Si l'utilisateur n'est pas connecte, on appel la vue en bloquant la suite d chargement du controleur
        if(!$this->getUser()){
            return $this->render('blog/publication_view.html.twig',[
                'article'=> $article,
                ]);
        }

        $comment = new Comment();

        $form = $this->createForm(CommentFormType::class, $comment);

        $form->handleRequest($request);

        if($form->isSubmitted() && $form->isValid()){

            $comment
            ->setPublicationDate( new \DateTime())
            ->setAuthor($this->getUser())
                ->setArticle($article)
            ;

            $em = $doctrine->getManager();
            $em->persist($comment);
            $em->flush();

            unset($comment);
            unset($form);

            $comment = new Comment();
            $form = $this->createForm(CommentFormType::class, $comment);

            $this->addFlash('success', 'Votre commentaire a bien été ajoute');
        }

        return $this->render('blog/publication_view.html.twig',[
            'article'=> $article,
            'comment_create_form' => $form->createView(),
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


    /**
     * Controleur de la page admin servant a modifier un article existant via son id passé dans l'url
     *
     * Acces reserve aux administrateurs (ROLE_ADMIN)
     */

    #[Route('/publication/modifier/{id}/', name: 'publication_edit', priority: 10)]
    #[IsGranted('ROLE_ADMIN')]
    public function publicationEdit(Article $article, Request $request, ManagerRegistry $doctrine): Response
    {

        $form = $this->createForm(NewPublicationFormType::class, $article);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()){

            $em = $doctrine->getManager();

            $em->flush();

            $this->addFlash('success','Publication modifiée avec success');

            return $this->redirectToRoute('blog_publication_view',[
                'slug' => $article->getSlug(),
            ]);

        }

        return $this->render('blog/publication_edit.html.twig',[
            'edit_form' => $form->createView(),
        ]);
    }

    /**
     * Controleur de la page permettant aux admins de supprimer
     *
     * Acces reserve aux admins (ROLE_ADMIN)
     */

    #[Route('/commentaires/suppression/{id}/', name: 'comment_delete', priority: 10)]
    #[IsGranted('ROLE_ADMIN')]
    public function commentDelete(Comment $comment, Request $request, ManagerRegistry $doctrine): Response
    {

        if (!$this->isCsrfTokenValid('blog_comment_delete_' . $comment->getId(), $request->query->get('csrf_token'))){

            $this->addFlash('error', 'Token securite invalide, veuillez ré-essayer');

        }else{

            $em = $doctrine->getManager();
            $em->remove($comment);
            $em->flush();

            $this->addFlash('success', 'Le commentaire aété supprimé avec succès !');
        }

        return $this->redirectToRoute('blog_publication_view',[
            'slug' => $comment->getArticle()->getSlug(),
        ]);

    }

}
