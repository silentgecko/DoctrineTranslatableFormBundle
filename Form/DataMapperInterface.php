<?php
/**
 * Created by Asier Marqués <asiermarques@gmail.com>
 * Date: 17/5/16
 * Time: 21:07
 */

namespace Simettric\DoctrineTranslatableFormBundle\Form;

use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\DataMapperInterface as SymfonyDataMapperInterface;

/**
 * Interface DataMapperInterface
 *
 * @package Simettric\DoctrineTranslatableFormBundle\Form
 */
interface DataMapperInterface extends SymfonyDataMapperInterface
{

    /**
     * @param FormBuilderInterface $builderInterface
     *
     * @return mixed
     */
    public function setBuilder(FormBuilderInterface $builderInterface);

    /**
     * @param       $name
     * @param       $type
     * @param array $options
     *
     * @return mixed
     */
    public function add($name, $type, $options = []);

    /**
     * @param array $locales
     *
     * @return mixed
     */
    public function setLocales(array $locales);

    /**
     * @return mixed
     */
    public function getLocales();

    /**
     * @param $locale
     *
     * @return mixed
     */
    public function setRequiredLocale($locale);

} 