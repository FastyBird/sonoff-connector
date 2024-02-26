<p align="center">
	<img src="https://github.com/fastybird/.github/blob/main/assets/repo_title.png?raw=true" alt="FastyBird"/>
</p>

# FastyBird IoT Sonoff connector

[![Build Status](https://flat.badgen.net/github/checks/FastyBird/sonoff-connector/main?cache=300&style=flat-square)](https://github.com/FastyBird/sonoff-connector/actions)
[![Licence](https://flat.badgen.net/github/license/FastyBird/sonoff-connector?cache=300&style=flat-square)](https://github.com/FastyBird/sonoff-connector/blob/main/LICENSE.md)
[![Code coverage](https://flat.badgen.net/coveralls/c/github/FastyBird/sonoff-connector?cache=300&style=flat-square)](https://coveralls.io/r/FastyBird/sonoff-connector)
[![Mutation testing](https://img.shields.io/endpoint?style=flat-square&url=https%3A%2F%2Fbadge-api.stryker-mutator.io%2Fgithub.com%2FFastyBird%2Fsonoff-connector%2Fmain)](https://dashboard.stryker-mutator.io/reports/github.com/FastyBird/sonoff-connector/main)

![PHP](https://flat.badgen.net/packagist/php/FastyBird/sonoff-connector?cache=300&style=flat-square)
[![Latest stable](https://flat.badgen.net/packagist/v/FastyBird/sonoff-connector/latest?cache=300&style=flat-square)](https://packagist.org/packages/FastyBird/sonoff-connector)
[![Downloads total](https://flat.badgen.net/packagist/dt/FastyBird/sonoff-connector?cache=300&style=flat-square)](https://packagist.org/packages/FastyBird/sonoff-connector)
[![PHPStan](https://flat.badgen.net/static/PHPStan/enabled/green?cache=300&style=flat-square)](https://github.com/phpstan/phpstan)

***

## What is Sonoff connector?

Sonoff connector is extension for [FastyBird](https://www.fastybird.com) [IoT](https://en.wikipedia.org/wiki/Internet_of_things) ecosystem
which is integrating [Sonoff](https://sonoff.tech) devices.

### Features:

- Support for Sonoff DIY and cloud devices, allowing users to connect and control a wide range of Sonoff devices
- Automated device discovery feature, which automatically detects and adds Sonoff devices to the FastyBird ecosystem
- Sonoff Connector management for the [FastyBird](https://www.fastybird.com) [IoT](https://en.wikipedia.org/wiki/Internet_of_things) [devices module](https://github.com/FastyBird/devices-module), allowing users to easily manage and monitor Sonoff devices
- Advanced device management features, such as controlling power status, measuring energy consumption, and reading sensor data
- [{JSON:API}](https://jsonapi.org/) schemas for full API access, providing a standardized and consistent way for developers to access and manipulate Sonoff device data
- Regular updates with new features and bug fixes, ensuring that the Sonoff Connector is always up-to-date and reliable.

Sonoff Connector is a distributed extension that is developed in [PHP](https://www.php.net), built on the [Nette](https://nette.org) and [Symfony](https://symfony.com) frameworks,
and is licensed under [Apache2](http://www.apache.org/licenses/LICENSE-2.0).

## Requirements

Sonoff connector is tested against PHP 8.2 and require installed [Process Control](https://www.php.net/manual/en/book.pcntl.php)
PHP extension.

## Installation

This extension is part of the [FastyBird](https://www.fastybird.com) [IoT](https://en.wikipedia.org/wiki/Internet_of_things) ecosystem and is installed by default.
In case you want to create you own distribution of [FastyBird](https://www.fastybird.com) [IoT](https://en.wikipedia.org/wiki/Internet_of_things) ecosystem you could install this extension with  [Composer](http://getcomposer.org/):

```sh
composer require fastybird/sonoff-connector
```

## Documentation

:book: Learn how to connect Sonoff devices and manage them with [FastyBird](https://www.fastybird.com) [IoT](https://en.wikipedia.org/wiki/Internet_of_things) system
in [documentation](https://github.com/FastyBird/sonoff-connector/wiki).

# FastyBird

<p align="center">
	<img src="https://github.com/fastybird/.github/blob/main/assets/fastybird_row.svg?raw=true" alt="FastyBird"/>
</p>

FastyBird is an Open Source IOT solution built from decoupled components with powerful API and the highest quality code. Read more on [fastybird.com.com](https://www.fastybird.com).

## Documentation

:book: Documentation is available on [docs.fastybird.com](https://docs.fastybird.com).

## Contributing

The sources of this package are contained in the [FastyBird monorepo](https://github.com/FastyBird/fastybird). We welcome
contributions for this package on [FastyBird/fastybird](https://github.com/FastyBird/).

## Feedback

Use the [issue tracker](https://github.com/FastyBird/fastybird/issues) for bugs reporting or send an [mail](mailto:code@fastybird.com)
to us or you could reach us on [X newtwork](https://x.com/fastybird) for any idea that can improve the project.

Thank you for testing, reporting and contributing.

## Changelog

For release info check [release page](https://github.com/FastyBird/fastybird/releases).

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
