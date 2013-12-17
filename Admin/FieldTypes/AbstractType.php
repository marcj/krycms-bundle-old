<?php

namespace Kryn\CmsBundle\Admin\FieldTypes;

use Kryn\CmsBundle\Configuration\Configs;
use Kryn\CmsBundle\Configuration\Field;
use Kryn\CmsBundle\ORM\Builder\Builder;

abstract class AbstractType implements TypeInterface
{

    /**
     * @var \Kryn\CmsBundle\Configuration\Field
     */
    protected $fieldDefinition;

    /**
     * @var mixed
     */
    protected $value;

    /**
     * @var \Kryn\CmsBundle\Admin\Form\Form
     */
    protected $form;

    /**
     * @var string
     */
    protected $name;

    /**
     * @param string $name
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param \Kryn\CmsBundle\Admin\Form\Form $form
     */
    public function setForm($form)
    {
        $this->form = $form;
    }

    /**
     * @return \Kryn\CmsBundle\Admin\Form\Form
     */
    public function getForm()
    {
        return $this->form;
    }

    /**
     * @param mixed $value
     */
    public function setValue($value)
    {
        $this->value = $value;
    }

    /**
     * @return mixed
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * @param Field $field
     */
    public function setFieldDefinition(Field $field)
    {
        $this->fieldDefinition = $field;
    }

    /**
     * @return Field
     */
    public function getFieldDefinition()
    {
        return $this->fieldDefinition;
    }

    /**
     * @return array
     */
    public function validate()
    {
        $result = [];
        $values = $this->getValue();

        $required = $this->getFieldDefinition()->isRequired()
            || ($this->getFieldDefinition()->isPrimaryKey() && !$this->getFieldDefinition()->isAutoIncrement());

        if (!$required) {
            return [];
        }

        foreach ($this->getColumns() as $column) {
            $field = $this->getFieldDefinition();
            $errors = [];
            $value = @$values[$column->getName()];

            if ($field->isHidden()) {
                return $result;
            }

            if (($value === '' || $value === null)) {
                $errors[] = 'Value is empty, but required.';
            } else {
                if ($regex = $column->getRequiredRegex()) {
                    $valueString = (string)$value;
                    if (!preg_match('/' . addslashes($regex) . '/', $valueString)) {

                        if (ColumnDefinition::isInteger($column) || ColumnDefinition::isFloat($column) || ColumnDefinition::isBoolean($column)) {
                            $name = 'Integer';
                            if (ColumnDefinition::isFloat($column)) {
                                $name = 'Decimal';
                            }
                            if (ColumnDefinition::isBoolean($column)) {
                                $name = 'Boolean';
                            }
                            $errors[] = sprintf('Value is not a %s (%s)', $name, $regex);
                        } else {
                            $errors[] = sprintf('Value requires format %s', $regex);
                        }
                    }
                }
            }

            if ($errors) {
                $result[$column->getName()] = $errors;
            }
        }

        return $result;
    }
} 