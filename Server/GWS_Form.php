<?php
namespace GDO\Websocket\Server;

use GDO\Core\GDO_Exception;
use GDO\Core\GDT;
use GDO\Core\GDT_Checkbox;
use GDO\Core\GDT_Decimal;
use GDO\Core\GDT_Enum;
use GDO\Core\GDT_Float;
use GDO\Core\GDT_Int;
use GDO\Core\GDT_Object;
use GDO\Core\GDT_String;
use GDO\Core\Logger;
use GDO\Core\Method;
use GDO\Date\GDT_Timestamp;
use GDO\Form\GDT_Form;
use GDO\Form\MethodForm;

/**
 * Fill a GDT_Form with a GWS_Message.
 * Fill a Method with a GWS_Message.
 *
 * @version 6.07
 *
 * @since 5.0
 * @author gizmore
 * @see GDT;
 * @see GDT_Form
 * @see GWS_Message
 */
final class GWS_Form
{

	public static function bindMethod(Method $method, GWS_Message $msg)
	{
		return self::bindFields($method, $msg);
	}

	public static function bindFields(Method $method, GWS_Message $msg)
	{
		$fields = $method->gdoParameterCache();
		$inputs = [];
		foreach ($fields as $gdt)
		{
			self::bind($gdt, $msg, $inputs);
		}
		$method->inputs($inputs);
	}

	private static function bind(GDT $gdt, GWS_Message $msg, array &$inputs)
	{
		try
		{
			if ($gdt->isSerializable())
			{
				Logger::logWebsocket(sprintf('Reading %s as a %s.', $gdt->getName(), get_class($gdt)));

				if ($gdt instanceof GDT_Checkbox)
				{
					$var = $msg->read8();
					$inputs[$gdt->getName()] = (string) $var;
//					$gdt->value($value > 0);
				}
				elseif ($gdt instanceof GDT_String)
				{
					$var = $msg->readString();
					$inputs[$gdt->getName()] = (string)$var;
//					$gdt->addInputValue($msg->readString());
				}
				elseif (
					($gdt instanceof GDT_Decimal) ||
					($gdt instanceof GDT_Float)
				)
				{
					$value = $msg->readFloat();
					$inputs[$gdt->getName()] = (string) $value;
//					$gdt->addInputValue($msg->readFloat());
				}
				elseif ($gdt instanceof GDT_Object)
				{
					$value = $msg->read32u();
					$inputs[$gdt->getName()] = (string)$value;
//					$gdt->addInputValue($msg->read32u());
				}
				elseif ($gdt instanceof GDT_Int)
				{
					$value = $msg->readN($gdt->bytes, !$gdt->unsigned);
					$inputs[$gdt->getName()] = (string)$value;
//					$gdt->addInputValue();
				}
				elseif ($gdt instanceof GDT_Enum)
				{
					$value = $gdt->enumForId($msg->read16u());
					$inputs[$gdt->getName()] = (string)$value;
//					$gdt->addInputValue();
				}
				elseif ($gdt instanceof GDT_Timestamp)
				{
					$ts = $msg->read32u();
					if ($ts)
					{
						$inputs[$gdt->getName()] = $ts;
//						$gdt->addInputValue($ts);
					}
					else
					{
						$inputs[$gdt->getName()] = null;
//						$gdt->addInputValue(null);
					}
				}
				Logger::logWebsocket(sprintf('Reading %s as a %s with var %s.', $gdt->name, get_class($gdt), $gdt->var));
			}
		}
		catch (GDO_Exception $ex)
		{
			Logger::logException($ex);
			throw new GDO_Exception("Cannot read {$gdt->name} which is a " . get_class($gdt));
		}
	}

	public static function bindMethodForm(MethodForm $method, GWS_Message $msg)
	{
		return self::bindForm($method, $msg);
	}

	public static function bindForm(Method $method, GWS_Message $msg)
	{
		self::bindFields($method, $msg);
		return $method->getForm();
	}

}
