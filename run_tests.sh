#!/bin/bash

export ELASTICSEARCH_ROOT_VERSION=2 && vendor/bin/phpunit;
export ELASTICSEARCH_ROOT_VERSION=5 && vendor/bin/phpunit;

