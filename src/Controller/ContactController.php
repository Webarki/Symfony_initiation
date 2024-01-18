<?php

namespace App\Controller;

use App\Entity\Message;
use App\Form\MessageType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ContactController extends AbstractController
{
    #[Route('/contact', name: 'app_contact')]
    public function index(Request $request, EntityManagerInterface $manager): Response
    {
        if($this->getUser())
        {
        $form = $this->createFormBuilder()
        ->add('sujet', TextType::class)
        ->add('content', TextareaType::class)
        ->add('Enregistrer', SubmitType::class)
        ->getForm();
        }else{
        $form = $this->createForm(MessageType::class);
        }
        $form->handleRequest($request);

        if($form->isSubmitted() && $form->isValid())
        {
            if($this->getUser()){
            $email = $this->getUser()->getUserIdentifier();
            }else{
            if(filter_var($form->get('email')->getData(), FILTER_VALIDATE_EMAIL))
            {
            $email = htmlspecialchars($form->get('email')->getData());
            }
        }

            $sujet = htmlspecialchars($form->get('sujet')->getData());

            $content = htmlspecialchars($form->get('content')->getData());

            $message = new Message();
            $message->setEmail($email)
            ->setSujet($sujet)
            ->setContent($content);

            $manager->persist($message);
            $manager->flush();

            return $this->redirectToRoute('app_contact_success');

        }
        
        return $this->render('contact/index.html.twig', [
            'form' => $form,
        ]);
    }

    #[Route('/contact/success', name: 'app_contact_success')]
    public function contactSuccess(): Response
    {
        return $this->render('contact/success.html.twig');
    }
}
