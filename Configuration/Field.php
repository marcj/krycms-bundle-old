<?php

namespace Kryn\CmsBundle\Configuration;

use Kryn\CmsBundle\Tools;

class Field extends Model
{
    protected $attributes = ['id', 'type', 'primaryKey', 'autoIncrement'];
    protected $arrayKey = 'id';

    /**
     * @var string
     */
    protected $id;

    /**
     * The label.
     *
     * @var string
     */
    protected $label;

    /**
     * Shows a grayed description text. __Warning__: This value is set as HTML. So escape `<` and `>`.
     *
     * @var string
     */
    protected $desc;

    /**
     * @var string
     */
    protected $type;

    /**
     * @var mixed
     */
    protected $value;

    /**
     * @var string
     */
    protected $object;

    /**
     * One of
     *
     * \Core\ORM\ORMAbstract::
     *       MANY_TO_ONE = 'nTo1',
     *       ONE_TO_MANY = '1ToN',
     *       ONE_TO_ONE = '1To1',
     *       MANY_TO_MANY = 'nToM';
     *
     * @var string
     */
    protected $objectRelation;

    /**
     * @var string
     */
    protected $objectLabel;

    /**
     * @var string
     */
    protected $objectRelationName;

    /**
     * The virtualField name of the field in the foreign object.
     *
     * @var string
     */
    protected $objectRefRelationName;

    /**
     * onDelete cascade|setnull|restrict|none
     *
     * @var string
     */
    protected $objectRelationOnDelete = 'cascade';

    /**
     * onUpdate cascade|setnull|restrict|none
     *
     * @var string
     */
    protected $objectRelationOnUpdate = 'cascade';

    /**
     * The key of the field this is representing. Primarily for types 'predefined'.
     *
     * @var string
     */
    protected $field;

    /**
     * @var string
     */
    protected $layout;

    /**
     * @var string
     */
    protected $target;

    /**
     * @var mixed
     */
    protected $needValue;

    /**
     * @var string
     */
    protected $againstField;

    /**
     * The default/initial value.
     *
     * @var mixed
     */
    protected $default = null;

    /**
     * @var Field
     */
    private $parentField;

    /**
     * @var \Core\Form\Form;
     */
    private $form;

    /**
     * If this field starts with a empty value (on initialisation).
     *
     * @var bool
     */
    protected $startEmpty = false;

    /**
     * If this field returns the value even though it's the `default` value (in a form).
     *
     * @var bool
     */
    protected $returnDefault = false;

    /**
     * Defines if this field needs a valid value.
     *
     * @var bool
     */
    protected $required = false;

    /**
     * @var string
     */
    protected $requiredRegex;

    /**
     * If this field injects a `tr`+2x`td` instead of `div`.
     *
     * @var bool
     */
    protected $tableItem = false;

    /**
     * If this fields is disabled or not.
     *
     * @var bool
     */
    protected $disabled = false;

    /**
     * If this fields contains a default wrapper div with title, description etc or only the input itself.
     *
     * @var bool
     */
    protected $noWrapper = false;

    /**
     * Shows a little help icon and points to the given help id.
     *
     * @var string
     */
    protected $help;

    /**
     * Width of a column.
     *
     * @var integer|string
     */
    protected $width;

    /**
     * Width of the actual input element (input, select, textarea, etc)
     *
     * @var string|integer
     */
    protected $inputWidth;

    /**
     * Width of the panel where the input is placed.
     *
     * @var string|integer
     */
    protected $fieldWidth;

    /**
     * @var bool
     */
    protected $primaryKey = false;

    /**
     * @var bool
     */
    protected $autoIncrement = false;

    /**
     * @var array
     */
    protected $children;

    /**
     * @var Options
     */
    protected $options;

    /**
     * If this is a virtual field or not. Virtual fields a dummy fields
     * to keep the relation between object fields in sync.
     *
     * @var boolean
     */
    protected $virtual = false;

    /**
     * @param array  $values
     * @param string $key
     */
    public function fromArray($values, $key = null)
    {
        parent::fromArray($values, $key);
        if (is_string($key)) {
            $this->setId($key);
        }
    }

    /**
     * @param boolean $virtual
     */
    public function setVirtual($virtual)
    {
        $this->virtual = $virtual;
    }

    /**
     * @return boolean
     */
    public function getVirtual()
    {
        return $this->virtual;
    }

    /**
     * @return bool
     */
    public function isVirtual()
    {
        return true == $this->virtual;
    }


    /**
     * @param Options $options
     */
    public function setOptions(Options $options)
    {
        foreach ($options->getOptions() as $key => $option){
            $setter = 'set' . ucfirst($key);
            if (is_callable(array($this, $setter))) {
                $this->$setter($option);
                $options->removeOption($key);
            }
        }
        $this->options = $options;
    }

    /**
     * @param $key
     * @param $value
     */
    public function setOption($key, $value)
    {
        $this->options = $this->options ?: new Options();
        $this->options->setOption($key, $value);
    }

    /**
     * @return Options
     */
    public function getOptions()
    {
        return $this->options;
    }

