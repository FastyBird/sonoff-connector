# Naming Convention

The connector uses the following naming convention for its entities:

## Connector

A connector entity in the [FastyBird](https://www.fastybird.com) [IoT](https://en.wikipedia.org/wiki/Internet_of_things) ecosystem refers to a entity which is holding basic configuration
and is responsible for managing communication with [Sonoff](https://sonoff.tech) devices and other [FastyBird](https://www.fastybird.com) [IoT](https://en.wikipedia.org/wiki/Internet_of_things) ecosystem services.

## Device

A device in the [FastyBird](https://www.fastybird.com) [IoT](https://en.wikipedia.org/wiki/Internet_of_things) ecosystem refers to a entity which is representing a physical [Sonoff](https://sonoff.tech) device.

## Channel

Chanel is a mapped property to physical device capability entity.

## Property

A property in the [FastyBird](https://www.fastybird.com) [IoT](https://en.wikipedia.org/wiki/Internet_of_things) ecosystem refers to a entity which is holding configuration values or
device actual state of a device. Connector, Device and Channel entity has own Property entities.

### Connector Property

Connector related properties are used to store configuration like `cloud username`, `cloud password` or `application id`. This configuration
values are used to connect to [Sonoff](https://sonoff.tech) cloud.

### Device Property

Device related properties are used to store configuration like `ip address`, `communication port` or to store basic device information
like `hardware model`, `manufacturer` or `api key`. Some of them have to be configured to be able to use this connector
or to communicate with device. In case some of the mandatory property is missing, connector will log and error.

### Channel Property

Channel related properties are used for storing actual state of [Sonoff](https://sonoff.tech) device. It could be a switch `state` or a light `brightness`.
These values are read from device and stored in system.

## Device Mode

There are two devices modes supported by this connector.

The first mode is **Cloud mode** and uses communication with [Sonoff](https://sonoff.tech) servers.
The second mode is **DIY mode** and is supported by some [Sonoff](https://sonoff.tech) devices. It allows you to control device
through local API.
