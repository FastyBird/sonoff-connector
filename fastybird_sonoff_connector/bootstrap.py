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
Sonoff connector DI container
"""

# pylint: disable=no-value-for-parameter

# Python base dependencies
import logging

# Library dependencies
from kink import di

# Library libs
from fastybird_sonoff_connector.connector import SonoffConnector
from fastybird_sonoff_connector.entities import SonoffConnectorEntity
from fastybird_sonoff_connector.logger import Logger


def create_connector(
    connector: SonoffConnectorEntity,
    logger: logging.Logger = logging.getLogger("dummy"),
) -> SonoffConnector:
    """Create Sonoff connector services"""
    if isinstance(logger, logging.Logger):
        connector_logger = Logger(connector_id=connector.id, logger=logger)

        di[Logger] = connector_logger
        di["sonoff-connector_logger"] = di[Logger]

    else:
        connector_logger = logger

    # Main connector service
    connector_service = SonoffConnector(
        connector_id=connector.id,
        logger=connector_logger,
    )
    di[SonoffConnector] = connector_service
    di["sonoff-connector_connector"] = connector_service

    return connector_service
