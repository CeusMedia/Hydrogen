<?php
class CMF_Hydrogen_Environment_Resource_Page extends UI_HTML_PageFrame
{
	public function __construct( CMF_Hydrogen_Environment_Abstract $env )
	{
		parent::__construct();
		$this->env	= $env;
		$this->js	= CMF_Hydrogen_View_Helper_JavaScript::getInstance();
	}
}
?>
