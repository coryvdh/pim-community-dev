<?php

namespace Pim\Bundle\FilterBundle\Filter\Flexible;

use Doctrine\Common\Persistence\ObjectRepository;
use Oro\Bundle\FilterBundle\Filter\ChoiceFilter as OroChoiceFilter;
use Oro\Bundle\FilterBundle\Datasource\FilterDatasourceAdapterInterface;
use Pim\Bundle\FlexibleEntityBundle\Entity\Attribute;
use Pim\Bundle\FlexibleEntityBundle\Entity\AttributeOption;
use Pim\Bundle\FlexibleEntityBundle\Manager\FlexibleManager;

/**
 * Flexible filter
 *
 * @author    Nicolas Dupont <nicolas@akeneo.com>
 * @copyright 2013 Akeneo SAS (http://www.akeneo.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class ChoiceFilter extends OroChoiceFilter
{
    /** @var array */
    protected $valueOptions;

    /**
     * {@inheritdoc}
     */
    public function apply(FilterDatasourceAdapterInterface $ds, $data)
    {
        $data = $this->parseData($data);
        if (!$data) {
            return false;
        }

        $operator = $this->getOperator($data['type']);

        $fen = $this->get(FilterUtility::FEN_KEY);
        $this->util->applyFlexibleFilter(
            $ds,
            $fen,
            $this->get(FilterUtility::DATA_NAME_KEY),
            $data['value'],
            $operator
        );

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function getForm()
    {
        $options = array_merge(
            $this->getOr('options', []),
            ['csrf_protection' => false]
        );

        $options['field_options']            = isset($options['field_options']) ? $options['field_options'] : [];
        $options['field_options']['choices'] = $this->getValueOptions();

        if (!$this->form) {
            $this->form = $this->formFactory->create($this->getFormType(), [], $options);
        }

        return $this->form;
    }

    /**
     * @return array
     * @throws \LogicException
     */
    protected function getValueOptions()
    {
        if (null === $this->valueOptions) {
            $filedName       = $this->get(FilterUtility::DATA_NAME_KEY);
            /** @var FlexibleManager $flexibleManager */
            $flexibleManager = $this->util->getFlexibleManager($this->get(FilterUtility::FEN_KEY));

            /** @var $attributeRepository ObjectRepository */
            $attributeRepository = $flexibleManager->getAttributeRepository();
            /** @var $attribute Attribute */
            $attribute = $attributeRepository->findOneBy(
                ['entityType' => $flexibleManager->getFlexibleName(), 'code' => $filedName]
            );
            if (!$attribute) {
                throw new \LogicException('There is no flexible attribute with name ' . $filedName . '.');
            }

            /** @var $optionsRepository ObjectRepository */
            $optionsRepository  = $flexibleManager->getAttributeOptionRepository();
            $options            = $optionsRepository->findAllForAttributeWithValues($attribute);
            $this->valueOptions = [];
            /** @var $option AttributeOption */
            foreach ($options as $option) {
                $optionValue = $option->getOptionValue();
                if ($optionValue) {
                    $this->valueOptions[$option->getId()] = $optionValue->getValue();
                } else {
                    $this->valueOptions[$option->getId()] = '[' . $option->getCode() . ']';
                }
            }
        }

        return $this->valueOptions;
    }
}
