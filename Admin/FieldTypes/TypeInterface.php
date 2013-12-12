<?php

namespace Kryn\CmsBundle\Admin\FieldTypes;

use Kryn\CmsBundle\Configuration\Field;

interface TypeInterface
{
    /**
     * @return string
     */
    public function getPhpDataType();

    /**
     * @return string
     */
    public function getSqlDataType();

    /**
     * @return string
     */
    public function getName();

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
     * @return mixed
     */
    public function getRequiredRegex();

    /**
     * Returns the field/s to select from the object model.
     *
     * @return array
     */
    public function getSelection();
}