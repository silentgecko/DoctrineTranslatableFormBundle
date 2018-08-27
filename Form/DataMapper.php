<?php
/**
 * Created by Asier Marqués <asiermarques@gmail.com>
 * Date: 17/5/16
 * Time: 20:58
 * Modified by René Welbers <info@wereco.de>
 * DateTime: 2018-08-27 16:12
 */

namespace Simettric\DoctrineTranslatableFormBundle\Form;

use Doctrine\ORM\EntityManager;
use Gedmo\Translatable\Entity\Repository\TranslationRepository;
use Gedmo\Translatable\TranslatableListener;
use Simettric\DoctrineTranslatableFormBundle\Interfaces\TranslatableFieldInterface;
use Symfony\Component\Form\Exception;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\PropertyAccess\PropertyAccess;

/**
 * Class DataMapper
 *
 * @package Simettric\DoctrineTranslatableFormBundle\Form
 */
class DataMapper implements DataMapperInterface
{

    /**
     * @var EntityManager
     */
    private $em;

    /**
     * @var TranslationRepository
     */
    private $repository;

    /**
     * @var FormBuilderInterface
     */
    private $builder;

    /**
     * @var array
     */
    private $translations = [];

    /**
     * @var array
     */
    private $locales = [];

    /**
     * @var
     */
    private $required_locale;

    /**
     * @var array
     */
    private $property_names = [];

    /**
     * DataMapper constructor.
     *
     * @param EntityManager              $entityManager
     * @param TranslationRepository|null $repository
     */
    public function __construct(EntityManager $entityManager, TranslationRepository $repository = null)
    {

        $this->em = $entityManager;

        if ( ! $repository) {
            $repository = 'Gedmo\Translatable\Entity\Translation';
        }

        $this->repository = $this->em->getRepository($repository);

    }

    /**
     * @param FormBuilderInterface $builderInterface
     */
    public function setBuilder(FormBuilderInterface $builderInterface)
    {
        $this->builder = $builderInterface;
    }

    /**
     * @param $locale
     */
    public function setRequiredLocale($locale)
    {
        $this->required_locale = $locale;
    }

    /**
     * @param array $locales
     */
    public function setLocales(array $locales)
    {
        $this->locales = $locales;
    }

    /**
     * @return array
     */
    public function getLocales()
    {
        return $this->locales;
    }

    /**
     * @param $entity
     *
     * @return array
     */
    public function getTranslations($entity)
    {

        if ( ! count($this->translations)) {
            $this->translations = $this->repository->findTranslations($entity);
        }

        return $this->translations;

    }

    /**
     * @param       $name
     * @param       $type
     * @param array $options
     *
     * @return DataMapper
     * @throws \Exception
     */
    public function add($name, $type, $options = [])
    {

        $this->property_names[] = $name;

        $field = $this->builder
            ->add($name, $type)
            ->get($name);

        if ( ! $field->getType()
                   ->getInnerType() instanceof TranslatableFieldInterface) {
            throw new \Exception("{$name} must implement TranslatableFieldInterface");
        }

        foreach ($this->locales as $iso) {

            $options = [
                "label"    => $iso,
                "attr"     => isset($options["attr"]) ? $options["attr"] : [],
                "required" => ($iso == $this->required_locale && ( ! isset($options["required"]) || $options["required"])),
            ];

            $field->add(
                $iso,
                get_class(
                    $field->getType()
                        ->getParent()
                        ->getInnerType()
                ),
                $options
            );

        }

        return $this;

    }

    /**
     * Maps properties of some data to a list of forms.
     *
     * @param mixed           $data  Structured data.
     * @param FormInterface[] $forms A list of {@link FormInterface} instances.
     *
     * @throws Exception\UnexpectedTypeException if the type of the data parameter is not supported.
     */
    public function mapDataToForms($data, $forms)
    {

        foreach ($forms as $form) {
            $this->translations = [];
            $translations       = $this->getTranslations($data);

            $methodName  = 'get' . ucfirst($form->getName());
            $defaultData = "";
            if (method_exists($data, $methodName)) {
                $defaultData = $data->{$methodName}();
            }

            if (false !== in_array($form->getName(), $this->property_names)) {
                $values = [];
                foreach ($this->getLocales() as $iso) {
                    if (isset($translations[$iso])) {
                        $values[$iso] = isset($translations[$iso][$form->getName()]) ? $translations[$iso][$form->getName()] : "";
                    }
                }

                if (isset($translations[$this->required_locale]) === false && $defaultData !== '') {
                    $values[$this->required_locale] = $defaultData;
                }

                $form->setData($values);
            } else {
                if (false === $form->getConfig()->getOption("mapped") || null === $form->getConfig()->getOption("mapped")) {
                    continue;
                }
                $accessor = PropertyAccess::createPropertyAccessor();
                $form->setData($accessor->getValue($data, $form->getName()));
            }
        }
    }

    /**
     * Maps the data of a list of forms into the properties of some data.
     *
     * @param FormInterface[] $forms A list of {@link FormInterface} instances.
     * @param mixed           $data  Structured data.
     *
     * @throws Exception\UnexpectedTypeException if the type of the data parameter is not supported.
     */
    public function mapFormsToData($forms, &$data)
    {
        /**
         * @var $form FormInterface
         */
        foreach ($forms as $form) {

            $entityInstance = $data;

            if (false !== in_array($form->getName(), $this->property_names)) {

                $meta     = $this->em->getClassMetadata(get_class($entityInstance));
                $listener = new TranslatableListener;
                $listener->loadMetadataForObjectClass($this->em, $meta);
                $config = $listener->getConfiguration($this->em, $meta->name);

                $translations = $form->getData();
                foreach ($this->getLocales() as $iso) {
                    if (isset($translations[$iso])) {
                        if (isset($config['translationClass'])) {
                            $t = $this->em->getRepository($config['translationClass'])
                                ->translate($entityInstance, $form->getName(), $iso, $translations[$iso]);
                            $this->em->persist($entityInstance);
                            $this->em->flush();
                        } else {
                            $this->repository->translate($entityInstance, $form->getName(), $iso, $translations[$iso]);
                        }
                    }
                }

            } else {

                if (false === $form->getConfig()->getOption("mapped") || null === $form->getConfig()->getOption("mapped")) {
                    continue;
                }

                $accessor = PropertyAccess::createPropertyAccessor();
                $accessor->setValue($entityInstance, $form->getName(), $form->getData());

            }

        }

    }

}
