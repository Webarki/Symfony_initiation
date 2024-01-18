<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\AvatarType;
use App\Form\MessageType;
use App\Repository\MessageRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Validator\Constraints\File;

class UserController extends AbstractController
{

    #[Route('/dashboard', name: 'app_dashboard')]
    public function dashboard(UserRepository $userRepository): Response
    {
        return $this->render('user/index.html.twig');
    }

    #[Route('/users', name: 'app_user_dashboard')]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function userDashboard(UserRepository $userRepository): Response
    {
        if($this->isGranted('ROLE_ADMIN')){
        $users = $userRepository->findAll();

        return $this->render('user/dashboard_user.html.twig', [
            'users' => $users
        ]);
    }
    return $this->redirectToRoute('app_home');
    }

    #[Route('/messages', name: 'app_message_dashboard')]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function messageDashboard(MessageRepository $messageRepository): Response
    {
        if($this->isGranted('ROLE_ADMIN')){
        $messages = $messageRepository->findAll();

        return $this->render('user/dashboard_message.html.twig', [
            'messages' => $messages
        ]);
    }
    return $this->redirectToRoute('app_home');
    }

    #[Route('/messages/delete/{id}', name: 'app_message_delete')]
    public function messageDelete($id, MessageRepository $messageRepository, EntityManagerInterface $manager): Response
    {
        $message = $messageRepository->find($id);
        $manager->remove($message);
        $manager->flush();
        $data = 'message supprimer';
        return $this->json($data, 200);
    }

    #[Route('/user/show/{id}', name: 'app_user_show')]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function userShow($id, UserRepository $userRepository): Response
    {
        $userId = $this->getUser()->getId();
        if($userId == $id){
        $user = $userRepository->find($id);

        return $this->render('user/show.html.twig', [
            'user' => $user
        ]);
    }else if($this->isGranted('ROLE_ADMIN')){
        $user = $userRepository->find($id);

        return $this->render('user/show.html.twig', [
            'user' => $user
        ]);
}else    {
        return $this->redirectToRoute('app_user_show', ['id' => $userId]);
    }
    }

    #[Route('/user/pseudo/{id}', name: 'app_user_pseudo')]
    public function updatePseudo(Request $request, User $user, EntityManagerInterface $manager): Response
    {
        $form = $this->createFormBuilder()
        ->add('pseudo', TextType::class, [
            'attr' => [
                'placeholder' => 'Entrer votre pseudo'
            ]
        ])
        ->add('Enregistrer', SubmitType::class)
        ->getForm();

        $form->handleRequest($request);

        if($form->isSubmitted() && $form->isValid())
        {
            if(!empty($form->get('pseudo')->getData()))
            {
                $pseudo = $form->get('pseudo')->getData();
                $user->setPseudo($pseudo);
                $manager->persist($user);
                $manager->flush();
                $this->addFlash('success', 'Pseudo enregistrer');
            }

        }
        return $this->render('user/pseudo.html.twig', [
            'form' => $form
        ]);
    }

    /**
     * @return Response
     * Permet de mettre a jour un mot de passe
     */
    #[Route('/user/pass/update/{id}', name: 'app_user_pass_update')]
    public function updatePass($id, Request $request, User $user, UserRepository $userRepository, UserPasswordHasherInterface $encoder): Response
    {
        $isConnect = $this->getUser();
        if($isConnect){
            if($this->getUser()->getId() === intval($id)){
        $form = $this->createFormBuilder()
        ->add('oldpass', PasswordType::class, [
            'label' => 'Mot de passe actuel :'
        ])
        ->add('password', RepeatedType::class, [
            'type' => PasswordType::class,
            'invalid_message' => 'Le mot de passe ne correspond pas',
            'options' => ['attr' => ['class' => 'password-field']],
            'required' => true,
            'first_options'  => ['label' => 'Nouveau mot de passe'],
            'second_options' => ['label' => 'Confirmer votre nouveau mot de passe'],
        ])
        ->add('Modifier', SubmitType::class)
        ->getForm();
        $form->handleRequest($request);


        if($form->isSubmitted() && $form->isValid())
        {
            $currentPass = $form->get('oldpass')->getData(); 
            if($encoder->isPasswordValid($user, $currentPass)){
            $password = htmlspecialchars($encoder->hashPassword($user, $form->get('password')->getData()));
       
            $userRepository->upgradePassword($user, $password);

            $this->addFlash('success', 'Votre mot de passe à été modifier avec succes !');
        }else{
                $this->addFlash('error', 'Veuillez entrer le bon mot de passe actuel !');
            }
        }
        return $this->render('user/update.html.twig', [
            'form' => $form
        ]);
            }else{
                return $this->redirectToRoute('app_user_pass_update', ['id' => $this->getUser()->getId()]);
            }
    }else{
        return $this->redirectToRoute('app_home');

        }
        
    }

    #[Route('/user/avatar/{id}', name: 'app_user_avatar')]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function updateAvatar(Request $request, User $user, EntityManagerInterface $manager): Response
    {
        $form = $this->createForm(AvatarType::class);
         $form->handleRequest($request);

        if($form->isSubmitted() && $form->isValid())
        {
            $avatar = $form->get('avatar')->getData();
            if ($avatar) {
                $newFilename = 'uploads/avatar'.$this->getUser()->getId().'.png';
                try {
                    $avatar->move(
                        $this->getParameter('avatar_directory'),
                        $newFilename
                    );
                } catch (FileException $e) {
                    $this->addFlash('error', 'Une erreur est survenue :'. $e->getMessage());
                }

                // updates the 'brochureFilename' property to store the PDF file name
                // instead of its contents
                $user->setAvatar($newFilename);
                $manager->persist($user);
                $manager->flush();
                return $this->redirectToRoute('app_user_account', ['id' => $this->getUser()->getId()]);
            }
        }

        return $this->render('user/avatar.html.twig', [
            'form' => $form
        ]);
    }
    

    #[Route('/user/delete/{id}', name: 'app_user_delete')]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function deleteUserById($id, User $user, EntityManagerInterface $entityManager ): Response
    {
        if($this->isGranted('ROLE_ADMIN')){
            unlink($user->getAvatar());
            $entityManager->remove($user);
            $entityManager->flush();
            return $this->render('user/success_delete.html.twig');
        }else{
        //Supprime une session utilisateur
        $userId = $this->getUser()->getId();
        if($userId === intval($id)){
        unlink($user->getAvatar());
        $entityManager->remove($user);
        $entityManager->flush();
        //$this->container->get('security.token_storage')->setToken(null);
        $session = new Session();
        $session->invalidate();
        return $this->redirectToRoute('app_home');
        }
    }
    }

#[Route('/user/account/{id}', name:'app_user_account')]
#[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function userAccount(): Response
    {
        return $this->render('user/account.html.twig', []);
    }


    #[Route('/user/message/{id}', name: 'app_user_message')]
    public function messageUser(User $user, MessageRepository $messageRepository): Response
    {
        $messages = $messageRepository->findBy([
            'email' => $user->getEmail()
        ]);
        return $this->render('user/message.html.twig', [
            'messages' => $messages
        ]);
    }

    }

