<?php 
class View
{
    private $_charset = 'utf-8';
    private $_path = array();
    private $_data = array();
    
    private $_helpers = array();
    
    public function __construct()
    {
        $view = $this;
        $cs = $this->_charset;
        
        $this->addHelper("escape", function($text, $flags = ENT_COMPAT, $charset = null, $doubleEncode = true) use ($cs) {
            return htmlspecialchars($text, $flags, $charset ?: $cs, $doubleEncode);
        });
        
        $this->addHelper("partial", function($path, $data) use ($view) {
            $view = $view->cloneThis();
            return $view->render($path, $data);
        });
    }
     
    public function __set($key, $value)
    {
        $this->_data[$key] = $value;
    }
    
    public function __get($key)
    {
        return (isset($this->_data[$key])) ? $this->_data[$key] : false; 
    }
    
    public function __call($method, $args) 
    {
        if (array_key_exists($method, $this->_helpers)) {
            return call_user_func_array($this->_helpers[$method], $args);
        } else {
            throw new RuntimeException("Helper view {$method} doesn't exists. Add it using addHelper method.");
        }
    }
    
    public function addViewPath($path)
    {
        if (!is_dir($path)) {
            throw new RuntimeException("View path {$path} must be a directory");
        }
        $this->_path[] = $path;
    }
    
    public function getViewPaths()
    {
        return $this->_path;
    }
    
    /**
     * @deprecated This method is deprecated use <code>addViewPath()</code>
     */
    public function setViewPath($path)
    {
        if (!is_dir($path)) {
            throw new RuntimeException("View path {$path} must be a directory");
        }
        $this->_path = array($path);
    }
    
    /**
     * @deprecated This method is deprecated in favor of <code>getViewPaths()</code>
     */
    public function getViewPath()
    {
        return (count($this->_path) > 0) ? $this->_path[0] : false;
    }
    
    public function render($filename, $data = false)
    {
        if($data) {
            if (!is_array($data)) {
                throw new RuntimeException("You must pass an array to data view.");
            }
            $this->_data = array_merge($this->_data, $data);
        }
    
        if(!count($this->getViewPaths())) {
            $this->addViewPath(dirname(__FILE__));
        }

        $filename = $this->_selectView($this->getViewPaths(), $filename);
    
        $rendered = "";
    
        ob_start();
        require($filename);
        $rendered = ob_get_contents();
        ob_end_clean();
    
        return $rendered;
    }
    
    protected function _selectView($paths, $filename)
    {
        do {
            $path = array_pop($paths);
            $filename = $path . "/" . $filename ;
            if (file_exists($filename)) {
                return $filename;
            }
        } while($paths);
        
        throw new RuntimeException("Unable to get view at path: {$filename}");
    }
    
    public function cloneThis()
    {
        return clone($this);
    }
    
    public function addHelper($name, $helper) 
    {
        $this->_helpers[$name] = $helper;
    }
    
    public function addHelpers(array $helpers)
    {
        $this->_helpers = array_merge($this->_helpers, $helpers);
    }
    
    public function getHelpers()
    {
        return $this->_helpers;
    }
}