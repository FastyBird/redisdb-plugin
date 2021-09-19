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
Redis data exchange
"""

# Library dependencies
import logging
import uuid
from typing import Callable, Dict
from modules_metadata.routing import RoutingKey
from modules_metadata.types import ModuleOrigin

# Library libs
from redisdb_exchange_plugin.consumer import Consumer
from redisdb_exchange_plugin.publisher import Publisher


class RedisExchange:
    """
    Redis data exchange

    @package        FastyBird:RedisDbExchangePlugin!
    @module         redis

    @author         Adam Kadlec <adam.kadlec@fastybird.com>
    """
    __consumer: Consumer
    __publisher: Publisher

    __logger: logging.Logger

    # -----------------------------------------------------------------------------

    def __init__(self, config: dict) -> None:
        identifier = uuid.uuid4()

        self.__publisher = Publisher(
            identifier=identifier.__str__(),
            config=config,
            channel_name=str(config.get("channel", "fb_exchange")),
        )

        self.__consumer = Consumer(
            identifier=identifier.__str__(),
            config=config,
            channel_name=str(config.get("channel", "fb_exchange")),
        )

        self.__logger = logging.getLogger("dummy")

    # -----------------------------------------------------------------------------

    def set_logger(self, logger: logging.Logger) -> None:
        """Configure custom logger handler"""
        self.__logger = logger

        self.__consumer.set_logger(logger)
        self.__publisher.set_logger(logger)

    # -----------------------------------------------------------------------------

    def set_data_callback(self, callback: Callable[[ModuleOrigin, RoutingKey, Dict or None], None]) -> None:
        """Set consumer handle data callback"""
        self.__consumer.set_data_callback(callback)

    # -----------------------------------------------------------------------------

    def start(self) -> None:
        """Start exchange services"""
        self.__consumer.start()

    # -----------------------------------------------------------------------------

    def close(self) -> None:
        """Close all opened connections & stop server thread"""
        self.__publisher.close()
        self.__consumer.close()

        self.__logger.info("Redis DB exchange was closed")

    # -----------------------------------------------------------------------------

    def is_healthy(self) -> bool:
        """Check if exchange is healthy"""
        return self.__consumer.is_alive()

    # -----------------------------------------------------------------------------

    def publish(self, origin: ModuleOrigin, routing_key: RoutingKey, data: dict) -> None:
        """Publish message to Redis exchange"""
        self.__publisher.publish(origin=origin, routing_key=routing_key, data=data)
