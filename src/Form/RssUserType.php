<?php
declare(strict_types=1);

namespace App\Form;

use App\Entity\RssUser;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class RssUserType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('email', EmailType::class)
//            ->add(
//                'roles',
//                ChoiceType::class, [
//                    'choices' => [
//                        'ROLE_ADMIN',
//                        'ROLE_USER',
//                    ],
//                ]
//            )
            ->add('password', PasswordType::class);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => RssUser::class,
        ]);
    }
}