    /**
     * @param string $key
     *
     * @return mixed
     */
    public function getOption($key)
    {
        return $this->options ? $this->options->getOption($key) : null;
    }


    /**
     * @param Field[] $children
     */
    public function setChildren(array $children = null)
    {
        $this->children = $children;
    }

    /**
     * @return Field[]
     */
    public function getChildren()
    {
        return $this->children;
    }

    public function getChildrenArray(){
        if (null !== $this->children) {
            $children = [];
            foreach ($this->children as $child) {
                $children[$child->getId()] = $child->toArray();
            }
            return $children;
        }
    }

    /**
     * @return bool
     */
    public function isPrimaryKey()
    {
        return true === $this->primaryKey;
    }

    /**
     * @return bool
     */
    public function isAutoIncrement()
    {
        return true === $this->autoIncrement;
    }

    /**
     * @param boolean $autoIncrement
     */
    public function setAutoIncrement($autoIncrement)
    {
        $this->autoIncrement = filter_var($autoIncrement, FILTER_VALIDATE_BOOLEAN);
    }

    /**
     * @return boolean
     */
    public function getAutoIncrement()
    {
        return $this->autoIncrement;
    }

    /**
     * @param string $desc
     */
    public function setDesc($desc)
    {
        $this->desc = $desc;
    }

    /**
     * @return string
     */
    public function getDesc()
    {
        return $this->desc;
    }

    /**
     * @param string $id
     */
    public function setId($id)
    {
        $this->id = $id;
    }

    /**
     * @return string
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Returns the column name for database access.
     *
     * @return string
     */
    public function getColumnName()
    {
        return Tools::camelcase2Underscore($this->getId());
    }

    /**
     * @param string $label
     */
    public function setLabel($label)
    {
        $this->label = $label;
    }

    /**
     * @return string
     */
    public function getLabel()
    {
        return $this->label;
    }

    /**
     * @param boolean $primaryKey
     */
    public function setPrimaryKey($primaryKey)
    {
        $this->primaryKey = filter_var($primaryKey, FILTER_VALIDATE_BOOLEAN);
    }

    /**
     * @return boolean
     */
    public function getPrimaryKey()
    {
        return $this->primaryKey;
    }

    /**
     * @param string $type
     */
    public function setType($type)
    {
        $this->type = $type;
    }

    /**
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param string $object
     */
    public function setObject($object)
    {
        $this->object = $object;
    }

    /**
     * @return string
     */
    public function getObject()
    {
        return $this->object;
    }

    /**
     * @param string $objectRelation
     */
    public function setObjectRelation($objectRelation)
    {
        $this->objectRelation = $objectRelation;
    }

    /**
     * @return string
     */
    public function getObjectRelation()
    {
        return $this->objectRelation;
    }

    /**
     * @param string $layout
     */
    public function setLayout($layout)
    {
        $this->layout = $layout;
    }

    /**
     * @return string
     */
    public function getLayout()
    {
        return $this->layout;
    }

    /**
     * @param string $target
     */
    public function setTarget($target)
    {
        $this->target = $target;
    }

    /**
     * @return string
     */
    public function getTarget()
    {
        return $this->target;
    }

    /**
     * @param mixed $tableItem
     */
    public function setTableItem($tableItem)
    {
        $this->tableItem = $tableItem;
    }

    /**
     * @return mixed
     */
    public function getTableItem()
    {
        return $this->tableItem;
    }

    /**
     * @param boolean $startEmpty
     */
    public function setStartEmpty($startEmpty)
    {
        $this->startEmpty = $startEmpty;
    }

    /**
     * @return boolean
     */
    public function getStartEmpty()
    {
        return $this->startEmpty;
    }

    /**
     * @param boolean $required
     */
    public function setRequired($required)
    {
        $this->required = $required;
    }

    /**
     * @return boolean
     */
    public function getRequired()
    {
        return $this->required;
    }

    /**
     * @param boolean $returnDefault
     */
    public function setReturnDefault($returnDefault)
    {
        $this->returnDefault = $returnDefault;
    }

    /**
     * @return boolean
     */
    public function getReturnDefault()
    {
        return $this->returnDefault;
    }

    /**
     * @param boolean $noWrapper
     */
    public function setNoWrapper($noWrapper)
    {
        $this->noWrapper = $noWrapper;
    }

    /**
     * @return boolean
     */
    public function getNoWrapper()
    {
        return $this->noWrapper;
    }

    /**
     * @param string $help
     */
    public function setHelp($help)
    {
        $this->help = $help;
    }

    /**
     * @return string
     */
    public function getHelp()
    {
        return $this->help;
    }

    /**
     * @param mixed $default
     */
    public function setDefault($default)
    {
        $this->default = $default;
    }

    /**
     * @return mixed
     */
    public function getDefault()
    {
        return $this->default;
    }

    /**
     * @param boolean $disabled
     */
    public function setDisabled($disabled)
    {
        $this->disabled = $disabled;
    }

    /**
     * @return boolean
     */
    public function getDisabled()
    {
        return $this->disabled;
    }

