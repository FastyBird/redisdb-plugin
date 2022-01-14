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
Redis DB exchange plugin publisher
"""

# Python base dependencies
import json
from typing import Dict, Optional, Union

# Library dependencies
from exchange.publisher import IPublisher
from metadata.routing import RoutingKey
from metadata.types import ModuleOrigin, PluginOrigin

# Library libs
from redisdb_exchange_plugin.connection import Connection
from redisdb_exchange_plugin.logger import Logger


class Publisher(IPublisher):  # pylint: disable=too-few-public-methods
    """
    Exchange data publisher

    @package        FastyBird:RedisDbExchangePlugin!
    @module         publisher

    @author         Adam Kadlec <adam.kadlec@fastybird.com>
    """

    __channel_name: str

    __connection: Connection

    __logger: Logger

    # -----------------------------------------------------------------------------

    def __init__(
        self,
        channel_name: str,
        connection: Connection,
        logger: Logger,
    ) -> None:
        self.__channel_name = channel_name

        self.__connection = connection

        self.__logger = logger

    # -----------------------------------------------------------------------------

    def publish(self, origin: Union[ModuleOrigin, PluginOrigin], routing_key: RoutingKey, data: Optional[Dict]) -> None:
        """Publish message to Redis exchange"""
        message = {
            "routing_key": routing_key.value,
            "origin": origin.value,
            "sender_id": self.__connection.identifier,
            "data": data,
        }

        result: int = self.__connection.publish(channel=self.__channel_name, message=json.dumps(message))

        self.__logger.debug(
            "Successfully published message to: %d consumers via RedisDB exchange plugin with key: %s",
            result,
            routing_key,
            extra={
                "source": "redisdb-exchange-plugin-publisher",
                "type": "publish",
            },
        )
