<?php
declare(strict_types=1);

namespace GDO\Websocket\Method;

use GDO\CLI\MethodCLI;
use GDO\Core\GDT;
use GDO\Core\GDT_Response;
use GDO\Form\GDT_Form;
use GDO\Form\GDT_Submit;
use GDO\User\GDO_Permission;
use GDO\Websocket\Module_Websocket;
use GDO\Websocket\Server\GWS_Server;

/**
 * Start a websocket server via gdo command.
 */
final class Run extends MethodCLI
{

	public function getPermission(): ?string
	{
		return GDO_Permission::ADMIN;
	}

	public function isTrivial(): bool
	{
		return false;
	}

	public function createForm(GDT_Form $form): void
	{
		$form->actions()->addField(GDT_Submit::make());
	}


	public function formValidated(GDT_Form $form): GDT
	{
		$this->run();
		return GDT_Response::make();
	}

	public function run(): void
	{
		$gws = Module_Websocket::instance();

		$processorPath = $gws->cfgWebsocketProcessorPath();
		require $processorPath;

		$processor = $gws->processorClass();

		$server = new GWS_Server();
		if (GDO_IPC === 'ipc')
		{
			$server->ipcTimer();
		}
		$server->initGWSServer(new $processor(), $gws);
		$server->mainloop($gws->cfgTimer());
	}

}
