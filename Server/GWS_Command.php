<?php
declare(strict_types=1);
namespace GDO\Websocket\Server;

use GDO\Core\GDO;
use GDO\Core\GDO_Exception;
use GDO\Core\GDT_Decimal;
use GDO\Core\GDT_Enum;
use GDO\Core\GDT_Float;
use GDO\Core\GDT_Int;
use GDO\Core\GDT_String;
use GDO\Date\GDT_Timestamp;
use GDO\Date\Time;
use GDO\Maps\GDT_Position;
use GDO\Table\GDT_PageMenu;
use GDO\User\GDO_User;
use GDO\Util\WS;

/**
 * GWS_Commands have to register via GWS_Commands::register($code, GWS_Command, $binary=true)
 *
 * @author gizmore
 */
abstract class GWS_Command
{

	protected $message;

	public function setMessage(GWS_Message $message)
	{
		$this->message = $message;
		return $this;
	}

	public function user(): GDO_User { return $this->message->user(); }

	public function message() { return $this->message; }

	################
	### Abstract ###
	################
	public function execute(GWS_Message $msg) {}

	############
	### Util ###
	############
	/**
	 * @throws GDO_Exception
	 */
	public function userToBinary(GDO_User $user): string
	{
		return $this->gdoToBinary($user);
	}

	/**
	 * @throws GDO_Exception
	 */
	public function gdoToBinary(GDO $gdo, array $fields = null): string
	{
		$fields = $fields ? $gdo->getGDOColumns($fields) : $gdo->gdoColumnsCache();
		$payload = '';
		foreach ($fields as $field)
        {
            if ($field->isSerializable())
            {
                $out = $field->gdo($gdo)->renderBinary();
                echo "Write {$field->getName()}: ";
                GWS_Message::hexdump($out)."\n";
                $payload .= $out;
            }
        }
        return $payload;
	}

	public function pagemenuToBinary(GDT_PageMenu $gdt): string
	{
		return $gdt->renderBinary();
	}

}
