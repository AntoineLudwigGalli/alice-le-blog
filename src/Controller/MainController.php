<?php

namespace App\Controller;

use App\Entity\Article;
use App\Form\EditPhotoFormType;
use Doctrine\Persistence\ManagerRegistry;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('', name: 'main_')]
class MainController extends AbstractController
{
    /**
     * Contrôleur de la page d'accueil
     */
    #[Route('/', name: 'home')]
    public function home(ManagerRegistry $doctrine): Response
    {
        $articleRepo = $doctrine->getRepository(Article::class);
        $articles = $articleRepo->findBy(
            [],
            ['publicationDate' => 'DESC'],
            $this->getParameter("app.articles.last_article_number_on_home"),
        );


        return $this->render('main/home.html.twig', [
            'articles' => $articles,
        ]);
    }

    /**
     * Contrôleur de la page de profil
     * Accès réservé aux connectés
     */
    #[Route('/mon-profil/', name: 'profil')]
    #[isGranted('ROLE_MEMBER')]
    public function profil(): Response
    {


        return $this->render('main/profil.html.twig');
    }

    /**
     * Contrôleur de la page de modification de la photo de profil
     * Accès réservé aux connectés
     */

    #[Route('/editer-photo/', name: 'edit_photo')]
    #[isGranted('ROLE_MEMBER')]
    public function editPhoto(Request $request, ManagerRegistry $doctrine): Response
    {

        $form=$this->createForm(EditPhotoFormType::class);

        $form->handleRequest($request);


        if($form->isSubmitted() && $form->isValid()){
            $photo = $form->get('photo')->getData();

            if(
                $this->getUser()->getPhoto() != null &&
                file_exists($this->getParameter('app.user.photo.directory') . $this->getUser()->getPhoto() )
            ){
                unlink($this->getParameter('app.user.photo.directory') . $this->getUser()->getPhoto() );
            }


            /*Génération nom*/
            do{
                $newFileName = md5( random_bytes(100) ) . '.' . $photo->guessExtension();
            } while (file_exists($this->getParameter('app.user.photo.directory') .$newFileName));

            $this->getUser()->setPhoto($newFileName);

            $em = $doctrine->getManager();
            $em->flush();

            $photo -> move(
                $this->getParameter('app.user.photo.directory'),
                $newFileName,
            );

            $this->addFlash('success', 'Photo de profil modifiée avec succès');

            return $this->redirectToRoute('main_profil');
        }

        return $this->render('main/edit_photo.html.twig', [
            'form' => $form->createView(),
        ]);
    }
}
