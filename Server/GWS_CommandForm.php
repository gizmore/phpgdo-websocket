<?php
namespace GDO\Websocket\Server;

use GDO\CLI\CLI;
use GDO\Core\GDO_Exception;
use GDO\Core\GDT;
use GDO\Core\GDT_Int;
use GDO\Core\GDT_JSON;
use GDO\Core\GDT_Response;
use GDO\Core\GDT_String;
use GDO\Core\Method;
use GDO\Form\GDT_Form;
use GDO\Form\GDT_Submit;
use GDO\Session\GDO_Session;
use GDO\UI\GDT_Success;

/**
 * Call MethodForm via websockets.
 *
 * @version 6.10.1
 * @since 5.0
 * @author gizmore
 */
abstract class GWS_CommandForm extends GWS_Command
{

	public function execute(GWS_Message $msg)
	{
		parent::execute($msg);

		$method = $this->getMethod();
		$form = $method->getForm();
		$this->fillRequestVars($msg, $method);

		$this->removeCSRF($form);

		try
		{
			$form = GWS_Form::bindMethodForm($method, $msg);
		}
		catch (GDO_Exception $ex)
		{
			$msg->replyErrorMessage($msg->cmd(), t('err_bind_form', [
				$ex->getMessage(),
			]));
			return;
		}

// 		$this->selectSubmit($form);
		$inputs = $method->getInputs();
		if ($button = $method->getAutoButton())
		{
			$inputs[$button] = '1';
		}
		$response = $method->executeWithInputs($inputs);
		$this->postExecute($msg, $form, $response);
	}

	/**
	 * @return Method
	 */
	abstract public function getMethod();

	public function fillRequestVars(GWS_Message $msg, Method $method) {}

	protected function removeCSRF(GDT_Form $form)
	{
		$form->removeFieldNamed('xsrf');
	}

	public function postExecute(GWS_Message $msg, GDT_Form $form, GDT_Response $response)
	{
		if ($response->hasError())
		{
			echo print_r($response->render(), 1);
			$msg->replyErrorMessage($msg->cmd(), $response->renderJSON());
		}
		else
		{
			GDO_Session::instance()->commit();
			$this->replySuccess($msg, $form, $response);
			$this->afterReplySuccess($msg);
		}
	}

	public function replySuccess(GWS_Message $msg, GDT_Form $form, GDT_Response $response)
	{
		$msg->replyBinary($msg->cmd(), $this->payloadFromResponse($response));
	}

	private function payloadFromResponse(GDT_Response $response)
	{
		$payload = '';
		foreach ($response->getFields() as $gdt)
		{
			$payload .= $this->payloadFromField($gdt);
		}

		$payload .= CLI::getTopResponse();

		return $payload;
	}

	private function payloadFromField(GDT $gdt)
	{
		$payload = '';
		if ($gdt instanceof GDT_JSON)
		{
			foreach ($gdt->getFields() as $gdt)
			{
				$payload .= $this->payloadFromField($gdt);
			}
		}
		elseif ($gdt instanceof GDT_String)
		{
			$payload .= GWS_Message::wrS($gdt->getVar());
		}
		elseif ($gdt instanceof GDT_Int)
		{
			$payload .= GWS_Message::wrN($gdt->bytes, $gdt->getValue());
		}
		elseif ($gdt instanceof GDT_Success)
		{
			$payload .= GWS_Message::wrS($gdt->renderText());
		}

		// if ($fields = $gdt->getFields())
		// {
		// foreach ($fields as $gdt2)
		// {
		// $payload .= $this->payloadFromField($gdt2);
		// }
		// }

		return $payload;
	}

	public function afterReplySuccess(GWS_Message $msg) {}

	/**
	 *
	 * @param GDT_Form $form
	 *
	 * @return GDT_Submit[]
	 */
	protected function getSubmits(GDT_Form $form)
	{
		$submits = [];
		foreach ($form->actions() as $field)
		{
			if ($field instanceof GDT_Submit)
			{
				$submits[] = $field;
			}
		}
		return $submits;
	}

	protected function removeCaptcha(GDT_Form $form)
	{
		$form->removeFieldNamed('captcha');
	}

// 	protected function selectSubmit(GDT_Form $form)
// 	{
// 		$this->selectSubmitNum($form, 0);
// 	}

// 	protected function selectSubmitNum(GDT_Form $form, $num)
// 	{
// 		$submits = $this->getSubmits($form);
// 		if ($submit = @$submits[$num])
// 		{
// 			$name = $submit->name;
// 			$f = $form->formName();
// // 			$_REQUEST[$f][$name] = $name;
// 		}
// 	}

}
