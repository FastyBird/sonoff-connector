<p align="center">
	<img src="https://github.com/fastybird/.github/blob/main/assets/repo_title.png?raw=true" alt="FastyBird"/>
</p>

> [!IMPORTANT]
This documentation is meant to be used by developers or users which has basic programming skills. If you are regular user
please use FastyBird IoT documentation which is available on [docs.fastybird.com](https://docs.fastybird.com).

The [FastyBird](https://www.fastybird.com) [IoT](https://en.wikipedia.org/wiki/Internet_of_things) Sonoff Connector is an extension for the [FastyBird](https://www.fastybird.com) [IoT](https://en.wikipedia.org/wiki/Internet_of_things) ecosystem that enables seamless integration
with [Sonoff](https://sonoff.tech) devices. It allows users to easily connect and control [Sonoff](https://sonoff.tech) devices from within the [FastyBird](https://www.fastybird.com) [IoT](https://en.wikipedia.org/wiki/Internet_of_things) ecosystem,
providing a simple and user-friendly interface for managing and monitoring your devices.

# About Connector

This connector has some services divided into namespaces. All services are preconfigured and imported into application
container automatically.

```
\FastyBird\Connector\Sonoff
  \API - Services and helpers related to API - for managing data exchange validation and data parsing
  \Clients - Services which handle communication with Sonoff devices or clouds
  \Commands - Services used for user console interface
  \Entities - All entities used by connector
  \Helpers - Useful helpers for reading values, bulding entities etc.
  \Queue - Services related to connector internal communication
  \Schemas - {JSON:API} schemas mapping for API requests
  \Services - Communication services factories
  \Translations - Connector translations
  \Writers - Services for handling request from other services
```

All services, helpers, etc. are written to be self-descriptive :wink:.

> [!TIP]
To better understand what some parts of the connector meant to be used for, please refer to the [Naming Convention](Naming-Convention) page.

## Using Connector

The connector is ready to be used as is. Has configured all services in application container and there is no need to develop
some other services or bridges.

> [!TIP]
Find fundamental details regarding the installation and configuration of this connector on the [Configuration](Configuration) page.

> [!TIP]
The connector features a built-in physical device discovery capability, and you can find detailed information about device
discovery on the dedicated [Discovery](Discovery) page.

This connector is equipped with interactive console. With this console commands you could manage almost all connector features.

* **fb:sonoff-connector:install**: is used for connector installation and configuration. With interactive menu you could manage connector and devices.
* **fb:sonoff-connector:discover**: is used for direct devices discover. This command will trigger actions which are responsible for devices discovery.
* **fb:sonoff-connector:execute**: is used for connector execution. It is simple command that will trigger all services which are related to communication with Sonoff devices and services with other [FastyBird](https://www.fastybird.com) [IoT](https://en.wikipedia.org/wiki/Internet_of_things) ecosystem services like state storage, or user interface communication.

Each console command could be triggered like this :nerd_face:

```shell
php bin/fb-console fb:sonoff-connector:install
```

> [!NOTE]
The path to the console command may vary depending on your FastyBird application distribution. For more information, refer to the FastyBird documentation.

# Troubleshooting

## Discovery Issues

In some cases, [Sonoff](https://sonoff.tech) devices in DIY mode may not be discovered. This is usually due to issues with mDNS service. Each [Sonoff](https://sonoff.tech) device
sends out multicast information, but some routers or other network components may block this communication.
To resolve this issue, refer to your router's configuration and check if there are any blocks or configurations that may
be blocking mDNS service.

Devices are broadcasting mDNS messages only in specific intervals and if the state is changed. So you could help do discover by changing device status.
