<?php
namespace GDO\Websocket\Server;

use GDO\Core\Method;
use GDO\Core\GDT_Response;
use GDO\Core\Application;

/**
 * Call GDO Method via websockets.
 * Either override fillRequestVars or gdoParameters in your derrived @link Method
 * 
 * @author gizmore
 * @version 7.0.1
 * @since 5.0.0
 */
abstract class GWS_CommandMethod extends GWS_Command
{
	/**
	 * @return Method
	 */
	public abstract function getMethod();
	
	public function fillRequestVars(GWS_Message $msg, Method $method)
	{
		GWS_Form::bindMethod($method, $msg);
	}
	
	public function execute(GWS_Message $msg)
	{
	    parent::execute($msg);
		$method = $this->getMethod();
		$this->fillRequestVars($msg, $method);
		$response = $method->executeWithInit();
		$this->postExecute($msg, $response);
	}
	
	public function postExecute(GWS_Message $msg, GDT_Response $response)
	{
		if (Application::$INSTANCE->isError())
		{
			$msg->replyErrorMessage($msg->cmd(), $response->displayJSON());
		}
		else
		{
			$this->replySuccess($msg, $response);
		}
	}
	
	public function replySuccess(GWS_Message $msg, GDT_Response $response)
	{
		$msg->replyBinary($msg->cmd());
	}
	
}
