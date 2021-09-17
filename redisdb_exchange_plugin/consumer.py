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
import time
from typing import Callable, Dict
from threading import Thread
import modules_metadata.exceptions as metadata_exceptions
from modules_metadata.loader import load_schema
from modules_metadata.routing import RoutingKey
from modules_metadata.types import ModuleOrigin
from modules_metadata.validator import validate
from redis.client import PubSub

# Library libs
from redisdb_exchange_plugin.connection import RedisClient
from redisdb_exchange_plugin.exceptions import HandleDataException


class Consumer(Thread, RedisClient):
    """
    Redis data exchange consumer

    @package        FastyBird:RedisDbExchangePlugin!
    @module         consumer

    @author         Adam Kadlec <adam.kadlec@fastybird.com>
    """
    __stopped: bool = False

    __redis_pub_sub: PubSub

    __handle_data: Callable[[ModuleOrigin, RoutingKey, Dict or None], None]

    # -----------------------------------------------------------------------------

    def __init__(
        self,
        identifier: str,
        config: Dict,
        channel_name: str = "fb_exchange",
    ) -> None:
        Thread.__init__(self)
        RedisClient.__init__(self, config, identifier, channel_name)

        self.__redis_pub_sub = self.client.pubsub()
        self.__redis_pub_sub.subscribe(self.channel_name)

        # Threading config...
        self.setDaemon(True)
        self.setName("Redis DB exchange thread")

    # -----------------------------------------------------------------------------

    def set_data_callback(self, callback: Callable[[ModuleOrigin, RoutingKey, Dict or None], None]) -> None:
        """Set handle data callback"""
        self.__handle_data = callback

    # -----------------------------------------------------------------------------

    def run(self) -> None:
        """Process Redis exchange messages"""
        self.__stopped = False

        while not self.__stopped:
            try:
                result = self.__redis_pub_sub.get_message()

                if (
                        result is not None
                        and result.get("type") == "message"
                        and isinstance(result.get("data", bytes("{}", "utf-8")), bytes)
                ):
                    message = result.get("data", bytes("{}", "utf-8"))
                    message = message.decode("utf-8")

                    self.__receive(message)

                time.sleep(0.001)

            except OSError:
                self.__stopped = True

    # -----------------------------------------------------------------------------

    def close(self) -> None:
        """Close all opened connections & stop server thread"""
        self.__stopped = True

        # Unsubscribe from exchange
        self.__redis_pub_sub.unsubscribe(self.channel_name)
        # Disconnect from pub sub exchange
        self.__redis_pub_sub.close()
        # Disconnect from server
        super().close()

    # -----------------------------------------------------------------------------

    def __receive(self, message: str) -> None:
        try:
            parsed_data: dict = json.loads(message)

            # Ignore own messages
            if (
                    parsed_data.get("sender_id", None) is not None
                    and parsed_data.get("sender_id", None) == self.identifier
            ):
                return

            origin: ModuleOrigin or None = self.__validate_origin(origin=parsed_data.get("origin", None))
            routing_key: RoutingKey or None = self.__validate_routing_key(
                routing_key=parsed_data.get("routing_key", None),
            )

            if (
                    routing_key is not None
                    and origin is not None
                    and parsed_data.get("data", None) is not None
                    and isinstance(parsed_data.get("data", None), dict) is True
            ):
                data: Dict or None = self.__validate_data(
                    origin=origin,
                    routing_key=routing_key,
                    data=parsed_data.get("data", None),
                )

                self.__handle_data(
                    origin,
                    routing_key,
                    data,
                )

            else:
                self.logger.warning("Received exchange message is not valid")

        except json.JSONDecodeError as ex:
            self.logger.exception(ex)

        except HandleDataException as ex:
            self.logger.exception(ex)

    # -----------------------------------------------------------------------------

    @staticmethod
    def __validate_origin(origin: str or None) -> ModuleOrigin or None:
        if (
                origin is not None
                and isinstance(origin, str) is True
                and ModuleOrigin.has_value(origin)
        ):
            return ModuleOrigin(origin)

        return None

    # -----------------------------------------------------------------------------

    @staticmethod
    def __validate_routing_key(routing_key: str or None) -> RoutingKey or None:
        if (
                routing_key is not None
                and isinstance(routing_key, str) is True
                and RoutingKey.has_value(routing_key)
        ):
            return RoutingKey(routing_key)

        return None

    # -----------------------------------------------------------------------------

    def __validate_data(self, origin: ModuleOrigin, routing_key: RoutingKey, data: Dict) -> Dict:
        """
        Validate received exchange message against defined schema
        """
        try:
            schema: str = load_schema(origin, routing_key)

        except metadata_exceptions.FileNotFoundException as ex:
            self.logger.error(
                "Schema file for origin: %s and routing key: %s could not be loaded",
                origin.value,
                routing_key.value,
            )

            raise HandleDataException("Provided data could not be validated") from ex

        except metadata_exceptions.InvalidArgumentException as ex:
            self.logger.error(
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
            self.logger.error(
                "Schema file for origin: %s and routing key: %s could not be parsed & compiled",
                origin.value,
                routing_key.value,
            )

            raise HandleDataException("Provided data could not be validated") from ex

        except metadata_exceptions.InvalidDataException as ex:
            raise HandleDataException("Provided data are not valid") from ex
