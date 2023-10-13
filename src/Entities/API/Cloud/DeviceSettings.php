<?php declare(strict_types = 1);

/**
 * DeviceSettings.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:SonoffConnector!
 * @subpackage     Entities
 * @since          1.0.0
 *
 * @date           07.05.23
 */

namespace FastyBird\Connector\Sonoff\Entities\API\Cloud;

use FastyBird\Connector\Sonoff\Entities;
use Orisai\ObjectMapper;

/**
 * Device settings entity
 *
 * @package        FastyBird:SonoffConnector!
 * @subpackage     Entities
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class DeviceSettings implements Entities\API\Entity
{

	public function __construct(
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\IntValue(),
			new ObjectMapper\Rules\NullValue(castEmptyString: true),
		])]
		private readonly int|null $opsNotify = null,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\IntValue(),
			new ObjectMapper\Rules\NullValue(castEmptyString: true),
		])]
		private readonly int|null $opsHistory = null,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\IntValue(),
			new ObjectMapper\Rules\NullValue(castEmptyString: true),
		])]
		private readonly int|null $alarmNotify = null,
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
