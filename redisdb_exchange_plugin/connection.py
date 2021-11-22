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
Redis DB exchange plugin connection service
"""

# Python base dependencies
import json
import uuid
from typing import Dict, Optional, Union

# Library dependencies
from modules_metadata.routing import RoutingKey
from modules_metadata.types import ModuleOrigin
from redis import Redis
from redis.client import PubSub

# Library libs
from redisdb_exchange_plugin.exceptions import InvalidStateException
from redisdb_exchange_plugin.logger import Logger


class RedisClient:
    """
    Redis client

    @package        FastyBird:RedisDbExchangePlugin!
    @module         connection

    @author         Adam Kadlec <adam.kadlec@fastybird.com>
    """

    __redis_client: Redis

    __pub_sub: Optional[PubSub] = None

    __identifier: str
    __channel_name: str

    __logger: Logger

    # -----------------------------------------------------------------------------

    def __init__(  # pylint: disable=too-many-arguments
        self,
        host: str,
        port: int,
        channel_name: str,
        logger: Logger,
        username: Optional[str] = None,
        password: Optional[str] = None,
    ) -> None:
        self.__redis_client = Redis(
            host=host,
            port=port,
            username=username,
            password=password,
        )

        self.__identifier = uuid.uuid4().__str__()
        self.__channel_name = channel_name

        self.__logger = logger

    # -----------------------------------------------------------------------------

    def publish(self, origin: ModuleOrigin, routing_key: RoutingKey, data: Optional[Dict]) -> None:
        """Publish message to default exchange channel"""
        message = {
            "routing_key": routing_key.value,
            "origin": origin.value,
            "sender_id": self.__identifier,
            "data": data,
        }

        result: int = self.__redis_client.publish(channel=self.__channel_name, message=json.dumps(message))

        self.__logger.debug(
            "Successfully published message to: %d consumers via RedisDB exchange plugin with key: %s",
            result,
            routing_key,
        )

    # -----------------------------------------------------------------------------

    def subscribe(self) -> None:
        """Subscribe to default exchange channel"""
        if self.__pub_sub is not None:
            raise InvalidStateException("Exchange is already subscribed to exchange")

        # Connect to pub sub exchange
        self.__pub_sub = self.__redis_client.pubsub()
        # Subscribe to channel
        self.__pub_sub.subscribe(self.__channel_name)

        self.__logger.debug(
            "Successfully subscribed to RedisDB exchange channel: %s",
            self.__channel_name,
        )

    # -----------------------------------------------------------------------------

    def unsubscribe(self) -> None:
        """Unsubscribe from default exchange channel"""
        if self.__pub_sub is not None:
            # Unsubscribe from channel
            self.__pub_sub.unsubscribe(self.__channel_name)
            # Disconnect from pub sub exchange
            self.__pub_sub.close()

            self.__logger.debug(
                "Successfully unsubscribed from RedisDB exchange channel: %s",
                self.__channel_name,
            )

            self.__pub_sub = None

    # -----------------------------------------------------------------------------

    def receive(self) -> Optional[Dict]:
        """Try to receive new message from exchange"""
        if self.__pub_sub is not None:
            result = self.__pub_sub.get_message()

            if (
                result is not None
                and result.get("type") == "message"
                and isinstance(result.get("data", bytes("{}", "utf-8")), bytes)
            ):
                message_data = result.get("data", bytes("{}", "utf-8"))
                message = message_data.decode("utf-8") if isinstance(message_data, bytes) else ""

                try:
                    data: Dict[str, Union[str, int, float, bool, None]] = json.loads(message)

                    # Ignore own messages
                    if data.get("sender_id", None) is not None and data.get("sender_id", None) == self.__identifier:
                        return None

                    return data

                except json.JSONDecodeError as ex:
                    self.__logger.exception(ex)

        return None

    # -----------------------------------------------------------------------------

    def close(self) -> None:
        """Close opened connection to Redis database"""
        self.unsubscribe()

        self.__redis_client.close()

    # -----------------------------------------------------------------------------

    @property
    def identifier(self) -> str:
        """Get connection generated identifier"""
        return self.__identifier

    # -----------------------------------------------------------------------------

    def __del__(self) -> None:
        self.__redis_client.close()
