The [FastyBird](https://www.fastybird.com) [IoT](https://en.wikipedia.org/wiki/Internet_of_things) Sonoff Connector is an extension for the [FastyBird](https://www.fastybird.com) [IoT](https://en.wikipedia.org/wiki/Internet_of_things) ecosystem that enables seamless integration
with [Sonoff](https://sonoff.tech) devices. It allows users to easily connect and control [Sonoff](https://sonoff.tech) devices from within the [FastyBird](https://www.fastybird.com) [IoT](https://en.wikipedia.org/wiki/Internet_of_things) ecosystem,
providing a simple and user-friendly interface for managing and monitoring your devices.

# Naming Convention

The connector uses the following naming convention for its entities:

## Connector

A connector is an entity that manages communication with [Sonoff](https://sonoff.tech) devices. It needs to be configured for a specific device interface.

## Device

A device is an entity that represents a physical [Sonoff](https://sonoff.tech) device.

## Device Mode

There are two devices modes supported by this connector.
The first mode is cloud mode and uses communication with [Sonoff](https://sonoff.tech) servers.
The second mode is DIY mode and is supported by some [Sonoff](https://sonoff.tech) devices. It allows you to control device
through local API.

# Configuration

To use [Sonoff](https://sonoff.tech) devices with the [FastyBird](https://www.fastybird.com) [IoT](https://en.wikipedia.org/wiki/Internet_of_things) ecosystem, you will need to configure at least one connector.
The connector can be configured using the [FastyBird](https://www.fastybird.com) [IoT](https://en.wikipedia.org/wiki/Internet_of_things) user interface or through the console.

There are three types of connectors available for selection:

- **Local** - This connector uses the local network for communication and supports only some [Sonoff](https://sonoff.tech) devices.
- **Cloud** - This connector communicates with the [Sonoff](https://sonoff.tech) cloud instance.
- **Auto** - This connector is combining both, Local and Auto and if device is supporting DIY mode, use it as primary, otherwise use cloud communication

## Configuring the Connector through the Console

To configure the connector through the console, run the following command:

```shell
php bin/fb-console fb:sonoff-connector:initialize
```

> **NOTE:**
The path to the console command may vary depending on your FastyBird application distribution. For more information, refer to the FastyBird documentation.

The console will ask you to confirm that you want to continue with the configuration.

```shell
Sonoff connector - initialization
=================================

 ! [NOTE] This action will create|update|delete connector configuration.                                                       

 Would you like to continue? (yes/no) [no]:
 > y
```

You will then be prompted to choose an action:

```shell
 What would you like to do?:
  [0] Create new connector configuration
  [1] Edit existing connector configuration
  [2] Delete existing connector configuration
 > 0
```

If you choose to create a new connector, you will be asked to choose the mode in which the connector will communicate with the devices:

```shell
 In what mode should this connector communicate with devices? [Local network mode]:
  [0] Auto mode
  [1] Local network mode
  [2] Cloud server mode
 > 0
```

You will then be asked to provide a connector identifier and name:

```shell
 Provide connector identifier:
 > my-sonoff
```

```shell
 Provide connector name:
 > My Sonoff
```

After providing the necessary information, your new [Sonoff](https://sonoff.tech) connector will be ready for use.

```shell
 [OK] New connector "My Sonoff" was successfully created                                                                
```

## Configuring the Connector with the FastyBird User Interface

You can also configure the [Sonoff](https://sonoff.tech) connector using the [FastyBird](https://www.fastybird.com) [IoT](https://en.wikipedia.org/wiki/Internet_of_things) user interface. For more information on how to do this,
please refer to the [FastyBird](https://www.fastybird.com) [IoT](https://en.wikipedia.org/wiki/Internet_of_things) documentation.

# Devices Discovery

The [Sonoff](https://sonoff.tech) connector includes a built-in feature for automatic device discovery. This feature can be triggered manually
through a console command or from the [FastyBird](https://www.fastybird.com) [IoT](https://en.wikipedia.org/wiki/Internet_of_things) user interface.

## Manual Console Command

To manually trigger device discovery, use the following command:

```shell
php bin/fb-console fb:sonoff-connector:discover
```

> **NOTE:**
The path to the console command may vary depending on your FastyBird application distribution. For more information, refer to the FastyBird documentation.

The console will prompt for confirmation before proceeding with the discovery process.

```shell
Sonoff connector - discovery
============================

 ! [NOTE] This action will run connector devices discovery.

 Would you like to continue? (yes/no) [no]:
 > y
```

You will then be prompted to select the connector to use for the discovery process.

```shell
 Would you like to discover devices with "My Sonoff" connector (yes/no) [no]:
 > y
```

The connector will then begin searching for new [Sonoff](https://sonoff.tech) devices, which may take a few minutes to complete. Once finished,
a list of found devices will be displayed.

```shell
 [INFO] Starting Sonoff connector discovery...

[============================] 100% 36 secs/36 secs %

 [INFO] Found 2 new devices


+---+--------------------------------------+----------------+---------------+--------------+
| # | ID                                   | Name           | Type          | IP address   |
+---+--------------------------------------+----------------+---------------+--------------+
| 1 | 89b1d985-0183-4c05-8d28-69f4acf4128e | MyDevice09889e | SNSW-001P16EU | N/A          |
| 2 | 8f377380-860f-4ac9-a4de-4be73e5ef59a | MyDevice04690b | SNSW-001P16EU | 10.10.10.126 |
+---+--------------------------------------+----------------+---------------+--------------+

 [OK] Devices discovery was successfully finished
```

Now that all newly discovered devices have been found, they are available in the [FastyBird](https://www.fastybird.com) [IoT](https://en.wikipedia.org/wiki/Internet_of_things) system and can be utilized.

# Troubleshooting

## Discovery Issues

In some cases, [Sonoff](https://sonoff.tech) devices in DIY mode may not be discovered. This is usually due to issues with mDNS service. Each [Sonoff](https://sonoff.tech) device
sends out multicast information, but some routers or other network components may block this communication.
To resolve this issue, refer to your router's configuration and check if there are any blocks or configurations that may
be blocking mDNS service.

Devices are broadcasting mDNS messages only in specific intervals and if the state is changed. So you could help do discover by changing device status.
