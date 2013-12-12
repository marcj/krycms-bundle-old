<?php

namespace Kryn\CmsBundle\Admin\FieldTypes;

use Kryn\CmsBundle\Configuration\Field;

abstract class AbstractType implements TypeInterface
{
    /**
     * @var string
     */
    protected $phpDataType;

    /**
     * @var string
     */
    protected $sqlDataType;

    /**
     * @var string
     */
    protected $name;

    /**
     * @var \Kryn\CmsBundle\Configuration\Field
     */
    protected $field;

    /**
     * @var \Kryn\CmsBundle\Admin\Form\Form
     */
    protected $form;

    /**
     * @return array
     */
    public function getSelection()
    {
        return [$this->getFieldDefinition()->getId()];
    }

    /**
     * @return array
     */
    public function validate()
    {
        $field = $this->getFieldDefinition();
        $result = [];

        if ($field->isHidden()) {
            return $result;
        }

        $required = $this->getFieldDefinition()->isRequired()
            || ($this->getFieldDefinition()->isPrimaryKey() && !$this->getFieldDefinition()->isAutoIncrement());

        if ($required && ($field->getValue() === '' || $field->getValue() === '')) {
            $result[] = 'Value is empty, but required.';
        }

        if ($required && $regex = $this->getRequiredRegex()) {
            $value = (string)$field->getValue();
            if (!preg_match('/' . addslashes($regex) . '/', $value)) {

                if ($this->isInteger() || $this->isFloat() || $this->isBoolean()) {
                    $name = 'Integer';
                    if ($this->isFloat()) $name = 'Decimal';
                    if ($this->isBoolean()) $name = 'Boolean';
                    $result[] = sprintf('Value is not a %s (%s)', $name, $regex);
                } else {
                    $result[] = sprintf('Value requires format %s', $regex);
                }
            }
        }

        return $result;
    }

    /**
     * @return mixed|string
     */
    public function getRequiredRegex()
    {
        if ('string' === $this->getPhpDataType()) {
            return '.+';

        } else if ($this->isInteger()) {
            return '[-+]?\d+';

        } else if ($this->isFloat()) {
            return '[-+]?(\d*[.])?\d+';

        } else if ($this->isBoolean()) {
            return 'false|true|1|0';
        }
    }

    public function isInteger()
    {
        return 'integer' === $this->getPhpDataType();
    }

    public function isFloat()
    {
        return in_array($this->getPhpDataType(), ['float', 'double', 'real']);
    }

    public function isBoolean()
    {
        return in_array($this->getPhpDataType(), ['boolean', 'bool']);
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
     * @param Field $field
     */
    public function setFieldDefinition(Field $field)
    {
        $this->field = $field;
    }

    /**
     * @return Field
     */
    public function getFieldDefinition()
    {
        return $this->field;
    }

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
     * @param string $phpDataType
     */
    public function setPhpDataType($phpDataType)
    {
        $this->phpDataType = $phpDataType;
    }

    /**
     * @return string
     */
    public function getPhpDataType()
    {
        return $this->phpDataType;
    }

    /**
     * @param string $sqlDataType
     */
    public function setSqlDataType($sqlDataType)
    {
        $this->sqlDataType = $sqlDataType;
    }

    /**
     * @return string
     */
    public function getSqlDataType()
    {
        return $this->sqlDataType;
    }

}
