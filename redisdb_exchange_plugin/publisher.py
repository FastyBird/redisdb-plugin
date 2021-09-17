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
import json
from modules_metadata.routing import RoutingKey
from modules_metadata.types import ModuleOrigin

# Library libs
from redisdb_exchange_plugin.connection import RedisClient


class Publisher(RedisClient):
    """
    Redis data exchange publisher

    @package        FastyBird:RedisDbExchangePlugin!
    @module         publisher

    @author         Adam Kadlec <adam.kadlec@fastybird.com>
    """
    def publish(self, origin: ModuleOrigin, routing_key: RoutingKey, data: dict) -> None:
        """Publish message to Redis exchange"""
        message: dict = {
            "routing_key": routing_key.value,
            "origin": origin.value,
            "sender_id": self.identifier,
            "data": data,
        }

        result: int = self.client.publish(self.channel_name, json.dumps(message))

        self.logger.debug(
            "Successfully published message to: %d consumers via Redis with key: %s",
            result,
            routing_key
        )

    # -----------------------------------------------------------------------------

    def __del__(self):
        self.close()
