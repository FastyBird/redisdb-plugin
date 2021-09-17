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
Redis connections
"""

# Library dependencies
import logging
from abc import ABC
from typing import Dict
from redis import Redis


class RedisClient(ABC):
    """
    Redis client

    @package        FastyBird:RedisDbExchangePlugin!
    @module         connection

    @author         Adam Kadlec <adam.kadlec@fastybird.com>
    """
    __redis_client: Redis

    __identifier: str
    __channel_name: str

    __logger: logging.Logger

    # -----------------------------------------------------------------------------

    def __init__(
        self,
        config: Dict,
        identifier: str,
        channel_name: str = "fb_exchange",
    ) -> None:
        self.__redis_client = Redis(
            host=config.get("host", "127.0.0.1"),
            port=int(config.get("port", 6379)),
            username=config.get("username", None),
            password=config.get("password", None),
        )

        self.__identifier = identifier
        self.__channel_name = channel_name

        self.__logger = logging.getLogger("dummy")

    # -----------------------------------------------------------------------------

    @property
    def client(self) -> Redis:
        """Get Redis client connection"""
        return self.__redis_client

    # -----------------------------------------------------------------------------

    @property
    def identifier(self) -> str:
        """Get created exchange instance identifier"""
        return self.__identifier

    # -----------------------------------------------------------------------------

    @property
    def channel_name(self) -> str:
        """Get exchange channel name"""
        return self.__channel_name

    # -----------------------------------------------------------------------------

    @property
    def logger(self) -> logging.Logger:
        """Get application logger"""
        return self.__logger

    # -----------------------------------------------------------------------------

    def set_logger(self, logger: logging.Logger) -> None:
        """Configure custom logger handler"""
        self.__logger = logger

    # -----------------------------------------------------------------------------

    def close(self) -> None:
        """Close opened connection to Redis database"""
        self.__redis_client.close()
