<?php
declare(strict_types=1);

namespace App\Form;

use App\Entity\Feed;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\UrlType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Url;

class FeedType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('feedUrl', UrlType::class, [
                'label'    => 'Feed url',
                'required' => true,
                'constraints' => [
                    new NotBlank(),
                    new Url(),
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class'                  => Feed::class,
            'widget_container_attributes' => 'field',
        ]);
    }
}
