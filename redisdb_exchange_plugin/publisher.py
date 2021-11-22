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
from typing import Dict, Optional

# Library dependencies
from exchange_plugin.publisher import IPublisher
from kink import inject
from modules_metadata.routing import RoutingKey
from modules_metadata.types import ModuleOrigin

# Library libs
from redisdb_exchange_plugin.connection import RedisClient


@inject
class Publisher(IPublisher):  # pylint: disable=too-few-public-methods
    """
    Exchange data publisher

    @package        FastyBird:RedisDbExchangePlugin!
    @module         publisher

    @author         Adam Kadlec <adam.kadlec@fastybird.com>
    """

    __redis_client: RedisClient

    # -----------------------------------------------------------------------------

    def __init__(
        self,
        redis_client: RedisClient,
    ) -> None:
        self.__redis_client = redis_client

    # -----------------------------------------------------------------------------

    def publish(self, origin: ModuleOrigin, routing_key: RoutingKey, data: Optional[Dict]) -> None:
        """Publish message to Redis exchange"""
        self.__redis_client.publish(origin=origin, routing_key=routing_key, data=data)
