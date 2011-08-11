<?php

class vlCollectionForm extends sfFormSymfony
{    
    public function __construct($options = array())
    {
        parent::__construct(array(), $options, null);        
    }
    
    public function setup()
    {
        $this->disableLocalCSRFProtection();
        $this->checkRequiredOptions();
        $this->initForms();
    }
    
    public function update($values)
    {
        $old_form_keys = array_keys($this->getEmbeddedForms());
        $new_form_keys = array_keys($values);
        
        $to_delete = $this->getIndexesToDelete($old_form_keys, $new_form_keys);
        $to_add = $this->getIndexesToAdd($old_form_keys, $new_form_keys);
        
        $this->processDelete($to_delete);
        $this->processAdd($to_add);        
    }
    
    protected function processAdd($to_add)  
    {
        foreach($to_add as $form_index)
        {
            $this->embedForm($form_index, $this->createForm());
        }
    }
    
    /**
     * Deletes forms that no longer exist and updates schemas
     */
    protected function processDelete($to_delete)
    {
        foreach($to_delete as $form_index)
        {
            $this->deleteForm($form_index);
        }
        
        $this->errorSchema = new sfValidatorErrorSchema($this->validatorSchema);
    }
    
    protected function deleteForm($form_index)
    {
        unset($this->embeddedForms[$form_index]);
        unset($this->validatorSchema[$form_index]);
        unset($this->widgetSchema[$form_index]);
        unset($this->defaults[$form_index]);
    }
    
    /**
     * Method resolves which forms shoud be deleted
     * @param type $old_form_keys
     * @param type $new_form_keys
     * @return type 
     */
    protected function getIndexesToDelete($old_form_keys, $new_form_keys)
    {
        return array_diff($old_form_keys, $new_form_keys);
    }
    
    /**
     * Method resolves which forms should be added
     * @param type $old_form_keys
     * @param type $new_form_keys
     * @return type 
     */
    protected function getIndexesToAdd($old_form_keys, $new_form_keys)
    {
        return array_diff($new_form_keys, $old_form_keys);
    }
        
    protected function initForms()
    {
        $form_index = 0;
        
        foreach($this->getOption('collection', array()) as $collection_element)
        {
            $this->embedForm($form_index++, $this->createForm($collection_element));
        }
        
        for($i = 0; $i < $this->getOption('empty_forms', $this->getDefaultOption('empty_forms')); $i++)
        {
            $this->embedForm($form_index++, $this->createForm());
        }
    }
    
    /**
     * Creates new form according to options
     * @param type $collection_element
     * @return sfForm 
     */
    protected function createForm($collection_element = null)
    {
        $form_class = $this->getOption('form_class');
        $form_options = $this->getOption('form_options', array());
        //initialization should work fine either for doctrine or non-doctrine form as null should do the trick
        return new $form_class($collection_element, $form_options);
    }
    
    protected function getDefaultOptions()
    {
        return array(
            'empty_forms' => 0
            );
    }
    
    protected function getDefaultOption($name)
    {
        $default_options = $this->getDefaultOptions();
        
        return $default_options[$name];
    }
    
    protected function checkRequiredOptions()
    {
        $diff = array_diff($this->getRequiredOptions(), array_keys($this->getOptions()));

        if(count($diff) > 0)
        {
            throw new Exception('Required options are missing: ['.implode(',', $diff).']');
        }
    }
    

    protected function getRequiredOptions()
    {
        return array('form_class');
    }
}