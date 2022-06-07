#!/usr/bin/python3

#     Copyright 2021. FastyBird s.r.o.
#
#     Licensed under the Apache License, Version 2.0 (the "License");
#     you may not use this file except in compliance with the License.
#     You may obtain a copy of the License at
#
#         http://www.apache.org/licenses/LICENSE-2.0
#
#     Unless required by applicable law or agreed to in writing, software
#     distributed under the License is distributed on an "AS IS" BASIS,
#     WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
#     See the License for the specific language governing permissions and
#     limitations under the License.

"""
Sonoff connector module
"""

# Python base dependencies
import asyncio
import logging
import uuid
from typing import Dict, Optional, Union

# Library dependencies
from fastybird_devices_module.connectors.connector import IConnector
from fastybird_devices_module.entities.channel import (
    ChannelControlEntity,
    ChannelEntity,
    ChannelPropertyEntity,
)
from fastybird_devices_module.entities.connector import ConnectorControlEntity
from fastybird_devices_module.entities.device import (
    DeviceAttributeEntity,
    DeviceControlEntity,
    DevicePropertyEntity,
)
from fastybird_metadata.types import ControlAction
from kink import inject

# Library libs
from fastybird_sonoff_connector.entities import (
    SonoffConnectorEntity,
    SonoffDeviceEntity,
)
from fastybird_sonoff_connector.logger import Logger


@inject(alias=IConnector)
class SonoffConnector(IConnector):  # pylint: disable=too-many-instance-attributes,too-many-public-methods
    """
    Sonoff connector

    @package        FastyBird:SonoffConnector!
    @module         connector

    @author         Adam Kadlec <adam.kadlec@fastybird.com>
    """

    __stopped: bool = False

    __connector_id: uuid.UUID

    __logger: Union[Logger, logging.Logger]

    # -----------------------------------------------------------------------------

    @property
    def id(self) -> uuid.UUID:  # pylint: disable=invalid-name
        """Connector identifier"""
        return self.__connector_id

    # -----------------------------------------------------------------------------

    def __init__(  # pylint: disable=too-many-arguments
        self,
        connector_id: uuid.UUID,
        logger: Union[Logger, logging.Logger] = logging.getLogger("dummy"),
    ) -> None:
        self.__connector_id = connector_id

        self.__logger = logger

    # -----------------------------------------------------------------------------

    def initialize(self, connector: SonoffConnectorEntity) -> None:
        """Set connector to initial state"""

    # -----------------------------------------------------------------------------

    def initialize_device(self, device: SonoffDeviceEntity) -> None:
        """Initialize device in connector registry"""

    # -----------------------------------------------------------------------------

    def remove_device(self, device_id: uuid.UUID) -> None:
        """Remove device from connector registry"""

    # -----------------------------------------------------------------------------

    def reset_devices(self) -> None:
        """Reset devices registry to initial state"""

    # -----------------------------------------------------------------------------

    def initialize_device_property(self, device: SonoffDeviceEntity, device_property: DevicePropertyEntity) -> None:
        """Initialize device property"""

    # -----------------------------------------------------------------------------

    def notify_device_property(self, device: SonoffDeviceEntity, device_property: DevicePropertyEntity) -> None:
        """Notify device property was reported to connector"""

    # -----------------------------------------------------------------------------

    def remove_device_property(self, device: SonoffDeviceEntity, property_id: uuid.UUID) -> None:
        """Remove device property from connector registry"""

    # -----------------------------------------------------------------------------

    def reset_devices_properties(self, device: SonoffDeviceEntity) -> None:
        """Reset devices properties registry to initial state"""

    # -----------------------------------------------------------------------------

    def initialize_device_attribute(self, device: SonoffDeviceEntity, device_attribute: DeviceAttributeEntity) -> None:
        """Initialize device attribute"""

    # -----------------------------------------------------------------------------

    def notify_device_attribute(self, device: SonoffDeviceEntity, device_attribute: DeviceAttributeEntity) -> None:
        """Notify device attribute was reported to connector"""

    # -----------------------------------------------------------------------------

    def remove_device_attribute(self, device: SonoffDeviceEntity, attribute_id: uuid.UUID) -> None:
        """Remove device attribute from connector registry"""

    # -----------------------------------------------------------------------------

    def reset_devices_attributes(self, device: SonoffDeviceEntity) -> None:
        """Reset devices attributes registry to initial state"""

    # -----------------------------------------------------------------------------

    def initialize_device_channel(self, device: SonoffDeviceEntity, channel: ChannelEntity) -> None:
        """Initialize device channel"""

    # -----------------------------------------------------------------------------

    def remove_device_channel(self, device: SonoffDeviceEntity, channel_id: uuid.UUID) -> None:
        """Remove device channel from connector registry"""

    # -----------------------------------------------------------------------------

    def reset_devices_channels(self, device: SonoffDeviceEntity) -> None:
        """Reset devices channels registry to initial state"""

    # -----------------------------------------------------------------------------

    def initialize_device_channel_property(
        self,
        channel: ChannelEntity,
        channel_property: ChannelPropertyEntity,
    ) -> None:
        """Initialize device channel property"""

    # -----------------------------------------------------------------------------

    def notify_device_channel_property(
        self,
        channel: ChannelEntity,
        channel_property: ChannelPropertyEntity,
    ) -> None:
        """Notify device channel property was reported to connector"""

    # -----------------------------------------------------------------------------

    def remove_device_channel_property(self, channel: ChannelEntity, property_id: uuid.UUID) -> None:
        """Remove device channel property from connector registry"""

    # -----------------------------------------------------------------------------

    def reset_devices_channels_properties(self, channel: ChannelEntity) -> None:
        """Reset devices channels properties registry to initial state"""

    # -----------------------------------------------------------------------------

    async def start(self) -> None:
        """Start connector services"""
        self.__logger.info("Connector has been started")

        self.__stopped = False

        # Register connector coroutine
        asyncio.ensure_future(self.__worker())

    # -----------------------------------------------------------------------------

    def stop(self) -> None:
        """Close all opened connections & stop connector"""
        self.__logger.info("Connector has been stopped")

        self.__stopped = True

    # -----------------------------------------------------------------------------

    def has_unfinished_tasks(self) -> bool:
        """Check if connector has some unfinished task"""
        return False

    # -----------------------------------------------------------------------------

    async def write_property(  # pylint: disable=too-many-branches
        self,
        property_item: Union[DevicePropertyEntity, ChannelPropertyEntity],
        data: Dict,
    ) -> None:
        """Write device or channel property value to device"""

    # -----------------------------------------------------------------------------

    async def write_control(
        self,
        control_item: Union[ConnectorControlEntity, DeviceControlEntity, ChannelControlEntity],
        data: Optional[Dict],
        action: ControlAction,
    ) -> None:
        """Write connector control action"""

    # -----------------------------------------------------------------------------

    async def __worker(self) -> None:
        """Run connector service"""
        while True:
            if self.__stopped and self.has_unfinished_tasks():
                return

            # Be gentle to server
            await asyncio.sleep(0.01)
