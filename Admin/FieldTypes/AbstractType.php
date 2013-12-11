<?php

namespace Kryn\CmsBundle\Admin\FieldTypes;

use Kryn\CmsBundle\Configuration\Field;
use Kryn\CmsBundle\Model\Content;
use Kryn\CmsBundle\Core;

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


    public function validate()
    {
        $field = $this->getFieldDefinition();
        $result = [];

        if ($field->isHidden()) {
            return $result;
        }

        if ($field->getRequired() && ($field->getValue() === '' || $field->getValue() === '')) {
            $result[] = 'Value is empty, but required.';
        }

        if ($regex = $this->getRequiredRegex()) {
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
            return $this->getFieldDefinition()->isRequired() ? '.+' : '.*';

        } else if ($this->isInteger()) {
            return $this->getFieldDefinition()->isRequired() ? '[-+]?\d+' : '[-+]?\d*';

        } else if ($this->isFloat()) {
            return $this->getFieldDefinition()->isRequired() ? '[-+]?(\d*[.])?\d+' : '[-+]?(\d*[.])?\d*';

        } else if ($this->isBoolean()) {
            return $this->getFieldDefinition()->isRequired() ? 'false|true|1|0' : 'false|true|1|0|';
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
