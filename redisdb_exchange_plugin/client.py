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
Redis DB exchange plugin exchange service
"""

# Python base dependencies
import json
import time
from abc import ABC
from typing import Dict, Optional, Union

# Library dependencies
import metadata.exceptions as metadata_exceptions
from kink import inject
from metadata.loader import load_schema_by_routing_key
from metadata.routing import RoutingKey
from metadata.types import ModuleOrigin
from metadata.validator import validate

# Library libs
from redisdb_exchange_plugin.connection import Connection
from redisdb_exchange_plugin.exceptions import (
    HandleDataException,
    HandleRequestException,
)
from redisdb_exchange_plugin.logger import Logger


class IConsumer(ABC):  # pylint: disable=too-few-public-methods
    """
    Redis client consumer interface

    @package        FastyBird:RedisDbExchangePlugin!
    @module         client

    @author         Adam Kadlec <adam.kadlec@fastybird.com>
    """

    def consume(
        self,
        origin: ModuleOrigin,
        routing_key: RoutingKey,
        data: Optional[Dict[str, Union[str, int, float, bool, None]]],
    ) -> None:
        """Consume data received from exchange bus"""


@inject
class Client:
    """
    Redis exchange client

    @package        FastyBird:RedisDbExchangePlugin!
    @module         client

    @author         Adam Kadlec <adam.kadlec@fastybird.com>
    """

    __connection: Connection

    __consumer: Optional[IConsumer]

    __logger: Logger

    # -----------------------------------------------------------------------------

    def __init__(
        self,
        connection: Connection,
        logger: Logger,
        consumer: IConsumer = None,  # type: ignore[assignment]
    ) -> None:
        self.__connection = connection
        self.__logger = logger

        self.__consumer = consumer

    # -----------------------------------------------------------------------------

    def start(self) -> None:
        """Start exchange services"""
        self.__connection.subscribe()

        self.__logger.info("Starting Redis DB exchange client")

    # -----------------------------------------------------------------------------

    def stop(self) -> None:
        """Close all opened connections & stop exchange thread"""
        self.__connection.unsubscribe()
        self.__connection.close()

        self.__logger.info("Closing Redis DB exchange client")

    # -----------------------------------------------------------------------------

    def handle(self) -> None:
        """Process Redis exchange messages"""
        try:
            data = self.__connection.receive()

            if data is not None:
                self.__receive(data)

            time.sleep(0.001)

        except OSError as ex:
            raise HandleRequestException("Error reading from redis database") from ex

    # -----------------------------------------------------------------------------

    def register_consumer(
        self,
        consumer: IConsumer,
    ) -> None:
        """Register new consumer to server"""
        self.__consumer = consumer

    # -----------------------------------------------------------------------------

    def __receive(self, data: Dict) -> None:
        if self.__consumer is None:
            return

        try:
            origin = self.__validate_origin(origin=data.get("origin", None))
            routing_key = self.__validate_routing_key(
                routing_key=data.get("routing_key", None),
            )

            if (
                routing_key is not None
                and origin is not None
                and data.get("data", None) is not None
                and isinstance(data.get("data", None), dict) is True
            ):
                data = self.__validate_data(
                    origin=origin,
                    routing_key=routing_key,
                    data=data.get("data", None),
                )

                self.__consumer.consume(
                    origin=origin,
                    routing_key=routing_key,
                    data=data,
                )

            else:
                self.__logger.warning("Received exchange message is not valid")

        except HandleDataException as ex:
            self.__logger.exception(ex)

    # -----------------------------------------------------------------------------

    @staticmethod
    def __validate_origin(origin: Optional[str]) -> Optional[ModuleOrigin]:
        if origin is not None and isinstance(origin, str) is True and ModuleOrigin.has_value(origin):
            return ModuleOrigin(origin)

        return None

    # -----------------------------------------------------------------------------

    @staticmethod
    def __validate_routing_key(routing_key: Optional[str]) -> Optional[RoutingKey]:
        if routing_key is not None and isinstance(routing_key, str) is True and RoutingKey.has_value(routing_key):
            return RoutingKey(routing_key)

        return None

    # -----------------------------------------------------------------------------

    def __validate_data(self, origin: ModuleOrigin, routing_key: RoutingKey, data: Dict) -> Dict:
        """Validate received exchange message against defined schema"""
        try:
            schema: str = load_schema_by_routing_key(routing_key)

        except metadata_exceptions.FileNotFoundException as ex:
            self.__logger.error(
                "Schema file for origin: %s and routing key: %s could not be loaded",
                origin.value,
                routing_key.value,
            )

            raise HandleDataException("Provided data could not be validated") from ex

        except metadata_exceptions.InvalidArgumentException as ex:
            self.__logger.error(
                "Schema file for origin: %s and routing key: %s is not configured in mapping",
                origin.value,
                routing_key.value,
            )

            raise HandleDataException("Provided data could not be validated") from ex

        try:
            return validate(json.dumps(data), schema)

        except metadata_exceptions.MalformedInputException as ex:
            raise HandleDataException("Provided data are not in valid json format") from ex

        except metadata_exceptions.LogicException as ex:
            self.__logger.error(
                "Schema file for origin: %s and routing key: %s could not be parsed & compiled",
                origin.value,
                routing_key.value,
            )

            raise HandleDataException("Provided data could not be validated") from ex

        except metadata_exceptions.InvalidDataException as ex:
            raise HandleDataException("Provided data are not valid") from ex
