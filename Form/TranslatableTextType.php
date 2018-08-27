<?php
/**
 * Created by Asier Marqués <asiermarques@gmail.com>
 * Date: 17/5/16
 * Time: 14:53
 */

namespace Simettric\DoctrineTranslatableFormBundle\Form;

use Simettric\DoctrineTranslatableFormBundle\Interfaces\TranslatableFieldInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Exception;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Class TranslatableTextType
 *
 * @package Simettric\DoctrineTranslatableFormBundle\Form
 */
class TranslatableTextType extends AbstractType implements TranslatableFieldInterface
{

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                "compound" => true,
            ]
        );
        $resolver->setRequired(["compound"]);
        $resolver->setAllowedValues("compound", true);

    }

    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return TextType::class;
    }

}