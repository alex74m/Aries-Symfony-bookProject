<?php

namespace AppBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;

use Symfony\Bridge\Doctrine\Form\Type\EntityType;

class UserInfoType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
        ->add('firstName', TextType::class, ['required'=>true])
        ->add('lastName', TextType::class, ['required'=>true])
        ->add('book', EntityType::class, array(
            'class' => 'AppBundle\Entity\Book',
            'choice_label' => function ($book) {
                if ($book->getAuthor() == null) {
                    return $book->getTitle();
                }else{
                    return $book->getTitle().' - '.$book->getAuthor();
                } 
            },
            'by_reference' => true,
            'multiple' => false,
        ))
        ->add('save', SubmitType::class, [
            'label' => 'Save my informations'
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'AppBundle\Entity\User'
        ));
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return 'app_infos_user';
    }


}
