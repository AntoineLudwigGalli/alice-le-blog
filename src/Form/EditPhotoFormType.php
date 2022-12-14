<?php

namespace App\Form;

use phpDocumentor\Reflection\Types\This;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\Validator\Constraints\NotBlank;

class EditPhotoFormType extends AbstractType {

    private $allowedMimeTypes = [
        'jpg' => 'image/jpeg',
        'png' => 'image/png',
        'gif' => 'image/gif',
    ];


    public function buildForm(FormBuilderInterface $builder, array $options): void {
        $builder
            ->add('photo', FileType::class, [
                'label' => 'Sélectionnez une nouvelle photo',
                'label_attr' => [
                    'style' => 'color: #19374d;'
                ],
                'attr' => [
                'accept' => implode(", ", $this->allowedMimeTypes),
                    ],
                'constraints' => [
                    new NotBlank([
                        'message' => 'Vous devez sélectionner un fichier !',
                    ]),
                    new File([
                        'maxSize' => '5M',
                        'maxSizeMessage' => 'Fichier trop volumineux ({{ size }} {{ suffix }}). La taille maximum autorisée est de {{ limit }} {{ suffix }}.',
                        'mimeTypes' => $this->allowedMimeTypes,
                        'mimeTypesMessage' => "Ce type de fichier {{ type }} n'est pas autorisé. Les types autorisés sont {{ types }}."
                        ]),
                    ],
                ])

            ->add('save', SubmitType::class, [
                'label' => 'Publier',
                'attr' => [
                    'class' => 'btn btn-outline-primary w-100',]]);
    }

    public function configureOptions(OptionsResolver $resolver): void {
        $resolver->setDefaults([// Configure your form options here
        ]);
    }
}
