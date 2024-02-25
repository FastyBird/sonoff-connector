<?php declare(strict_types = 1);

/**
 * DeviceSettings.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:SonoffConnector!
 * @subpackage     API
 * @since          1.0.0
 *
 * @date           07.05.23
 */

namespace FastyBird\Connector\Sonoff\API\Messages\Response\Cloud;

use FastyBird\Connector\Sonoff\API;
use Orisai\ObjectMapper;

/**
 * Device settings entity
 *
 * @package        FastyBird:SonoffConnector!
 * @subpackage     API
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final readonly class DeviceSettings implements API\Messages\Message
{

	public function __construct(
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\IntValue(),
			new ObjectMapper\Rules\NullValue(castEmptyString: true),
		])]
		private int|null $opsNotify = null,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\IntValue(),
			new ObjectMapper\Rules\NullValue(castEmptyString: true),
		])]
		private int|null $opsHistory = null,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\IntValue(),
			new ObjectMapper\Rules\NullValue(castEmptyString: true),
		])]
		private int|null $alarmNotify = null,
	)
	{
	}

	public function hasOpsNotify(): bool
	{
		return $this->opsNotify === 1;
	}

	public function hasOpsHistory(): bool
	{
		return $this->opsHistory === 1;
	}

	public function hasAlarmNotify(): bool
	{
		return $this->alarmNotify === 1;
	}

	/**
	 * {@inheritDoc}
	 */
	public function toArray(): array
	{
		return [
			'ops_notify' => $this->hasOpsNotify(),
			'ops_history' => $this->hasOpsHistory(),
			'alarm_notify' => $this->hasAlarmNotify(),
		];
	}

}
