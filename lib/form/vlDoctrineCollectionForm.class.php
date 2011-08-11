<?php

class vlDoctrineCollectionForm extends vlCollectionForm
{
    public function setup()
    {
        if( ($object = $this->getOption('object')) && ($relation = $this->getOption('relation')) )
        {
            $this->checkIsNew();
            
            $this->resolveCollectionAndFK($object, $relation);
            $this->resolveFormClass();
            $this->resolveModelName();
        }
        
        parent::setup();
    }
    
    public function update($values)
    {
        $this->checkIsNew();
        
        parent::update($values);
    }
    
    protected function checkIsNew()
    {
        if($this->getOption('object')->isNew() && $this->getOption('edit_only', true)) 
            throw new sfException(get_class($this)." supports forms only with existing objects.". 
                                                   " Set 'edit_only' option to false if you want to bypass this restriction, and provide".
                                                   " your own logic to handle new objects");
    }
    
    /**
     *
     * @return Doctrine_Collection
     */
    protected function getCollection()
    {
        return $this->getOption('collection');
    }
    
    protected function resolveCollectionAndFK(sfDoctrineRecord $object, $relation)
    {
        $relation_getter = "get$relation";
        $collection = $object->$relation_getter();
        
        $this->setOption('collection', $collection);
        $this->setOption('foreign_key', $object->getTable()->getRelation($relation)->getForeignFieldName());
    }
    
    protected function getRequiredOptions()
    {
        return array_merge(parent::getRequiredOptions(), array('object', 'relation'));
    }
    
    protected function getDefaultOptions()
    {
        return array_merge(parent::getDefaultOptions(), array(
            'adapt_forms' => true,
            ));
    }

    /**
     * Creates new form, sets foreign key field and adapts the form
     * @param type $collection_element
     * @return sfFormObject
     */
    protected function createForm($record = null)
    {
        $form = parent::createForm($record);
        $form->setDefault($this->getOption('foreign_key'), $this->getOption('object')->getId());
        
        if($this->getOption('adapt_forms', $this->getDefaultOption('adapt_forms'))) $this->adaptForm($form);
        
        return $form;
    }
    
    protected function deleteForm($form_index)
    {
        $form = $this->getEmbeddedForm($form_index);
        
        if(!$form->isNew())
        {
            $this->deleteObjectFromCollection($form->getObject());
        }
        
        parent::deleteForm($form_index);
    }
    
    protected function deleteObjectFromCollection($object)
    {
        $collection = $this->getCollection();
        
        $record_key = false;
        
        foreach($collection as $key => $elem)
        {
            if($object == $elem)
            {
                $record_key = $key;
                break;
            }
        }
        
        $collection->remove($record_key);
    }        
    
    /**
     * It makes field ralated to parent object a hidden field
     * @param sfFormDoctrine $form 
     */
    protected function adaptForm(sfFormDoctrine $form)
    {        
        $form->widgetSchema[$this->getOption('foreign_key')] = new sfWidgetFormInputHidden();
        $form->validatorSchema[$this->getOption('foreign_key')] = new sfValidatorPass();
    }
    
    protected function resolveModelName()
    {
        if( ($collection = $this->getCollection()) )
        {
            $this->setOption('model', $collection->getTable()->getComponentName());
        }
        else
        {
            throw new Exception("Model can't be resolved, provide more options");
        }
    }
    
    protected function resolveFormClass()
    {
        if($this->getOption('form_class'))
        {
            //ok, leave set form_class
        }
        else if( ($collection = $this->getCollection()) )
        {
            //resolve form_class from collection
            $form_name = $collection->getTable()->getComponentName().'Form';
            $this->setOption('form_class', $form_name);
        }
        else
        {
            throw new Exception("Form class can't be resolved, provide more options");
        }
    }
}