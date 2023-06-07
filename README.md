# FastyBird IoT Sonoff connector

[![Build Status](https://badgen.net/github/checks/FastyBird/sonoff-connector/main?cache=300&style=flat-square)](https://github.com/FastyBird/sonoff-connector/actions)
[![Licence](https://badgen.net/github/license/FastyBird/sonoff-connector?cache=300&style=flat-square)](https://github.com/FastyBird/sonoff-connector/blob/main/LICENSE.md)
[![Code coverage](https://badgen.net/coveralls/c/github/FastyBird/sonoff-connector?cache=300&style=flat-square)](https://coveralls.io/r/FastyBird/sonoff-connector)
[![Mutation testing](https://img.shields.io/endpoint?style=flat-square&url=https%3A%2F%2Fbadge-api.stryker-mutator.io%2Fgithub.com%2FFastyBird%2Fsonoff-connector%2Fmain)](https://dashboard.stryker-mutator.io/reports/github.com/FastyBird/sonoff-connector/main)

![PHP](https://badgen.net/packagist/php/FastyBird/sonoff-connector?cache=300&style=flat-square)
[![PHP latest stable](https://badgen.net/packagist/v/FastyBird/sonoff-connector/latest?cache=300&style=flat-square)](https://packagist.org/packages/FastyBird/sonoff-connector)
[![PHP downloads total](https://badgen.net/packagist/dt/FastyBird/sonoff-connector?cache=300&style=flat-square)](https://packagist.org/packages/FastyBird/sonoff-connector)
[![PHPStan](https://img.shields.io/badge/phpstan-enabled-brightgreen.svg?style=flat-square)](https://github.com/phpstan/phpstan)

***

## What is Sonoff connector?

Sonoff connector is extension for [FastyBird](https://www.fastybird.com) [IoT](https://en.wikipedia.org/wiki/Internet_of_things) ecosystem
which is integrating [Sonoff](https://sonoff.tech) devices.

Sonoff Connector is a distributed extension that is developed in [PHP](https://www.php.net), built on the [Nette](https://nette.org) and [Symfony](https://symfony.com) frameworks,
and is licensed under [Apache2](http://www.apache.org/licenses/LICENSE-2.0).

### Features:

- Support for Sonoff DIY and cloud devices, allowing users to connect and control a wide range of Sonoff devices
- Automated device discovery feature, which automatically detects and adds Sonoff devices to the FastyBird ecosystem
- Sonoff Connector management for the [FastyBird](https://www.fastybird.com) [IoT](https://en.wikipedia.org/wiki/Internet_of_things) [devices module](https://github.com/FastyBird/devices-module), allowing users to easily manage and monitor Sonoff devices
- Advanced device management features, such as controlling power status, measuring energy consumption, and reading sensor data
- [{JSON:API}](https://jsonapi.org/) schemas for full API access, providing a standardized and consistent way for developers to access and manipulate Sonoff device data
- Regular updates with new features and bug fixes, ensuring that the Sonoff Connector is always up-to-date and reliable.

## Requirements

Sonoff connector is tested against PHP 8.1 and require installed [Process Control](https://www.php.net/manual/en/book.pcntl.php)
PHP extension.

## Installation

### Manual installation

The best way to install **fastybird/sonoff-connector** is using [Composer](http://getcomposer.org/):

```sh
composer require fastybird/sonoff-connector
```

### Marketplace installation [WIP]

You could install this connector in your [FastyBird](https://www.fastybird.com) [IoT](https://en.wikipedia.org/wiki/Internet_of_things)
application under marketplace section.

## Documentation

Learn how to connect Sonoff devices and manage them with [FastyBird](https://www.fastybird.com) [IoT](https://en.wikipedia.org/wiki/Internet_of_things) system
in [documentation](https://github.com/FastyBird/sonoff-connector/wiki).

## Feedback

Use the [issue tracker](https://github.com/FastyBird/fastybird/issues) for bugs
or [mail](mailto:code@fastybird.com) or [Tweet](https://twitter.com/fastybird) us for any idea that can improve the
project.

Thank you for testing, reporting and contributing.

## Changelog

For release info check [release page](https://github.com/FastyBird/fastybird/releases).

## Contribute

The sources of this package are contained in the [FastyBird monorepo](https://github.com/FastyBird/fastybird). We welcome contributions for this package on [FastyBird/fastybird](https://github.com/FastyBird/).

## Maintainers

<table>
	<tbody>
		<tr>
			<td align="center">
				<a href="https://github.com/akadlec">
					<img alt="akadlec" width="80" height="80" src="https://avatars3.githubusercontent.com/u/1866672?s=460&amp;v=4" />
				</a>
				<br>
				<a href="https://github.com/akadlec">Adam Kadlec</a>
			</td>
		</tr>
	</tbody>
</table>

***
Homepage [https://www.fastybird.com](https://www.fastybird.com) and
repository [https://github.com/fastybird/sonoff-connector](https://github.com/fastybird/sonoff-connector).
