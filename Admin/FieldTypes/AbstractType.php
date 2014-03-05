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

    public function mapValues(array &$data)
    {
        $data[$this->getFieldDefinition()->getId()] = $this->getValue();
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

        $field = $this->getFieldDefinition();

        if ($field->isHidden()) {
            return $result;
        }

        $columns = $this->getColumns();
        if (1 === count($columns)) {
            $this->validateColumn($values, $columns[0], $result);
        } else {
            foreach ($this->getColumns() as $column) {
                $this->validateColumn(@$values[$column->getName()], $column, $result);
            }
        }

        return $result;
    }

    /**
     * @param mixed $value
     * @param ColumnDefinition $column
     * @param array $result
     */
    protected function validateColumn($value, ColumnDefinition $column, array &$result)
    {
        $errors = [];

        if ($value === '' || $value === null) {
            $errors[] = 'Value is empty, but required.';
        } else {
            if ($regex = $column->getRequiredRegex()) {
                $valueString = (string)$value;

                if (!preg_match('/' . $regex . '/', $valueString)) {

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

	/**
	 * @return string
	 */
	public function getPhpDataType()
	{
		if (1 < count($this->getColumns())) {
			return 'array';
		}
		if ($this->getColumns()) {
			$first = $this->getColumns()[0];
			if (ColumnDefinition::isInteger($first)) {
				return 'integer';
			} else if (ColumnDefinition::isBoolean($first)) {
				return 'boolean';
			} else if (ColumnDefinition::isFloat($first)) {
				return 'float';
			} else {
				return 'string';
			}
		}
	}
}