    /**
     * @param mixed $needValue
     */
    public function setNeedValue($needValue)
    {
        $this->needValue = $needValue;
    }

    /**
     * @return mixed
     */
    public function getNeedValue()
    {
        return $this->needValue;
    }

    /**
     * @param string $againstField
     */
    public function setAgainstField($againstField)
    {
        $this->againstField = $againstField;
    }

    /**
     * @return string
     */
    public function getAgainstField()
    {
        return $this->againstField;
    }

    /**
     * @param int|string $width
     */
    public function setWidth($width)
    {
        $this->width = $width;
    }

    /**
     * @return int|string
     */
    public function getWidth()
    {
        return $this->width;
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
     * @param string $requiredRegex
     */
    public function setRequiredRegex($requiredRegex)
    {
        $this->requiredRegex = $requiredRegex;
    }

    /**
     * @return string
     */
    public function getRequiredRegex()
    {
        return $this->requiredRegex;
    }

    /**
     * Hidden means here if the `needValue` is correct with the parent or `getAgainstField`.
     *
     * @return bool
     */
    public function isHidden()
    {
        if ($parentField = $this->getParentField()) {
            if ($parentField->isHidden()) {
                return true;
            }
        }

        if ($this->getNeedValue()) {
            if ($againstFieldName = $this->getAgainstField()) {
                if ($this->getForm()) {
                    $againstField = $this->getForm()->getField($againstFieldName);
                }
            } else {
                $againstField = $this->getParentField();
            }
            if ($againstField){
                if ($againstField->isHidden()) {
                    return true;
                }
                if (is_array($this->getNeedValue())) {
                    if (!in_array($againstField->getValue(), $this->getNeedValue())) {
                        return true;
                    }
                } else if ($againstField->getValue() != $this->getNeedValue()) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * @return bool
     */
    public function isValid()
    {
        $ok = true;

        if ($this->isHidden()) {
            return $ok;
        }

        if ($this->getRequired() && ($this->getValue() === '' || $this->getValue() === '' )){
            $ok = false;
        }

        if ($regex = $this->getRequiredRegex()) {
            $value = (string) $this->getValue();
            if (!preg_match('/'.addslashes($regex).'/', $value)) {
                $ok = false;
            }
        }

        return $ok;
    }

    /**
     * @param \Kryn\CmsBundle\Configuration\Field $parentField
     */
    public function setParentField($parentField)
    {
        $this->parentField = $parentField;
    }

    /**
     * @return \Kryn\CmsBundle\Configuration\Field
     */
    public function getParentField()
    {
        return $this->parentField;
    }

    /**
     * @param \Core\Form\Form $form
     */
    public function setForm($form)
    {
        $this->form = $form;
    }

    /**
     * @return \Core\Form\Form
     */
    public function getForm()
    {
        return $this->form;
    }

    /**
     * @param int|string $inputWidth
     */
    public function setInputWidth($inputWidth)
    {
        $this->inputWidth = $inputWidth;
    }

    /**
     * @return int|string
     */
    public function getInputWidth()
    {
        return $this->inputWidth;
    }

    /**
     * @param int|string $fieldWidth
     */
    public function setFieldWidth($fieldWidth)
    {
        $this->fieldWidth = $fieldWidth;
    }

    /**
     * @return int|string
     */
    public function getFieldWidth()
    {
        return $this->fieldWidth;
    }

    /**
     * @param string $field
     */
    public function setField($field)
    {
        $this->field = $field;
    }

    /**
     * @return string
     */
    public function getField()
    {
        return $this->field;
    }

    /**
     * @param string $objectRelationName
     */
    public function setObjectRelationName($objectRelationName)
    {
        $this->objectRelationName = $objectRelationName;
    }

    /**
     * @return string
     */
    public function getObjectRelationName()
    {
        return $this->objectRelationName;
    }

    /**
     * @param string $objectRefRelationName
     */
    public function setObjectRefRelationName($objectRefRelationName)
    {
        $this->objectRefRelationName = $objectRefRelationName;
    }

    /**
     * @return string
     */
    public function getObjectRefRelationName()
    {
        return $this->objectRefRelationName;
    }

    /**
     * @param string $objectLabel
     */
    public function setObjectLabel($objectLabel)
    {
        $this->objectLabel = $objectLabel;
    }

    /**
     * @return string
     */
    public function getObjectLabel()
    {
        return $this->objectLabel;
    }

    /**
     * @param string $objectRelationOnUpdate
     */
    public function setObjectRelationOnUpdate($objectRelationOnUpdate)
    {
        $this->objectRelationOnUpdate = $objectRelationOnUpdate;
    }

    /**
     * @return string
     */
    public function getObjectRelationOnUpdate()
    {
        return $this->objectRelationOnUpdate;
    }

    /**
     * @param string $objectRelationOnDelete
     */
    public function setObjectRelationOnDelete($objectRelationOnDelete)
    {
        $this->objectRelationOnDelete = $objectRelationOnDelete;
    }

    /**
     * @return string
     */
    public function getObjectRelationOnDelete()
    {
        return $this->objectRelationOnDelete;
    }

}