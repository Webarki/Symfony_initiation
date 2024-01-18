<?php

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;

class AvatarType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
        ->add('avatar', FileType::class, [
            'label' => 'Ajouter un avatar',

            // non mappé signifie que ce champ n'est associé à aucune propriété d'entité
            'mapped' => false,
            // les champs non mappés ne peuvent pas définir leur validation à l'aide des attributs de l'entité associée,
            // vous pouvez donc utiliser les classes de contraintes PHP
            'constraints' => [
                new File([
                    'maxSize' => '10240k',
                    'mimeTypes' => [
                        'image/png'
                    ],
                    'mimeTypesMessage' => 'Le fichier telecharger doit être de type PNG',
                ])
            ],
        ])
        ->add('Enregistrer', SubmitType::class, [
            'attr' => [
                'class' => 'btn btn-outline-primary'
            ]
        ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
        ]);
    }
}
