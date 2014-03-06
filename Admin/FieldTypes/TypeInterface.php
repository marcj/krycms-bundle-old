<?php

namespace Kryn\CmsBundle\Admin\FieldTypes;

use Kryn\CmsBundle\Configuration\Configs;
use Kryn\CmsBundle\Configuration\Field;
use Kryn\CmsBundle\Configuration\Object;
use Kryn\CmsBundle\ORM\Builder\Builder;

interface TypeInterface
{
    /**
     * @return string
     */
    public function getName();

    /**
     * Do whatever is needed to setup the build environment correctly.
     *
     * Add new objects with relations if a field is a n-to-n relation for example.
     * This changes won't be saved, but only be used for the model building/schema migration.
     *
     * Make sure that this method does only change stuff once, because we call it frequently, depends
     * if another boot has something changed.
     *
     * @param \Kryn\CmsBundle\Configuration\Object $object
     * @param Configs $configs
     *
     * @return bool true for the boot has changed something on a object/field and we need to call it on other fields again.
     */
    public function bootBuildTime(Object $object, Configs $configs);

    /**
     * Do whatever is needed to setup the runtime environment correctly.
     * This changes are also used in the model buildTime.
     *
     * e.g. create cross foreignKeys for 1-to-n relations.
     *
     * This changes will be saved.
     *
     * Make sure that this method does only change stuff once, because we call it frequently, depends
     * if another boot has something changed.
     *
     * @param \Kryn\CmsBundle\Configuration\Object $object
     * @param Configs $configs
     *
     * @return bool true for the boot has changed something on a object/field and we need to call it on other fields again.
     */
    public function bootRunTime(Object $object, Configs $configs);

    /**
     * @return Field
     */
    public function getFieldDefinition();

    /**
     * @param Field $field
     */
    public function setFieldDefinition(Field $field);

    /**
     * @return \Kryn\CmsBundle\Admin\Form\Form
     */
    public function getForm();

    /**
     * @return array
     */
    public function validate();

    /**
     * Returns all columns that are necessary to get this field working.
     *
     * @return ColumnDefinitionInterface[]
     */
    public function getColumns();

    /**
     * Returns the field names to select from the object model as array.
     *
     * @return string[]
     */
    public function getSelection();

    /**
     * @param mixed $value
     */
    public function setValue($value);

    /**
     * @return mixed
     */
    public function getValue();

    /**
     * Maps the internal value to $data.
     *
     * @param array $data
     */
    public function mapValues(array &$data);

	/**
	 * Returns the internal data type that is required for setValue or that is returned by getValue.
	 *
	 * Possible values: integer|float|string|array|bool
	 *
	 * @return string
	 */
	public function getPhpDataType();

    public function isDiffAllowed();

}