# Configuration

To use [Sonoff](https://sonoff.tech) devices with the [FastyBird](https://www.fastybird.com) [IoT](https://en.wikipedia.org/wiki/Internet_of_things) ecosystem, you will need to configure at least one connector.
The connector can be configured using the [FastyBird](https://www.fastybird.com) [IoT](https://en.wikipedia.org/wiki/Internet_of_things) user interface or through the console.

There are three types of connectors available for selection:

- **Local** - This connector uses the local network for communication and supports only some [Sonoff](https://sonoff.tech) devices.
- **Cloud** - This connector communicates with the [Sonoff](https://sonoff.tech) cloud instance.
- **Auto** - This connector is combining both, Local and Auto and if device is supporting DIY mode, use it as primary, otherwise use cloud communication

## Configuring the Connectors and Devices through the Console

To configure the connector through the console, run the following command:

```shell
php bin/fb-console fb:sonoff-connector:install
```

> [!NOTE]
The path to the console command may vary depending on your FastyBird application distribution. For more information, refer to the FastyBird documentation.

This command is interactive and easy to operate.

The console will show you basic menu. To navigate in menu you could write value displayed in square brackets or you
could use arrows to select one of the options:

```
Sonoff connector - installer
============================

 ! [NOTE] This action will create|update|delete connector configuration

 What would you like to do? [Nothing]:
  [0] Create connector
  [1] Edit connector
  [2] Delete connector
  [3] Manage connector
  [4] List connectors
  [5] Nothing
 > 0
```

### Create connector

If you choose to create a new connector, you will be asked to choose the mode in which the connector will communicate with the devices:

```
 In what mode should this connector communicate with Sonoff devices? [Automatic selection mode (combined local & cloud)]:
  [0] Automatic selection mode (combined local & cloud)
  [1] Local network mode
  [2] Cloud server mode
 > 0
```

You will then be asked to provide a connector identifier and name:

```
 Provide connector identifier:
 > my-sonoff
```

```
 Provide connector name:
 > My Sonoff
```

After providing the necessary information, your new [Sonoff](https://sonoff.tech) connector will be ready for use.

```
 [OK] New connector "My Sonoff" was successfully created
```

### Connectors and Devices management

With this console command you could manage all your connectors and their devices. Just use the main menu to navigate to proper action.

## Configuring the Connector with the FastyBird User Interface

You can also configure the [Sonoff](https://sonoff.tech) connector using the [FastyBird](https://www.fastybird.com) [IoT](https://en.wikipedia.org/wiki/Internet_of_things) user interface. For more information on how to do this,
please refer to the [FastyBird](https://www.fastybird.com) [IoT](https://en.wikipedia.org/wiki/Internet_of_things) documentation.
