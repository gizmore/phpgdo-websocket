<?php
namespace GDO\Websocket\Server;

use GDO\Core\GDO;
use GDO\Date\GDT_Timestamp;
use GDO\Date\Time;
use GDO\Core\GDT_Enum;
use GDO\Core\GDT_Decimal;
use GDO\Core\GDT_Int;
use GDO\Core\GDT_String;
use GDO\User\GDO_User;
use GDO\Maps\GDT_Position;
use GDO\Table\GDT_PageMenu;
use GDO\Core\GDT_Float;
use GDO\Core\GDO_Exception;
use GDO\UI\GDT_Page;

/**
 * GWS_Commands have to register via GWS_Commands::register($code, GWS_Command, $binary=true)
 * @author gizmore
 */
abstract class GWS_Command
{
	protected $message;
	
	public function setMessage(GWS_Message $message) { $this->message = $message; return $this; }
	
	/**
	 * @return GDO_User
	 */
	public function user() { return $this->message->user(); }
	public function message() { return $this->message; }
	
	################
	### Abstract ###
	################
	public function execute(GWS_Message $msg)
	{
	}

	############
	### Util ###
	############
	public function userToBinary(GDO_User $user)
	{
		$fields = $user->gdoColumnsExcept('user_password', 'user_register_ip');
		return $this->gdoToBinary($user, array_keys($fields));
	}
	
	public function gdoToBinary(GDO $gdo, array $fields=null)
	{
		$fields = $fields ? $gdo->getGDOColumns($fields) : $gdo->gdoColumnsCache();
		$payload = '';
		foreach ($fields as $field)
		{
		    if ( (!$field->isSerializable()) )
		    {
		        continue;
		    }
		    
			$field->gdo($gdo);
			
			if ($field instanceof GDT_Position)
			{
				$payload .= GWS_Message::wrF(floatval($field->getLat()));
				$payload .= GWS_Message::wrF(floatval($field->getLng()));
			}
			elseif ($field instanceof GDT_String)
			{
				$payload .= GWS_Message::wrS($gdo->gdoVar($field->name));
			}
			elseif ( ($field instanceof GDT_Decimal) ||
					 ($field instanceof GDT_Float) )
			{
				$payload .= GWS_Message::wrF($gdo->gdoVar($field->name));
			}
			elseif ($field instanceof GDT_Int)
			{
				$payload .= GWS_Message::wrN($field->bytes, $gdo->gdoVar($field->name));
			}
			elseif ($field instanceof GDT_Enum)
			{
				$value = array_search($gdo->gdoVar($field->name), $field->enumValues);
				$payload .= GWS_Message::wr16($value === false ? 0 : $value + 1);
			}
			elseif ($field instanceof GDT_Timestamp)
			{
				$time = 0;
				if ($date = $gdo->gdoVar($field->name))
				{
					$time = Time::getTimestamp($date);
				}
				$payload .= GWS_Message::wr32(floor($time));
			}
			else
			{
				throw new GDO_Exception("Cannot ws encode {$field->name}");
			}
		}
		return $payload;
	}
	
	public function pagemenuToBinary(GDT_PageMenu $gdt)
	{
		return GWS_Message::wr16($gdt->getPage()) . 
		   GWS_Message::wr16($gdt->getPageCount()) .
		   GWS_Message::wr32($gdt->numItems) .
		   GWS_Message::wr16($gdt->ipp);
	}

}
