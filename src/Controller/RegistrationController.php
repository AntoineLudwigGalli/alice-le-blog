<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\RegistrationFormType;
use App\Recaptcha\RecaptchaValidator;
use App\Repository\UserRepository;
use App\Services\JWTService;
use App\Services\SendEmailService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use SymfonyCasts\Bundle\VerifyEmail\VerifyEmailHelperInterface;

class RegistrationController extends AbstractController {

    /*Contrôleur de la page d'inscription*/
    #[Route('/creer-un-compte/', name: 'app_register')]
    public function register(Request $request, UserPasswordHasherInterface $userPasswordHasher,
        EntityManagerInterface $entityManager, RecaptchaValidator $recaptcha, VerifyEmailHelperInterface $verifyEmailHelper, SendEmailService $mail, JWTService
                             $jwt): Response {
        if ($this->getUser()) {
            return $this->redirectToRoute('main_home');
        }
        $user = new User();
        $form = $this->createForm(RegistrationFormType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            /*Récupération de la valeur de $_POST[g-recaptcha-response] sinon null*/
            $captchaResponse = $request->request->get('g-recaptcha-response', null);

            /* Récupération de l'adresse IP du client*/
            $ip = $request->server->get('REMOTE_ADDR');

            /*Si le captcha n'est pas valide, on ajoute une erreur au formulaire*/
            if (!$recaptcha->verify($captchaResponse, $ip)) {
                $form->addError(new FormError('Veuillez remplir le captcha de sécurité'));
            }

            if ($form->isValid()) {

                // Hydratation du MDP avec le hashage du MDP venant du formulaire
                $user->setPassword($userPasswordHasher->hashPassword($user, $form->get('plainPassword')->getData()));

                $user->setRegistrationDate(new \DateTime());

                $entityManager->persist($user);
                $entityManager->flush();

                // On génère le JWT de l'utilisateur
                // On crée le Header
                $header = [ 'typ' => 'JWT', 'alg' => 'HS256' ];
                // On crée le Payload
                $payload = [ 'user_id' => $user->getId() ];

                // On génère le token
                $token = $jwt->generate($header, $payload, $this->getParameter('app.jwtsecret'));

                $mail->send( 'no-reply@alice-le-blog.fr', $user->getEmail(), 'Activation de votre compte sur le site Le blog d\'A.L.I.C.E.', 'register', compact('user', 'token' ) );

                $this->addFlash('success', 'cliquez sur le lien reçu par mail pour valider votre compte');

                return $this->redirectToRoute('app_login');
            }
        }

        return $this->render('registration/register.html.twig', ['registrationForm' => $form->createView(),]);
    }

    #[Route('/verif/{token}', name: 'verify_user')]
    public function verifyUser($token, JWTService $jwt, UserRepository $userRepository, EntityManagerInterface $em, SendEmailService $mail): Response {
        //On vérifie si le token est valide, n'a pas expiré et n'a pas été modifié
        if($jwt->isValid($token) && !$jwt->isExpired($token) && $jwt->check($token, $this->getParameter('app.jwtsecret'))){
            // On récupère le payload
            $payload = $jwt->getPayload($token);
            // On récupère le user du token
            $user = $userRepository->find($payload['user_id']);

            //On vérifie que l'utilisateur existe et n'a pas encore activé son compte
            if($user && !$user->isIsVerified()){
                if($user->isIsVerified()) {
                    $user->setRoles(["ROLE_MEMBER"]);
                }

                $user->setIsVerified(true);
                $user->setRoles(["ROLE_MEMBER"]);
                $em->flush($user);



                $this->addFlash('success', 'Utilisateur activé');
                return $this->redirectToRoute('main_profil');
            }
        }

        // Ici un problème se pose dans le token
        $this->addFlash('error', 'Le token est invalide ou a expiré');
        return $this->redirectToRoute('app_login');
    }
}
