<?php
namespace system\lib;

class TemplateManager
{

	public function getErrorTemplate ($errorCode, $errorBody)
	{
		return str_replace('@erorcode@', $errorCode, 
				str_replace('@errortext@', $errorBody, $this->compose('ERROR')));
	}

	private function compose ($elem)
	{
		return str_replace('@appname@', $this->appname, 
				str_replace('@docelem@', $this->docElem, 
						$this->HEAD . $this->templates[$elem] . $this->FOOT));
	}

	private $templates = array(
		'ERROR' => '<h1>@erorcode@</h1><p>@errortext@</p>'
	);

	private $HEAD = '<html><head><title>@appname@ - @docelem@</title><body>';

	private $FOOT = '</body></html>';

	private $appname = 'something', $docElem = 'somewhere';
}